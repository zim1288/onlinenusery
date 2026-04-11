<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please log in to continue.', 'redirect' => '/login.php']);
    exit;
}

$action  = $_POST['action'] ?? $_GET['action'] ?? '';
$userOid = toObjectId((string) $_SESSION['user_id']);

if ($action === 'add') {
    $plantId  = $_POST['plant_id'] ?? '';
    $quantity = (int)($_POST['quantity'] ?? 1);
    $plantOid = toObjectId($plantId);

    if (!$plantOid || $quantity < 1) {
        echo json_encode(['success' => false, 'message' => 'Invalid plant or quantity.']);
        exit;
    }
    // Check stock
    $plant = $db->plants->findOne(['_id' => $plantOid]);
    if (!$plant || ($plant['stock'] ?? 0) < 1) {
        echo json_encode(['success' => false, 'message' => 'Plant is out of stock.']);
        exit;
    }
    // Enforce stock cap: current cart quantity + requested must not exceed available stock
    $existing = $db->cart->findOne(['user_id' => $userOid, 'plant_id' => $plantOid]);
    $currentQty = (int)($existing['quantity'] ?? 0);
    if ($currentQty + $quantity > ($plant['stock'] ?? 0)) {
        echo json_encode(['success' => false, 'message' => 'Cannot add more than available stock.']);
        exit;
    }
    $db->cart->updateOne(
        ['user_id' => $userOid, 'plant_id' => $plantOid],
        ['$inc' => ['quantity' => $quantity]],
        ['upsert' => true]
    );
    echo json_encode(['success' => true, 'message' => 'Item added to cart.']);
    exit;
}

if ($action === 'remove') {
    $plantId  = $_POST['plant_id'] ?? '';
    $plantOid = toObjectId($plantId);
    if ($plantOid) {
        $db->cart->deleteOne(['user_id' => $userOid, 'plant_id' => $plantOid]);
    }
    echo json_encode(['success' => true, 'message' => 'Item removed from cart.']);
    exit;
}

if ($action === 'update') {
    $plantId  = $_POST['plant_id'] ?? '';
    $quantity = (int)($_POST['quantity'] ?? 1);
    $plantOid = toObjectId($plantId);
    if (!$plantOid) {
        echo json_encode(['success' => false, 'message' => 'Invalid plant ID.']);
        exit;
    }
    if ($quantity < 1) {
        $db->cart->deleteOne(['user_id' => $userOid, 'plant_id' => $plantOid]);
    } else {
        $db->cart->updateOne(
            ['user_id' => $userOid, 'plant_id' => $plantOid],
            ['$set' => ['quantity' => $quantity]]
        );
    }
    echo json_encode(['success' => true, 'message' => 'Cart updated.']);
    exit;
}

if ($action === 'get') {
    $pipeline = [
        ['$match' => ['user_id' => $userOid]],
        ['$lookup' => [
            'from'         => 'plants',
            'localField'   => 'plant_id',
            'foreignField' => '_id',
            'as'           => 'plant',
        ]],
        ['$unwind' => '$plant'],
        ['$project' => [
            'plant_id' => 1,
            'quantity' => 1,
            'name'     => '$plant.name',
            'price'    => '$plant.price',
            'image'    => '$plant.image',
            'stock'    => '$plant.stock',
        ]],
    ];

    $items = [];
    $total = 0;
    foreach ($db->cart->aggregate($pipeline) as $doc) {
        $item    = mongoDoc((array) $doc);
        $items[] = $item;
        $total  += $item['price'] * $item['quantity'];
    }
    echo json_encode(['success' => true, 'items' => $items, 'total' => round($total, 2)]);
    exit;
}

if ($action === 'clear') {
    $db->cart->deleteMany(['user_id' => $userOid]);
    echo json_encode(['success' => true, 'message' => 'Cart cleared.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);
