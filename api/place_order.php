<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please log in to continue.', 'redirect' => '/login.php']);
    exit;
}

$userOid = toObjectId((string) $_SESSION['user_id']);

// Get cart items with plant details via aggregation
$pipeline = [
    ['$match' => ['user_id' => $userOid]],
    ['$lookup' => [
        'from'         => 'plants',
        'localField'   => 'plant_id',
        'foreignField' => '_id',
        'as'           => 'plant',
    ]],
    ['$unwind' => '$plant'],
];

$cartItems = [];
foreach ($db->cart->aggregate($pipeline) as $doc) {
    $cartItems[] = (array) $doc;
}

if (empty($cartItems)) {
    echo json_encode(['success' => false, 'message' => 'Your cart is empty.']);
    exit;
}

// Validate stock
foreach ($cartItems as $item) {
    $plant = (array) $item['plant'];
    if ($item['quantity'] > ($plant['stock'] ?? 0)) {
        echo json_encode(['success' => false, 'message' => "Insufficient stock for {$plant['name']}."]);
        exit;
    }
}

$total = array_sum(array_map(fn($i) => ((array) $i['plant'])['price'] * $i['quantity'], $cartItems));

$orderItems = array_map(fn($i) => [
    'plant_id'   => $i['plant_id'],
    'plant_name' => ((array) $i['plant'])['name'],
    'quantity'   => $i['quantity'],
    'price'      => ((array) $i['plant'])['price'],
], $cartItems);

try {
    $result  = $db->orders->insertOne([
        'user_id'      => $userOid,
        'total_amount' => round($total, 2),
        'status'       => 'pending',
        'items'        => $orderItems,
        'created_at'   => new MongoDB\BSON\UTCDateTime(),
    ]);
    $orderId = (string) $result->getInsertedId();

    // Decrement stock for each plant
    foreach ($cartItems as $item) {
        $db->plants->updateOne(
            ['_id' => $item['plant_id']],
            ['$inc' => ['stock' => -$item['quantity']]]
        );
    }

    // Clear cart
    $db->cart->deleteMany(['user_id' => $userOid]);

    echo json_encode(['success' => true, 'message' => 'Order placed successfully.', 'order_id' => $orderId]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to place order. Please try again.']);
}
