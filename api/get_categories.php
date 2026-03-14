<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

$result = $conn->query("SELECT id, name, description FROM categories ORDER BY name ASC");
$categories = [];
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}
echo json_encode($categories);
