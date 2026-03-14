<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

$search     = trim($_GET['search'] ?? '');
$categoryId = (int)($_GET['category_id'] ?? 0);
$minPrice   = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float)$_GET['min_price'] : null;
$maxPrice   = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float)$_GET['max_price'] : null;
$sort       = $_GET['sort'] ?? 'newest';

$where  = [];
$params = [];
$types  = '';

if ($search !== '') {
    $where[]  = "(p.name LIKE ? OR p.description LIKE ?)";
    $like     = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $types   .= 'ss';
}

if ($categoryId > 0) {
    $where[]  = "p.category_id = ?";
    $params[] = $categoryId;
    $types   .= 'i';
}

if ($minPrice !== null) {
    $where[]  = "p.price >= ?";
    $params[] = $minPrice;
    $types   .= 'd';
}

if ($maxPrice !== null) {
    $where[]  = "p.price <= ?";
    $params[] = $maxPrice;
    $types   .= 'd';
}

$whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

$orderBy = 'p.created_at DESC';
if ($sort === 'price_asc')  $orderBy = 'p.price ASC';
if ($sort === 'price_desc') $orderBy = 'p.price DESC';
if ($sort === 'name_asc')   $orderBy = 'p.name ASC';

$sql = "
    SELECT
        p.id,
        p.name,
        p.description,
        p.price,
        p.image,
        p.stock,
        p.category_id,
        c.name AS category_name,
        ROUND(COALESCE(AVG(r.rating), 0), 1) AS avg_rating,
        COUNT(r.id) AS review_count
    FROM plants p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN reviews r ON p.id = r.plant_id
    $whereClause
    GROUP BY p.id, p.name, p.description, p.price, p.image, p.stock, p.category_id, c.name
    ORDER BY $orderBy
";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$plants = [];

while ($row = $result->fetch_assoc()) {
    $plants[] = $row;
}
$stmt->close();

echo json_encode($plants);
