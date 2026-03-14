<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $plantId = (int)($_GET['plant_id'] ?? 0);
    if ($plantId <= 0) {
        echo json_encode([]);
        exit;
    }
    $stmt = $conn->prepare("
        SELECT r.id, r.rating, r.comment, r.created_at, u.username
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        WHERE r.plant_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->bind_param('i', $plantId);
    $stmt->execute();
    $reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    echo json_encode($reviews);
    exit;
}

if ($method === 'POST') {
    $action  = $_POST['action'] ?? '';
    if ($action === 'add') {
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Please log in to submit a review.', 'redirect' => '/login.php']);
            exit;
        }
        $userId  = (int)$_SESSION['user_id'];
        $plantId = (int)($_POST['plant_id'] ?? 0);
        $rating  = (int)($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');

        if ($plantId <= 0 || $rating < 1 || $rating > 5) {
            echo json_encode(['success' => false, 'message' => 'Invalid plant or rating.']);
            exit;
        }

        // Check existing review
        $stmt = $conn->prepare("SELECT id FROM reviews WHERE user_id = ? AND plant_id = ?");
        $stmt->bind_param('ii', $userId, $plantId);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existing) {
            echo json_encode(['success' => false, 'message' => 'You have already reviewed this plant.']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO reviews (user_id, plant_id, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('iiis', $userId, $plantId, $rating, $comment);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Review submitted successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to submit review.']);
        }
        $stmt->close();
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid request.']);
