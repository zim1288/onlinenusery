<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$id  = $_POST['id'] ?? '';
$oid = toObjectId($id);
if (!$oid) {
    echo json_encode(['success' => false, 'message' => 'Invalid plant ID.']);
    exit;
}

$plant = $db->plants->findOne(['_id' => $oid]);
if (!$plant) {
    echo json_encode(['success' => false, 'message' => 'Plant not found.']);
    exit;
}

$db->plants->deleteOne(['_id' => $oid]);

// Remove orphaned reviews and cart entries that reference this plant
$db->reviews->deleteMany(['plant_id' => $oid]);
$db->cart->deleteMany(['plant_id' => $oid]);

if (isset($plant['image']) && $plant['image'] !== 'default.jpg') {
    @unlink(__DIR__ . '/../uploads/' . $plant['image']);
}
echo json_encode(['success' => true, 'message' => 'Plant deleted successfully.']);
