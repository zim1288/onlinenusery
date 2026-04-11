<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$id          = $_POST['id'] ?? '';
$name        = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$price       = (float)($_POST['price'] ?? 0);
$category_id = $_POST['category_id'] ?? '';
$stock       = (int)($_POST['stock'] ?? 0);

$oid = toObjectId($id);
if (!$oid || empty($name) || $price <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID, name and price are required.']);
    exit;
}

// Fetch existing plant
$existing = $db->plants->findOne(['_id' => $oid]);
if (!$existing) {
    echo json_encode(['success' => false, 'message' => 'Plant not found.']);
    exit;
}

$image = $existing['image'] ?? 'default.jpg';

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
        if ($image !== 'default.jpg') {
            @unlink(__DIR__ . '/../uploads/' . $image);
        }
        $image = $newImage;
    }
}

$update = [
    'name'        => $name,
    'description' => $description,
    'price'       => $price,
    'image'       => $image,
    'stock'       => $stock,
];

$catOid = toObjectId($category_id);
$update['category_id'] = $catOid ?: null;

$db->plants->updateOne(['_id' => $oid], ['$set' => $update]);
echo json_encode(['success' => true, 'message' => 'Plant updated successfully.']);
