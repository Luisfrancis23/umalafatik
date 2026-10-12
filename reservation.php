<?php
/**
 * Uma Lafatik — Reservation form
 */
require_once __DIR__ . '/config/config.php';

$pdo = getDB();
$user = is_logged_in() ? current_customer() : null;

$errors = [];
$form = [
    'name'    => $user ? trim($user['first_name'] . ' ' . $user['last_name']) : '',
    'email'   => $user ? (string) $user['email'] : '',
    'phone'   => $user ? (string) $user['phone'] : '',
    'guests'  => 2,
    'date'    => date('Y-m-d', strtotime('+1 day')),
    'time'    => '19:00',
    'seating' => 'Indoor',
    'requests' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Form session expired. Please try again.');
        redirect('/reservation.php');
    }

    $form['name']     = str_in($_POST['name'] ?? '', 120);
    $form['email']    = email_in($_POST['email'] ?? '');
    $form['phone']    = phone_in($_POST['phone'] ?? '');
    $form['guests']   = int_in($_POST['guests'] ?? 0, 1, (int) setting('max_guests', 20));
    $form['date']     = str_in($_POST['date'] ?? '', 10);
    $form['time']     = str_in($_POST['time'] ?? '', 5);
    $form['seating']  = in_array($_POST['seating'] ?? '', ['Indoor', 'Outdoor', 'Private'], true) ? $_POST['seating'] : 'Indoor';
    $form['requests'] = str_in($_POST['requests'] ?? '', 500);

    // Validation
    if (strlen($form['name']) < 2) $errors[] = 'Please enter your full name.';
    if (!is_valid_email($form['email'])) $errors[] = 'Please enter a valid email address.';
    if (strlen($form['phone']) < 7) $errors[] = 'Please enter a valid phone number.';
    if ($form['guests'] < 1) $errors[] = 'Please specify the number of guests.';

    $dateObj = false;
    try {
        $dateObj = new DateTime($form['date'] . ' ' . ($form['time'] ?: '00:00'));
    } catch (Exception $e) {
        $errors[] = 'Please choose a valid date and time.';
    }
    if ($dateObj && $dateObj < new DateTime('today')) {
        $errors[] = 'The reservation date must be today or in the future.';
    }
    if ($dateObj && $dateObj > new DateTime('+3 months')) {
        $errors[] = 'Reservations can be made up to 3 months in advance.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO reservations
             (user_id, name, email, phone, guests, reservation_date, reservation_time, seating, special_requests, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "pending")'
        );
        $stmt->execute([
            $user ? (int) $user['id'] : null,
            $form['name'],
            $form['email'],
            $form['phone'],
            $form['guests'],
            $dateObj->format('Y-m-d'),
            $dateObj->format('H:i:s'),
            $form['seating'],
            $form['requests'],
        ]);

        if ($user) {
            push_notification(
                $pdo,
                (int) $user['id'],
                'reservation',
                'Your reservation for ' . $form['guests'] . ' guest(s) on ' . $dateObj->format('M j, Y') . ' at ' . $dateObj->format('g:i A') . ' has been received. We will confirm shortly.',
                null,
                (int) $pdo->lastInsertId()
            );
        }

        set_flash('success', 'Your reservation request has been received! We will confirm by email and phone shortly.');
        redirect('/reservation.php?success=1');
    }
}

