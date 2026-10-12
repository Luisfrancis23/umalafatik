<?php
/**
 * Uma Lafatik — shopping cart (database-backed, guest + customer)
 */
declare(strict_types=1);

/**
 * Get or create the cart for the current visitor.
 * Logged-in users get a cart tied to their account; guests get one tied to the session.
 */
function get_or_create_cart(PDO $pdo): array
{
    $userId = $_SESSION['user_id'] ?? null;
    $sessionId = session_id();

    if ($userId !== null) {
        $stmt = $pdo->prepare('SELECT * FROM carts WHERE user_id = ? LIMIT 1');
        $stmt->execute([(int) $userId]);
        $cart = $stmt->fetch();
        if ($cart) {
            return $cart;
        }
        $stmt = $pdo->prepare('INSERT INTO carts (user_id, session_id) VALUES (?, NULL)');
        $stmt->execute([(int) $userId]);
    } else {
        $stmt = $pdo->prepare('SELECT * FROM carts WHERE session_id = ? AND user_id IS NULL LIMIT 1');
        $stmt->execute([$sessionId]);
        $cart = $stmt->fetch();
        if ($cart) {
            return $cart;
        }
        $stmt = $pdo->prepare('INSERT INTO carts (user_id, session_id) VALUES (NULL, ?)');
        $stmt->execute([$sessionId]);
    }

    return [
        'id'         => (int) $pdo->lastInsertId(),
        'user_id'    => $userId !== null ? (int) $userId : null,
        'session_id' => $userId !== null ? null : $sessionId,
    ];
}

/** Merge a guest cart into the customer's cart (called on login). */
function merge_guest_cart(PDO $pdo, int $userId): void
{
    $sessionId = session_id();

    $guest = $pdo->prepare('SELECT * FROM carts WHERE session_id = ? AND user_id IS NULL LIMIT 1');
    $guest->execute([$sessionId]);
    $guestCart = $guest->fetch();
    if (!$guestCart) {
        return;
    }

    $userCart = get_or_create_cart($pdo);

    $items = $pdo->prepare('SELECT * FROM cart_items WHERE cart_id = ?');
    $items->execute([(int) $guestCart['id']]);

    foreach ($items->fetchAll() as $item) {
        $exists = $pdo->prepare(
            'SELECT quantity FROM cart_items WHERE cart_id = ? AND menu_item_id = ? LIMIT 1'
        );
        $exists->execute([(int) $userCart['id'], (int) $item['menu_item_id']]);
        $row = $exists->fetch();
        if ($row) {
            $upd = $pdo->prepare('UPDATE cart_items SET quantity = quantity + ?, updated_at = NOW() WHERE cart_id = ? AND menu_item_id = ?');
            $upd->execute([(int) $item['quantity'], (int) $userCart['id'], (int) $item['menu_item_id']]);
        } else {
            $ins = $pdo->prepare('INSERT INTO cart_items (cart_id, menu_item_id, quantity) VALUES (?, ?, ?)');
            $ins->execute([(int) $userCart['id'], (int) $item['menu_item_id'], (int) $item['quantity']]);
        }
    }

    $del = $pdo->prepare('DELETE FROM cart_items WHERE cart_id = ?');
    $del->execute([(int) $guestCart['id']]);
    $delCart = $pdo->prepare('DELETE FROM carts WHERE id = ?');
    $delCart->execute([(int) $guestCart['id']]);
}

/**
 * Add a menu item to the current cart.
 * @throws RuntimeException when the item is unavailable
 */
