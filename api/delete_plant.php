<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;

    if ($id) {
        $stmt = $conn->prepare("DELETE FROM plants WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo "Plant deleted successfully!";
        } else {
            echo "Error deleting plant.";
        }
        $stmt->close();
    } else {
        echo "Invalid ID.";
    }
}
$conn->close();
?>