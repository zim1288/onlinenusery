<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$id          = (int)($_POST['id'] ?? 0);
$name        = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$price       = (float)($_POST['price'] ?? 0);
$category_id = (int)($_POST['category_id'] ?? 0);
$stock       = (int)($_POST['stock'] ?? 0);

if ($id <= 0 || empty($name) || $price <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID, name and price are required.']);
    exit;
}

// Fetch existing image
$stmt = $conn->prepare("SELECT image FROM plants WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$existing) {
    echo json_encode(['success' => false, 'message' => 'Plant not found.']);
    exit;
}

$image = $existing['image'];

if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Invalid image type.']);
        exit;
    }
    $newImage = uniqid('plant_', true) . '.' . $ext;
    $dest     = __DIR__ . '/../uploads/' . $newImage;
    if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
        // Remove old image if not default
        if ($image !== 'default.jpg') {
            @unlink(__DIR__ . '/../uploads/' . $image);
        }
        $image = $newImage;
    }
}

$stmt = $conn->prepare(
    "UPDATE plants SET name=?, description=?, price=?, image=?, category_id=?, stock=? WHERE id=?"
);
$stmt->bind_param('ssdsiii', $name, $description, $price, $image, $category_id, $stock, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Plant updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update plant.']);
}
$stmt->close();