function add_to_cart(PDO $pdo, int $menuItemId, int $qty = 1): void
{
    $qty = max(1, min(20, $qty));

    $stmt = $pdo->prepare('SELECT id, price, is_available FROM menu_items WHERE id = ? LIMIT 1');
    $stmt->execute([$menuItemId]);
    $item = $stmt->fetch();
    if (!$item) {
        throw new RuntimeException('This dish is no longer available.');
    }
    if (!$item['is_available']) {
        throw new RuntimeException('Sorry — this dish is currently unavailable.');
    }

    $cart = get_or_create_cart($pdo);

    $existing = $pdo->prepare(
        'SELECT quantity FROM cart_items WHERE cart_id = ? AND menu_item_id = ? LIMIT 1'
    );
    $existing->execute([(int) $cart['id'], $menuItemId]);
    $row = $existing->fetch();

    if ($row) {
        $newQty = min(20, (int) $row['quantity'] + $qty);
        $upd = $pdo->prepare('UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE cart_id = ? AND menu_item_id = ?');
        $upd->execute([$newQty, (int) $cart['id'], $menuItemId]);
    } else {
        $ins = $pdo->prepare('INSERT INTO cart_items (cart_id, menu_item_id, quantity) VALUES (?, ?, ?)');
        $ins->execute([(int) $cart['id'], $menuItemId, $qty]);
    }
}

function set_cart_qty(PDO $pdo, int $menuItemId, int $qty): bool
{
    $cart = get_or_create_cart($pdo);
    if ($qty <= 0) {
        return remove_from_cart($pdo, $menuItemId);
    }
    $qty = min(20, $qty);
    $upd = $pdo->prepare('UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE cart_id = ? AND menu_item_id = ?');
    $upd->execute([$qty, (int) $cart['id'], $menuItemId]);
    return $upd->rowCount() > 0;
}

function remove_from_cart(PDO $pdo, int $menuItemId): bool
{
    $cart = get_or_create_cart($pdo);
    $del = $pdo->prepare('DELETE FROM cart_items WHERE cart_id = ? AND menu_item_id = ?');
    $del->execute([(int) $cart['id'], $menuItemId]);
    return $del->rowCount() > 0;
}

function clear_cart(PDO $pdo): void
{
    $cart = get_or_create_cart($pdo);
    $del = $pdo->prepare('DELETE FROM cart_items WHERE cart_id = ?');
    $del->execute([(int) $cart['id']]);
}

/**
 * Full cart with item details (name, price, image, category).
 * Removes entries whose menu item has been deleted.
 *
 * @return array<int, array<string, mixed>>
 */
function get_cart_items(PDO $pdo): array
{
    $cart = get_or_create_cart($pdo);

    $stmt = $pdo->prepare(
        'SELECT ci.id AS line_id, ci.quantity, mi.id AS item_id, mi.name, mi.price, mi.image, mi.is_available,
                c.id AS category_id, c.name AS category_name
         FROM cart_items ci
         JOIN menu_items mi ON mi.id = ci.menu_item_id
         LEFT JOIN categories c ON c.id = mi.category_id
         WHERE ci.cart_id = ?
         ORDER BY ci.updated_at DESC'
    );
    $stmt->execute([(int) $cart['id']]);
    return $stmt->fetchAll();
}

function cart_count(PDO $pdo): int
{
    $cart = get_or_create_cart($pdo);
    $stmt = $pdo->prepare('SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE cart_id = ?');
    $stmt->execute([(int) $cart['id']]);
    return (int) $stmt->fetchColumn();
}

/** @return array{subtotal:float, delivery_fee:float, tax:float, total:float, count:int} */
function cart_totals(PDO $pdo, string $deliveryType = 'delivery'): array
{
    $items = get_cart_items($pdo);
    $subtotal = 0.0;
    $count = 0;
    foreach ($items as $it) {
        $subtotal += (float) $it['price'] * (int) $it['quantity'];
        $count    += (int) $it['quantity'];
    }
    $subtotal = round($subtotal, 2);

    $deliveryFee = 0.0;
    if ($deliveryType === 'delivery' && $subtotal > 0 && $subtotal < (float) setting('free_delivery_min', FREE_DELIVERY_MIN)) {
        $deliveryFee = (float) setting('delivery_fee', DELIVERY_FEE);
    }
    $tax = round($subtotal * (float) setting('tax_rate', TAX_RATE), 2);
    $total = round($subtotal + $deliveryFee + $tax, 2);

    return [
        'subtotal'     => $subtotal,
        'delivery_fee' => $deliveryFee,
        'tax'          => $tax,
        'total'        => $total,
        'count'        => $count,
    ];
}
