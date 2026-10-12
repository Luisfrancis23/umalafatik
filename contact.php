<?php
/**
 * Uma Lafatik — Contact page
 */
require_once __DIR__ . '/config/config.php';

$pdo = getDB();
$errors = [];
$sent = false;

$form = ['name' => '', 'email' => '', 'phone' => '', 'subject' => 'General enquiry', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Form session expired. Please try again.');
        redirect('/contact.php');
    }

    $form['name']    = str_in($_POST['name'] ?? '', 120);
    $form['email']   = email_in($_POST['email'] ?? '');
    $form['phone']   = phone_in($_POST['phone'] ?? '');
    $form['subject'] = in_array($_POST['subject'] ?? '', ['General enquiry', 'Reservation', 'Feedback', 'Partnership', 'Press'], true)
        ? $_POST['subject'] : 'General enquiry';
    $form['message'] = str_in($_POST['message'] ?? '', 2000);

    if (strlen($form['name']) < 2) $errors[] = 'Please enter your name.';
    if (!is_valid_email($form['email'])) $errors[] = 'Please enter a valid email address.';
    if (strlen($form['message']) < 10) $errors[] = 'Please write a message of at least 10 characters.';

    // Simple spam guard
    $honeypot = str_in($_POST['company'] ?? '', 255);
    if ($honeypot !== '') {
        $errors[] = 'Please try again.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO contact_messages (name, email, phone, subject, message)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$form['name'], $form['email'], $form['phone'], $form['subject'], $form['message']]);
        $sent = true;
    }
}

$page_title = 'Contact — ' . SITE_NAME;
$page_desc = 'Contact Uma Lafatik — call, email or visit us at ' . SITE_ADDRESS . '. We reply within one business day.';
$canonical = BASE_URL . '/contact.php';

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/navbar.php';
$active_page = 'contact';
?>

<section class="section" style="padding-top:9rem">
  <div class="container">
    <div class="section-head reveal">
      <p class="eyebrow">Contact</p>
      <h2>We'd love to hear from you</h2>
      <p>Questions, feedback, bookings for large groups — send us a message and we will reply within one business day.</p>
    </div>

    <div class="info-grid" style="align-items:start">
      <div class="reveal">
        <ul class="contact-list">
          <li>
            <span class="ci"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 12-9 12s-9-5-9-12a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></span>
            <div><strong>Visit us</strong><span><?= e(SITE_ADDRESS) ?></span></div>
          </li>
          <li>
            <span class="ci"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.08 4.18 2 2 0 0 1 4.06 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg></span>
            <div><strong>Call us</strong><a href="tel:+966500000000"><?= e(SITE_PHONE) ?></a></div>
          </li>
          <li>
            <span class="ci"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/><path d="M22 6l-10 7L2 6"/></svg></span>
            <div><strong>Email us</strong><a href="mailto:<?= e(SITE_EMAIL) ?>"><?= e(SITE_EMAIL) ?></a></div>
          </li>
          <li>
            <span class="ci"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/></svg></span>
            <div>
              <strong>Hours</strong>
              <span>Mon–Thu 12:00–23:00 · Fri–Sat 12:00–00:00 · Sun 13:00–22:00</span>
            </div>
          </li>
        </ul>

        <div class="social-links" style="margin-top:2rem">
          <a href="#" aria-label="Instagram"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17.2" cy="6.8" r="1.3" fill="currentColor"/></svg></a>
          <a href="#" aria-label="Facebook"><svg viewBox="0 0 24 24"><path d="M14 8h3V4.5h-3c-2.5 0-4 1.8-4 4.3V11H7v3.5h3V21h3.5v-6.5h3l.5-3.5h-3.5V8.7c0-.5.3-.7 1-.7z" fill="currentColor"/></svg></a>
          <a href="#" aria-label="X (Twitter)"><svg viewBox="0 0 24 24"><path d="M4 4l7.2 9.3L4.4 20h2.5l5.3-5.4L16.8 20H20l-7.5-9.7L18.9 4h-2.5l-4.7 4.9L8.2 4H4z" fill="currentColor"/></svg></a>
          <a href="#" aria-label="TikTok"><svg viewBox="0 0 24 24"><path d="M16.5 3c.4 2.2 1.8 3.7 4.1 3.9v3c-1.6 0-3-.5-4.1-1.3v6.6c0 3.4-2.5 5.8-5.7 5.8A5.4 5.4 0 0 1 5.4 15c0-3.2 2.7-5.5 6-5.3v3.1c-1.6-.3-3 .7-3 2.2 0 1.5 1.2 2.7 2.8 2.7 1.8 0 3.2-1.4 3.2-3.4V3h3.1z" fill="currentColor"/></svg></a>
        </div>

        <a href="<?= url('/reservation.php') ?>" class="btn btn-gold" style="margin-top:2rem">Reserve a Table</a>
      </div>

      <div class="form-card reveal" data-delay="1">
        <?php if ($sent): ?>
        <div class="flash flash-success" role="alert">Thank you for reaching out! We will get back to you within one business day.</div>
        <?php endif; ?>
        <?php if ($errors): ?>
        <div class="flash flash-error" role="alert"><?= implode('<br>', $errors) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= url('/contact.php') ?>">
          <?= csrf_input() ?>
          <input type="text" name="company" id="company" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">
          <div class="form-grid">
            <div class="field">
              <label for="name">Name</label>
              <input id="name" name="name" type="text" value="<?= e($form['name']) ?>" required maxlength="120" autocomplete="name">
            </div>
            <div class="field">
              <label for="email">Email</label>
              <input id="email" name="email" type="email" value="<?= e($form['email']) ?>" required maxlength="120" autocomplete="email">
            </div>
            <div class="field">
              <label for="phone">Phone <span class="text-muted">(optional)</span></label>
              <input id="phone" name="phone" type="tel" value="<?= e($form['phone']) ?>" maxlength="25" autocomplete="tel">
            </div>
            <div class="field">
              <label for="subject">Topic</label>
              <select id="subject" name="subject">
                <?php foreach (['General enquiry', 'Reservation', 'Feedback', 'Partnership', 'Press'] as $s): ?>
                <option value="<?= $s ?>" <?= $form['subject'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="field full">
              <label for="message">Message</label>
              <textarea id="message" name="message" required minlength="10" maxlength="2000" placeholder="How can we help?"><?= e($form['message']) ?></textarea>
            </div>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn-terra">
              Send Message
              <svg viewBox="0 0 24 24" aria-hidden="true" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
            </button>
          </div>
        </form>
      </div>
    </div>

    <div class="map-frame reveal" style="margin-top:3rem">
      <iframe
        title="Map — Uma Lafatik, <?= e(SITE_ADDRESS) ?>"
        src="https://www.google.com/maps?q=<?= urlencode(SITE_ADDRESS) ?>&output=embed"
        loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
    </div>
  </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
