<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

if (!isLoggedIn()) {
    echo json_encode([]);
    exit;
}

$userId = (int)$_SESSION['user_id'];

if (isAdmin()) {
    $stmt = $conn->prepare("
        SELECT o.id, o.total_amount, o.status, o.created_at,
               u.username
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        ORDER BY o.created_at DESC
    ");
    $stmt->execute();
} else {
    $stmt = $conn->prepare("
        SELECT o.id, o.total_amount, o.status, o.created_at,
               u.username
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.user_id = ?
        ORDER BY o.created_at DESC
    ");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
}

$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Attach order items
foreach ($orders as &$order) {
    $stmt = $conn->prepare("
        SELECT oi.quantity, oi.price, p.name AS plant_name, p.image
        FROM order_items oi
        LEFT JOIN plants p ON oi.plant_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->bind_param('i', $order['id']);
    $stmt->execute();
    $order['items'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

echo json_encode($orders);