$page_title = 'Reserve a Table — ' . SITE_NAME;
$page_desc = 'Reserve a table at Uma Lafatik. Indoor, outdoor and private seating for every occasion.';
$canonical = BASE_URL . '/reservation.php';

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<section class="section" style="padding-top:9rem" id="reserve">
  <div class="container narrow">
    <div class="section-head reveal">
      <p class="eyebrow">Reservations</p>
      <h2>Reserve a Table</h2>
      <p>We hold each table for the entire evening — yours for as long as the conversation runs.</p>
    </div>

    <?php if (!empty($_GET['success'])): ?>
    <div class="flash flash-success" role="alert">Reservation received — see you soon! You can manage your reservations from your account dashboard.</div>
    <?php endif; ?>

    <div class="form-card reveal">
      <?php if ($errors): ?>
      <div class="flash flash-error" role="alert"><?= implode('<br>', $errors) ?></div>
      <?php endif; ?>

      <form method="post" action="<?= url('/reservation.php') ?>" id="reservationForm" novalidate>
        <?= csrf_input() ?>
        <div class="form-grid">
          <div class="field">
            <label for="name">Full name</label>
            <input id="name" name="name" type="text" value="<?= e($form['name']) ?>" required maxlength="120" autocomplete="name">
          </div>
          <div class="field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="<?= e($form['email']) ?>" required maxlength="120" autocomplete="email">
          </div>
          <div class="field">
            <label for="phone">Phone</label>
            <input id="phone" name="phone" type="tel" value="<?= e($form['phone']) ?>" required maxlength="25" autocomplete="tel">
          </div>
          <div class="field">
            <label for="guests">Guests</label>
            <select id="guests" name="guests" required>
              <?php for ($i = 1; $i <= (int) setting('max_guests', 20); $i++): ?>
              <option value="<?= $i ?>" <?= $form['guests'] == $i ? 'selected' : '' ?>><?= $i ?> guest<?= $i === 1 ? '' : 's' ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="field">
            <label for="date">Date</label>
            <input id="date" name="date" type="date" value="<?= e($form['date']) ?>" required min="<?= date('Y-m-d') ?>">
          </div>
          <div class="field">
            <label for="time">Time</label>
            <input id="time" name="time" type="time" value="<?= e($form['time']) ?>" required min="11:00" max="23:30">
          </div>
          <div class="field full">
            <label>Seating preference</label>
            <div class="option-tiles" role="radiogroup" aria-label="Seating preference">
              <label class="option-tile<?= $form['seating'] === 'Indoor' ? ' selected' : '' ?>">
                <input type="radio" name="seating" value="Indoor" <?= $form['seating'] === 'Indoor' ? 'checked' : '' ?>>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V8l7-5 7 5v13M9 21v-6h6v6"/></svg>
                Indoor
              </label>
              <label class="option-tile<?= $form['seating'] === 'Outdoor' ? ' selected' : '' ?>">
                <input type="radio" name="seating" value="Outdoor" <?= $form['seating'] === 'Outdoor' ? 'checked' : '' ?>>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                Outdoor
              </label>
              <label class="option-tile<?= $form['seating'] === 'Private' ? ' selected' : '' ?>">
                <input type="radio" name="seating" value="Private" <?= $form['seating'] === 'Private' ? 'checked' : '' ?>>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Private
              </label>
            </div>
          </div>
          <div class="field full">
            <label for="requests">Special requests <span class="text-muted">(optional)</span></label>
            <textarea id="requests" name="requests" placeholder="Occasions, allergies, dietary needs, a quiet corner…" maxlength="500"><?= e($form['requests']) ?></textarea>
          </div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-gold">
            <svg viewBox="0 0 24 24" aria-hidden="true" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/></svg>
            Reserve a Table
          </button>
          <span class="text-muted" style="font-size:0.88rem">Free cancellation up to 4 hours before.</span>
        </div>
      </form>
    </div>

    <div class="info-grid" style="margin-top:3rem">
      <div class="reveal">
        <h3 style="margin-bottom:1rem">Opening Hours</h3>
        <table class="hours-table">
          <tr><td>Monday – Thursday</td><td>12:00 – 23:00</td></tr>
          <tr><td>Friday – Saturday</td><td>12:00 – 00:00</td></tr>
          <tr><td>Sunday</td><td>13:00 – 22:00</td></tr>
        </table>
      </div>
      <div class="reveal" data-delay="1">
        <h3 style="margin-bottom:1rem">Good to know</h3>
        <ul class="contact-list">
          <li>
            <span class="ci"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/></svg></span>
            <div><strong>Confirmation</strong><span>We confirm every reservation by phone within 30 minutes.</span></div>
          </li>
          <li>
            <span class="ci"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 2C8 6 6 8.5 6 12a6 6 0 0 0 12 0c0-3.5-2-6-6-10z"/></svg></span>
            <div><strong>Large groups</strong><span>For 10+ guests, please call <?= e(SITE_PHONE) ?>.</span></div>
          </li>
        </ul>
      </div>
    </div>
  </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
$page_scripts = [];
