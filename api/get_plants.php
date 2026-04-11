<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

$search     = trim($_GET['search'] ?? '');
$categoryId = $_GET['category_id'] ?? '';
$minPrice   = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float)$_GET['min_price'] : null;
$maxPrice   = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float)$_GET['max_price'] : null;
$sort       = $_GET['sort'] ?? 'newest';

// Build $match filter
$match = [];
if ($search !== '') {
    $match['$or'] = [
        ['name'        => ['$regex' => $search, '$options' => 'i']],
        ['description' => ['$regex' => $search, '$options' => 'i']],
    ];
}
if ($categoryId !== '') {
    $catOid = toObjectId($categoryId);
    if ($catOid) $match['category_id'] = $catOid;
}
if ($minPrice !== null || $maxPrice !== null) {
    $priceFilter = [];
    if ($minPrice !== null) $priceFilter['$gte'] = $minPrice;
    if ($maxPrice !== null) $priceFilter['$lte'] = $maxPrice;
    $match['price'] = $priceFilter;
}

$sortStage = match($sort) {
    'price_asc'  => ['price' => 1],
    'price_desc' => ['price' => -1],
    'name_asc'   => ['name'  => 1],
    default      => ['created_at' => -1],
};

$pipeline = [
    ['$match' => empty($match) ? (object)[] : $match],
    ['$lookup' => [
        'from'         => 'categories',
        'localField'   => 'category_id',
        'foreignField' => '_id',
        'as'           => 'category',
    ]],
    ['$lookup' => [
        'from'         => 'reviews',
        'localField'   => '_id',
        'foreignField' => 'plant_id',
        'as'           => 'reviews',
    ]],
    ['$addFields' => [
        'category_name' => ['$ifNull' => [['$arrayElemAt' => ['$category.name', 0]], null]],
        'avg_rating'    => ['$ifNull' => [['$round' => [['$avg' => '$reviews.rating'], 1]], 0.0]],
        'review_count'  => ['$size' => '$reviews'],
    ]],
    ['$project' => [
        'name' => 1, 'description' => 1, 'price' => 1, 'image' => 1, 'stock' => 1,
        'category_id' => 1, 'category_name' => 1, 'avg_rating' => 1, 'review_count' => 1,
        'created_at' => 1,
    ]],
    ['$sort' => $sortStage],
];

$plants = mongoDocs($db->plants->aggregate($pipeline));
echo json_encode($plants);
