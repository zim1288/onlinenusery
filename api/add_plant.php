<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$name        = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$price       = (float)($_POST['price'] ?? 0);
$category_id = (int)($_POST['category_id'] ?? 0);
$stock       = (int)($_POST['stock'] ?? 0);

if (empty($name) || $price <= 0) {
    echo json_encode(['success' => false, 'message' => 'Name and price are required.']);
    exit;
}

$image = 'default.jpg';
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Invalid image type. Allowed: jpg, png, gif, webp.']);
        exit;
    }
    $image = uniqid('plant_', true) . '.' . $ext;
    $dest  = __DIR__ . '/../uploads/' . $image;
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
        echo json_encode(['success' => false, 'message' => 'Failed to upload image.']);
        exit;
    }
}

$stmt = $conn->prepare(
    "INSERT INTO plants (name, description, price, image, category_id, stock) VALUES (?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param('ssdsii', $name, $description, $price, $image, $category_id, $stock);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Plant added successfully.', 'id' => $stmt->insert_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add plant.']);
}
$stmt->close();
