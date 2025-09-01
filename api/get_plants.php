<?php
header('Content-Type: application/json');
require 'db.php';

$result = $conn->query("SELECT * FROM plants ORDER BY name");
$plants = [];
while ($row = $result->fetch_assoc()) {
    $plants[] = $row;
}

echo json_encode($plants);

$conn->close();
?>