<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$orderId = (int)($_POST['order_id'] ?? 0);
$status  = $_POST['status'] ?? '';
$allowed = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

if ($orderId <= 0 || !in_array($status, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID or status.']);
    exit;
}

$stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
$stmt->bind_param('si', $status, $orderId);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Order status updated.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update order status.']);
}
$stmt->close();
