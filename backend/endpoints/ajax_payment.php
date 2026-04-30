<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/backend/includes/init.php';

header('Content-Type: application/json');

if (!is_post()) {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_login();

$input = json_decode(file_get_contents('php://input'), true);
$orderId = (int)($input['order_id'] ?? 0);
$method = trim((string)($input['method'] ?? ''));
$status = trim((string)($input['status'] ?? ''));

if ($orderId <= 0 || !in_array($method, ['telebirr', 'chapa'], true) || $status !== 'paid') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Verify order belongs to current user and is awaiting payment
$stmt = db()->prepare('SELECT status FROM orders WHERE order_id = ? AND user_id = ?');
$stmt->execute([$orderId, (int)current_user()['user_id']]);
$order = $stmt->fetch();

if (!$order || $order['status'] !== 'Pending Payment') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Order not found or already processed']);
    exit;
}

// Mark as paid (we treat "paid" as a demo confirmation for now)
$stmt = db()->prepare('UPDATE orders SET status = ?, payment_method = ? WHERE order_id = ?');
$success = $stmt->execute(['Pending', $method, $orderId]);

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Payment processed successfully']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update order status']);
}
?>
