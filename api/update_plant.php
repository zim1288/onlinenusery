<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;
    $name = $_POST['name'] ?? '';
    $price = $_POST['price'] ?? 0;

    if ($id && $name && is_numeric($price)) {
        $stmt = $conn->prepare("UPDATE plants SET name = ?, price = ? WHERE id = ?");
        $stmt->bind_param("sdi", $name, $price, $id);
        if ($stmt->execute()) {
            echo "Plant updated successfully!";
        } else {
            echo "Error updating plant.";
        }
        $stmt->close();
    } else {
        echo "Invalid input.";
    }
}
$conn->close();
?>