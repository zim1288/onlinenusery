<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $price = $_POST['price'] ?? 0;

    if ($name && is_numeric($price)) {
        $stmt = $conn->prepare("INSERT INTO plants (name, price) VALUES (?, ?)");
        $stmt->bind_param("sd", $name, $price);
        if ($stmt->execute()) {
            echo "Plant added successfully!";
        } else {
            echo "Error adding plant.";
        }
        $stmt->close();
    } else {
        echo "Invalid input.";
    }
}
$conn->close();
?>