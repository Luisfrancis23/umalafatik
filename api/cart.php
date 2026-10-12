<?php
/**
 * API — cart actions (JSON)
 * POST: action=add|update|remove|summary
 */
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

function out(array $data): void
{
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    out(['success' => false, 'message' => 'Method not allowed.']);
}

if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    out(['success' => false, 'message' => 'Invalid session token. Please reload the page.']);
}

$action = str_in($_POST['action'] ?? '', 20);
$pdo = getDB();

try {
    switch ($action) {
        case 'add':
            if (!is_logged_in()) {
                out(['success' => false, 'login' => true, 'message' => 'Please sign in to order.']);
            }
            $id = int_in($_POST['menu_item_id'] ?? 0, 1, PHP_INT_MAX);
            $qty = int_in($_POST['quantity'] ?? 1, 1, 20);
            add_to_cart($pdo, $id, $qty);
            out([
                'success' => true,
                'count'   => cart_count($pdo),
                'message' => 'Added to cart.',
            ]);
            break;

        case 'update':
            $id = int_in($_POST['menu_item_id'] ?? 0, 1, PHP_INT_MAX);
            $qty = int_in($_POST['quantity'] ?? 0, 0, 20);
            if ($qty === 0) {
                remove_from_cart($pdo, $id);
            } else {
                set_cart_qty($pdo, $id, $qty);
            }
            out([
                'success' => true,
                'count'   => cart_count($pdo),
                'message' => 'Cart updated.',
            ]);
            break;

        case 'remove':
            $id = int_in($_POST['menu_item_id'] ?? 0, 1, PHP_INT_MAX);
            remove_from_cart($pdo, $id);
            out(['success' => true, 'count' => cart_count($pdo), 'message' => 'Item removed.']);
            break;

        case 'summary':
        default:
            $items = get_cart_items($pdo);
            $totals = cart_totals($pdo, 'delivery');
            out([
                'success' => true,
                'count'   => $totals['count'],
                'items'   => array_map(function ($it) {
                    return [
                        'id'       => (int) $it['item_id'],
                        'name'     => $it['name'],
                        'price'    => (float) $it['price'],
                        'quantity' => (int) $it['quantity'],
                    ];
                }, $items),
                'totals'  => $totals,
            ]);
    }
} catch (RuntimeException $e) {
    http_response_code(400);
    out(['success' => false, 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    out(['success' => false, 'message' => 'Something went wrong. Please try again.']);
}
