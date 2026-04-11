<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$orderId  = $_POST['order_id'] ?? '';
$status   = $_POST['status'] ?? '';
$allowed  = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
$orderOid = toObjectId($orderId);

if (!$orderOid || !in_array($status, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID or status.']);
    exit;
}

$result = $db->orders->updateOne(
    ['_id' => $orderOid],
    ['$set' => ['status' => $status]]
);

if ($result->getMatchedCount() > 0) {
    echo json_encode(['success' => true, 'message' => 'Order status updated.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Order not found.']);
}
