<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

if (!isLoggedIn()) {
    echo json_encode([]);
    exit;
}

$userOid    = toObjectId((string) $_SESSION['user_id']);
$matchStage = isAdmin() ? (object)[] : ['user_id' => $userOid];

$pipeline = [
    ['$match' => $matchStage],
    ['$lookup' => [
        'from'         => 'users',
        'localField'   => 'user_id',
        'foreignField' => '_id',
        'as'           => 'user',
    ]],
    ['$addFields' => ['username' => ['$arrayElemAt' => ['$user.username', 0]]]],
    ['$project'   => ['user' => 0]],
    ['$sort'      => ['created_at' => -1]],
];

$orders = mongoDocs($db->orders->aggregate($pipeline));
echo json_encode($orders);
