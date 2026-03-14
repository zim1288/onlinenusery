<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please log in to continue.', 'redirect' => '/login.php']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

// Get cart items
$stmt = $conn->prepare("
    SELECT c.plant_id, c.quantity, p.price, p.stock, p.name
    FROM cart c
    JOIN plants p ON c.plant_id = p.id
    WHERE c.user_id = ?
");
$stmt->bind_param('i', $userId);
$stmt->execute();
$cartItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($cartItems)) {
    echo json_encode(['success' => false, 'message' => 'Your cart is empty.']);
    exit;
}

// Validate stock
foreach ($cartItems as $item) {
    if ($item['quantity'] > $item['stock']) {
        echo json_encode(['success' => false, 'message' => "Insufficient stock for {$item['name']}."]);
        exit;
    }
}

$total = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cartItems));

$conn->begin_transaction();
try {
    // Create order
    $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'pending')");
    $stmt->bind_param('id', $userId, $total);
    $stmt->execute();
    $orderId = $stmt->insert_id;
    $stmt->close();

    // Insert order items and update stock
    $stmtItem  = $conn->prepare("INSERT INTO order_items (order_id, plant_id, quantity, price) VALUES (?, ?, ?, ?)");
    $stmtStock = $conn->prepare("UPDATE plants SET stock = stock - ? WHERE id = ?");

    foreach ($cartItems as $item) {
        $stmtItem->bind_param('iiid', $orderId, $item['plant_id'], $item['quantity'], $item['price']);
        $stmtItem->execute();
        $stmtStock->bind_param('ii', $item['quantity'], $item['plant_id']);
        $stmtStock->execute();
    }
    $stmtItem->close();
    $stmtStock->close();

    // Clear cart
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Order placed successfully.', 'order_id' => $orderId]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Failed to place order. Please try again.']);
}
