<?php
/**
 * API — order status (JSON)
 * GET: order_id — accessible only by the order owner or admins
 */
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = getDB();
$orderId = int_in($_GET['order_id'] ?? 0, 1, PHP_INT_MAX);

if ($orderId < 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing order id.']);
    exit;
}

// Authorization: owner or admin
$userId = $_SESSION['user_id'] ?? null;
$adminId = $_SESSION['admin_id'] ?? null;

if ($userId === null && $adminId === null) {
    http_response_code(401);
    echo json_encode(['success' => false, 'login' => true, 'message' => 'Please sign in.']);
    exit;
}

$stmt = $pdo->prepare('SELECT id, status, order_number, updated_at FROM orders WHERE id = ?');
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Order not found.']);
    exit;
}

if ($adminId === null && (int) $order['id'] > 0) {
    $owner = $pdo->prepare('SELECT user_id FROM orders WHERE id = ?');
    $owner->execute([$orderId]);
    if ((int) $owner->fetchColumn() !== (int) $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied.']);
        exit;
    }
}

echo json_encode([
    'success' => true,
    'order_id' => (int) $order['id'],
    'order_number' => $order['order_number'],
    'status' => $order['status'],
    'updated_at' => $order['updated_at'],
]);
