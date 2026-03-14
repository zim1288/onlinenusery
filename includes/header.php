<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/auth_check.php';

$cartCount = 0;
if (isLoggedIn()) {
    require_once __DIR__ . '/../api/db.php';
    $stmt = $conn->prepare("SELECT COALESCE(SUM(quantity), 0) as total FROM cart WHERE user_id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $cartCount = (int)($result['total'] ?? 0);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Green Nursery') ?></title>
    <link rel="stylesheet" href="/styles.css">
</head>
<body>
<nav class="navbar">
    <div class="container nav-container">
        <a href="/index.php" class="nav-logo">🌿 Green Nursery</a>
        <div class="nav-links">
            <a href="/index.php">Home</a>
            <a href="/index.php">Plants</a>
            <a href="/cart.php" class="cart-link">
                🛒 Cart
                <?php if ($cartCount > 0): ?>
                    <span class="cart-badge"><?= $cartCount ?></span>
                <?php endif; ?>
            </a>
            <a href="/orders.php">My Orders</a>
            <?php if (isAdmin()): ?>
                <a href="/admin/index.php" class="admin-link">⚙ Admin Dashboard</a>
            <?php endif; ?>
            <?php if (isLoggedIn()): ?>
                <span class="nav-username">👤 <?= htmlspecialchars($_SESSION['username']) ?></span>
                <a href="/api/auth.php?action=logout" class="btn btn-sm btn-outline">Logout</a>
            <?php else: ?>
                <a href="/login.php">Login</a>
                <a href="/register.php" class="btn btn-sm btn-primary">Register</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<main class="main-content">
    <div class="container">
