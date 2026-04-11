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
$category_id = $_POST['category_id'] ?? '';
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

$doc = [
    'name'        => $name,
    'description' => $description,
    'price'       => $price,
    'image'       => $image,
    'stock'       => $stock,
    'created_at'  => new MongoDB\BSON\UTCDateTime(),
];

$catOid = toObjectId($category_id);
if ($catOid) {
    $doc['category_id'] = $catOid;
}

$result = $db->plants->insertOne($doc);
echo json_encode(['success' => true, 'message' => 'Plant added successfully.', 'id' => (string) $result->getInsertedId()]);
