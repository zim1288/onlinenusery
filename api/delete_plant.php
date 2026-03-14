<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid plant ID.']);
    exit;
}

// Get image to remove
$stmt = $conn->prepare("SELECT image FROM plants WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$plant = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$plant) {
    echo json_encode(['success' => false, 'message' => 'Plant not found.']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM plants WHERE id = ?");
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    if ($plant['image'] && $plant['image'] !== 'default.jpg') {
        @unlink(__DIR__ . '/../uploads/' . $plant['image']);
    }
    echo json_encode(['success' => true, 'message' => 'Plant deleted successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete plant.']);
}
$stmt->close();
