<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Logged out successfully.']);
    exit;
}

if ($action === 'login') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
        exit;
    }

    $user = $db->users->findOne([
        '$or' => [['username' => $identifier], ['email' => $identifier]],
    ]);

    if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid username/email or password.']);
        exit;
    }

    $_SESSION['user_id']  = (string) $user['_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role']     = $user['role'];

    echo json_encode(['success' => true, 'message' => 'Login successful.', 'role' => $user['role']]);
    exit;
}

if ($action === 'register') {
    $username         = trim($_POST['username'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
        exit;
    }

    if ($password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
        exit;
    }

    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
        exit;
    }

    // Check duplicates
    $existing = $db->users->findOne([
        '$or' => [['username' => $username], ['email' => $email]],
    ]);
    if ($existing) {
        echo json_encode(['success' => false, 'message' => 'Username or email already taken.']);
        exit;
    }

    $hashed = password_hash($password, PASSWORD_BCRYPT);
    $result = $db->users->insertOne([
        'username'   => $username,
        'email'      => $email,
        'password'   => $hashed,
        'role'       => 'customer',
        'created_at' => new MongoDB\BSON\UTCDateTime(),
    ]);

    $userId = (string) $result->getInsertedId();
    $_SESSION['user_id']  = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['role']     = 'customer';

    echo json_encode(['success' => true, 'message' => 'Registration successful.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);
