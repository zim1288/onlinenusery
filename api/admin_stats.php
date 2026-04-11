<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$stats = [];

$stats['total_plants'] = $db->plants->countDocuments();
$stats['total_orders'] = $db->orders->countDocuments();
$stats['total_users']  = $db->users->countDocuments(['role' => 'customer']);

// Total revenue (excluding cancelled orders)
$revResult = $db->orders->aggregate([
    ['$match' => ['status' => ['$ne' => 'cancelled']]],
    ['$group' => ['_id' => null, 'revenue' => ['$sum' => '$total_amount']]],
])->toArray();
$stats['total_revenue'] = round($revResult[0]['revenue'] ?? 0, 2);

// Recent 5 orders
$pipeline = [
    ['$lookup' => [
        'from'         => 'users',
        'localField'   => 'user_id',
        'foreignField' => '_id',
        'as'           => 'user',
    ]],
    ['$addFields' => ['username' => ['$arrayElemAt' => ['$user.username', 0]]]],
    ['$project'   => ['user' => 0, 'items' => 0]],
    ['$sort'      => ['created_at' => -1]],
    ['$limit'     => 5],
];
$stats['recent_orders'] = mongoDocs($db->orders->aggregate($pipeline));

// Low stock plants (stock < 5)
$stats['low_stock'] = mongoDocs(
    $db->plants->find(['stock' => ['$lt' => 5]], ['sort' => ['stock' => 1]])
);

echo json_encode($stats);
