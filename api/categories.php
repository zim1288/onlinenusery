<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $categories = mongoDocs($db->categories->find([], ['sort' => ['name' => 1]]));
    echo json_encode($categories);
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
        $result = $db->categories->insertOne(['name' => $name, 'description' => $desc]);
        echo json_encode(['success' => true, 'message' => 'Category added.', 'id' => (string) $result->getInsertedId()]);
        exit;
    }

    if ($action === 'update') {
        $id   = $_POST['id'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $oid  = toObjectId($id);
        if (!$oid || empty($name)) {
            echo json_encode(['success' => false, 'message' => 'ID and name are required.']);
            exit;
        }
        $db->categories->updateOne(
            ['_id' => $oid],
            ['$set' => ['name' => $name, 'description' => $desc]]
        );
        echo json_encode(['success' => true, 'message' => 'Category updated.']);
        exit;
    }

    if ($action === 'delete') {
        $id  = $_POST['id'] ?? '';
        $oid = toObjectId($id);
        if (!$oid) {
            echo json_encode(['success' => false, 'message' => 'Invalid category ID.']);
            exit;
        }
        $db->categories->deleteOne(['_id' => $oid]);
        echo json_encode(['success' => true, 'message' => 'Category deleted.']);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid request.']);
