<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

$categories = mongoDocs($db->categories->find([], ['sort' => ['name' => 1]]));
echo json_encode($categories);
