<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please log in to continue.', 'redirect' => '/login.php']);
    exit;
}

$action  = $_POST['action'] ?? $_GET['action'] ?? '';
$userId  = (int)$_SESSION['user_id'];

if ($action === 'add') {
    $plantId  = (int)($_POST['plant_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);
    if ($plantId <= 0 || $quantity < 1) {
        echo json_encode(['success' => false, 'message' => 'Invalid plant or quantity.']);
        exit;
    }
    // Check stock
    $stmt = $conn->prepare("SELECT stock FROM plants WHERE id = ?");
    $stmt->bind_param('i', $plantId);
    $stmt->execute();
    $plant = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$plant || $plant['stock'] < 1) {
        echo json_encode(['success' => false, 'message' => 'Plant is out of stock.']);
        exit;
    }
    $stmt = $conn->prepare(
        "INSERT INTO cart (user_id, plant_id, quantity) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)"
    );
    $stmt->bind_param('iii', $userId, $plantId, $quantity);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true, 'message' => 'Item added to cart.']);
    exit;
}

if ($action === 'remove') {
    $plantId = (int)($_POST['plant_id'] ?? 0);
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND plant_id = ?");
    $stmt->bind_param('ii', $userId, $plantId);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true, 'message' => 'Item removed from cart.']);
    exit;
}

if ($action === 'update') {
    $plantId  = (int)($_POST['plant_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);
    if ($quantity < 1) {
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND plant_id = ?");
        $stmt->bind_param('ii', $userId, $plantId);
    } else {
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND plant_id = ?");
        $stmt->bind_param('iii', $quantity, $userId, $plantId);
    }
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true, 'message' => 'Cart updated.']);
    exit;
}

if ($action === 'get') {
    $stmt = $conn->prepare("
        SELECT c.plant_id, c.quantity, p.name, p.price, p.image, p.stock
        FROM cart c
        JOIN plants p ON c.plant_id = p.id
        WHERE c.user_id = ?
        ORDER BY c.id ASC
    ");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $items  = [];
    $total  = 0;
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
        $total  += $row['price'] * $row['quantity'];
    }
    $stmt->close();
    echo json_encode(['success' => true, 'items' => $items, 'total' => round($total, 2)]);
    exit;
}

if ($action === 'clear') {
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true, 'message' => 'Cart cleared.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);
