<?php
/**
 * API — notifications (JSON)
 * GET:  list unread + recent
 * POST: mark read (all or one)
 */
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'login' => true, 'message' => 'Please sign in.']);
    exit;
}

$pdo = getDB();
$user = current_customer();
$userId = (int) $user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid token.']);
        exit;
    }
    $notifId = int_in($_POST['notification_id'] ?? 0, 0, PHP_INT_MAX);
    if ($notifId > 0) {
        $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
        $stmt->execute([$notifId, $userId]);
    } else {
        $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?');
        $stmt->execute([$userId]);
    }
    echo json_encode(['success' => true, 'unread' => unread_notifications($pdo, $userId)]);
    exit;
}

// GET — recent notifications
$limit = int_in($_GET['limit'] ?? 10, 1, 50);
$stmt = $pdo->prepare(
    'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?'
);
$stmt->execute([$userId, $limit]);
$rows = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'unread'  => unread_notifications($pdo, $userId),
    'items'   => array_map(function ($n) {
        return [
            'id'      => (int) $n['id'],
            'type'    => $n['type'],
            'message' => $n['message'],
            'is_read' => (bool) $n['is_read'],
            'created' => $n['created_at'],
            'order_id' => $n['order_id'] ? (int) $n['order_id'] : null,
        ];
    }, $rows),
]);
