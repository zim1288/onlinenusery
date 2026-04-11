<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $plantId  = $_GET['plant_id'] ?? '';
    $plantOid = toObjectId($plantId);
    if (!$plantOid) {
        echo json_encode([]);
        exit;
    }

    $pipeline = [
        ['$match' => ['plant_id' => $plantOid]],
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

    echo json_encode(mongoDocs($db->reviews->aggregate($pipeline)));
    exit;
}

if ($method === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Please log in to submit a review.', 'redirect' => '/login.php']);
            exit;
        }
        $userOid  = toObjectId((string) $_SESSION['user_id']);
        $plantId  = $_POST['plant_id'] ?? '';
        $rating   = (int)($_POST['rating'] ?? 0);
        $comment  = trim($_POST['comment'] ?? '');
        $plantOid = toObjectId($plantId);

        if (!$plantOid || $rating < 1 || $rating > 5) {
            echo json_encode(['success' => false, 'message' => 'Invalid plant or rating.']);
            exit;
        }

        // Check for existing review
        $existing = $db->reviews->findOne(['user_id' => $userOid, 'plant_id' => $plantOid]);
        if ($existing) {
            echo json_encode(['success' => false, 'message' => 'You have already reviewed this plant.']);
            exit;
        }

        $db->reviews->insertOne([
            'user_id'    => $userOid,
            'plant_id'   => $plantOid,
            'rating'     => $rating,
            'comment'    => $comment,
            'created_at' => new MongoDB\BSON\UTCDateTime(),
        ]);
        echo json_encode(['success' => true, 'message' => 'Review submitted successfully.']);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid request.']);
