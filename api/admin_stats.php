<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$stats = [];

// Counts
$row = $conn->query("SELECT COUNT(*) as cnt FROM plants")->fetch_assoc();
$stats['total_plants'] = (int)$row['cnt'];

$row = $conn->query("SELECT COUNT(*) as cnt FROM orders")->fetch_assoc();
$stats['total_orders'] = (int)$row['cnt'];

$row = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role = 'customer'")->fetch_assoc();
$stats['total_users'] = (int)$row['cnt'];

$row = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE status != 'cancelled'")->fetch_assoc();
$stats['total_revenue'] = round((float)$row['revenue'], 2);

// Recent 5 orders
$result = $conn->query("
    SELECT o.id, o.total_amount, o.status, o.created_at, u.username
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
    LIMIT 5
");
$stats['recent_orders'] = $result->fetch_all(MYSQLI_ASSOC);

// Low stock plants (stock < 5)
$result = $conn->query("
    SELECT id, name, stock, price FROM plants WHERE stock < 5 ORDER BY stock ASC
");
$stats['low_stock'] = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($stats);
