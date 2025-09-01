<?php
// Simple order handler for nursery
require 'api/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plant = htmlspecialchars($_POST['plant'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 0);
    $customer = htmlspecialchars($_POST['customer'] ?? '');

    if ($plant && $quantity > 0 && $customer) {
        // Save the order to the database
        $stmt = $conn->prepare("INSERT INTO orders (customer_name, plant_name, quantity) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $customer, $plant, $quantity);
        
        if ($stmt->execute()) {
            echo "Thank you, $customer! Your order for $quantity $plant(s) has been received.";
        } else {
            echo "There was an error placing your order.";
        }
        $stmt->close();
    } else {
        echo "Invalid order data. Please check your input.";
    }
} else {
    echo "Invalid request.";
}

$conn->close();
?>