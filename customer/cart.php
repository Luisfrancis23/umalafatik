<?php
/**
 * Customer — shopping cart
 */
require_once __DIR__ . '/../config/config.php';

require_login();

$pdo = getDB();
$items = get_cart_items($pdo);
$totals = cart_totals($pdo, 'delivery');
$empty = empty($items);

$page_title = 'Your Cart';
$nav_active = 'cart';

require_once __DIR__ . '/../includes/customer_header.php';
?>

<h2 style="margin-bottom:1.6rem">Your Cart</h2>

<?php if ($empty): ?>
<div class="empty-state">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6h15l-1.7 8.5a2 2 0 0 1-2 1.5H8.6a2 2 0 0 1-2-1.6L4.4 3.8A1 1 0 0 0 3.4 3H1"/><circle cx="9" cy="20" r="1.6"/><circle cx="17" cy="20" r="1.6"/></svg>
  <h3>Your cart is empty</h3>
  <p>Add something delicious from the menu.</p>
  <a href="<?= url('/customer/menu.php') ?>" class="btn btn-terra" style="margin-top:1.2rem">Browse the Menu</a>
</div>
<?php else: ?>
<div class="cart-layout">
  <div>
    <?php foreach ($items as $it): ?>
    <div class="cart-line" data-item-id="<?= (int) $it['item_id'] ?>">
      <img src="<?= e(image_url($it['image'])) ?>" alt="<?= e($it['name']) ?>">
      <div>
        <h3><?= e($it['name']) ?></h3>
        <span class="chip terra" style="margin:0.3rem 0"><?= e($it['category_name'] ?? 'Dish') ?></span>
        <div style="margin-top:0.5rem">
          <span class="cl-price"><?= format_price($it['price']) ?></span>
          <span class="text-muted" style="font-size:0.85rem"> each</span>
        </div>
        <?php if (!$it['is_available']): ?>
        <span class="tag-off">No longer available — please remove</span>
        <?php endif; ?>
      </div>
      <div class="cl-right">
        <div class="qty-stepper" aria-label="Quantity">
          <button type="button" data-qty-dec aria-label="Decrease quantity">−</button>
          <span><?= (int) $it['quantity'] ?></span>
          <button type="button" data-qty-inc aria-label="Increase quantity">+</button>
        </div>
        <div class="cl-total"><?= format_price((float) $it['price'] * (int) $it['quantity']) ?></div>
        <button class="cl-remove" data-remove-id="<?= (int) $it['item_id'] ?>" type="button">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
          Remove
        </button>
      </div>
    </div>
    <?php endforeach; ?>

    <div style="display:flex; gap:1rem; flex-wrap:wrap; margin-top:1.4rem">
      <a href="<?= url('/customer/menu.php') ?>" class="btn btn-outline dark">← Continue Ordering</a>
      <button class="btn btn-ghost dark" id="clearCart" type="button">Clear Cart</button>
    </div>
  </div>

  <aside class="summary" aria-label="Order summary">
    <h3>Order Summary</h3>
    <div class="summary-row"><span>Items (<?= $totals['count'] ?>)</span><span><?= format_price($totals['subtotal']) ?></span></div>
    <div class="summary-row"><span>Delivery fee</span><span><?= $totals['delivery_fee'] > 0 ? format_price($totals['delivery_fee']) : 'Free' ?></span></div>
    <div class="summary-row"><span>Tax (<?= round((float) setting('tax_rate', TAX_RATE) * 100) ?>%)</span><span><?= format_price($totals['tax']) ?></span></div>
    <?php if ($totals['delivery_fee'] === 0 && $totals['subtotal'] > 0): ?>
    <div class="free-ship-note">You've unlocked free delivery!</div>
    <?php elseif ($totals['subtotal'] > 0): ?>
    <?php $remaining = (float) setting('free_delivery_min', FREE_DELIVERY_MIN) - $totals['subtotal']; ?>
    <div class="free-ship-note">Add <?= format_price($remaining) ?> more for free delivery</div>
    <?php endif; ?>
    <div class="summary-row total"><span>Total</span><span><?= format_price($totals['total']) ?></span></div>
    <a href="<?= url('/customer/checkout.php') ?>" class="btn btn-terra btn-wide" style="margin-top:1.4rem">Proceed to Checkout</a>
    <p class="hint text-muted" style="margin-top:1rem; font-size:0.82rem; text-align:center">
      Taxes calculated at checkout. No payment taken until the restaurant confirms.
    </p>
  </aside>
</div>
<?php endif; ?>

<?php
$page_scripts = ['cart.js'];
require_once __DIR__ . '/../includes/customer_footer.php';
