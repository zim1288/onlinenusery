<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $result = $conn->query("SELECT id, name, description FROM categories ORDER BY name ASC");
    echo json_encode($result->fetch_all(MYSQLI_ASSOC));
    exit;
}

if ($method === 'POST') {
    if (!isLoggedIn() || !isAdmin()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Category name is required.']);
            exit;
        }
        $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        $stmt->bind_param('ss', $name, $desc);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Category added.', 'id' => $stmt->insert_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add category.']);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'update') {
        $id   = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if ($id <= 0 || empty($name)) {
            echo json_encode(['success' => false, 'message' => 'ID and name are required.']);
            exit;
        }
        $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
        $stmt->bind_param('ssi', $name, $desc, $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Category updated.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update category.']);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid category ID.']);
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Category deleted.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete category.']);
        }
        $stmt->close();
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid request.']);
