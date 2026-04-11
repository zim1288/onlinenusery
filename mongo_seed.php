<?php
/**
 * MongoDB Seed Script
 * Run once to populate your MongoDB Atlas database with sample data.
 *
 * Usage:
 *   MONGODB_URI="mongodb+srv://user:pass@cluster.mongodb.net/" php mongo_seed.php
 *
 * Or set MONGODB_URI in a .env file and load it before running.
 */

require_once __DIR__ . '/vendor/autoload.php';

// Load .env file if present (simple key=value parser, no external dependency needed)
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0) continue;   // skip comment-only lines
        if (strpos($line, '=') === false) continue;      // skip malformed lines
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        // Strip inline comments (e.g. KEY=value # note)
        $value = trim(explode(' #', $value, 2)[0]);
        if (!getenv($key)) {                             // don't overwrite real env vars
            putenv("$key=$value");
        }
    }
    echo "Loaded .env file.\n";
}

$mongoUri = getenv('MONGODB_URI') ?: 'mongodb://localhost:27017';
$mongoDb  = getenv('MONGODB_DB')  ?: 'nursery';

$client = new MongoDB\Client($mongoUri, [], [
    'typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array'],
]);
$db = $client->selectDatabase($mongoDb);

echo "Connected to MongoDB database: $mongoDb\n";

// Drop existing collections
foreach (['users', 'categories', 'plants', 'orders', 'cart', 'reviews'] as $col) {
    $db->dropCollection($col);
    echo "Dropped collection: $col\n";
}

// Create indexes
$db->users->createIndex(['username' => 1], ['unique' => true]);
$db->users->createIndex(['email' => 1],    ['unique' => true]);
$db->cart->createIndex(['user_id' => 1, 'plant_id' => 1], ['unique' => true]);
$db->reviews->createIndex(['user_id' => 1, 'plant_id' => 1], ['unique' => true]);
$db->plants->createIndex(['category_id' => 1]);
$db->plants->createIndex(['name' => 1]);
echo "Indexes created.\n";

// Seed admin user (password: 'password')
$adminResult = $db->users->insertOne([
    'username'   => 'admin',
    'email'      => 'admin@nursery.com',
    'password'   => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'role'       => 'admin',
    'created_at' => new MongoDB\BSON\UTCDateTime(),
]);
echo "Admin user inserted (password: password)\n";

// Seed categories
$categoryData = [
    ['name' => 'Indoor Plants',       'description' => 'Beautiful plants perfect for indoor spaces, requiring minimal sunlight'],
    ['name' => 'Outdoor Plants',      'description' => 'Hardy plants ideal for gardens, patios, and outdoor spaces'],
    ['name' => 'Succulents & Cacti',  'description' => 'Low-maintenance drought-tolerant plants, great for beginners'],
    ['name' => 'Flowering Plants',    'description' => 'Vibrant blooming plants to brighten any space'],
    ['name' => 'Herbs & Vegetables',  'description' => 'Edible plants for your kitchen garden and cooking needs'],
];
$catResult = $db->categories->insertMany($categoryData);
$catIds    = array_values($catResult->getInsertedIds());
echo "Categories inserted: " . count($catIds) . "\n";

// Map category names to their ObjectIds
$catMap = [];
foreach ($categoryData as $i => $cat) {
    $catMap[$cat['name']] = $catIds[$i];
}

// Seed plants
$plantsData = [
    ['name' => 'Monstera Deliciosa', 'description' => 'The iconic Swiss Cheese Plant with dramatic split leaves. Easy to care for and great for brightening up any room. Thrives in indirect light and needs watering once a week.', 'price' => 24.99, 'image' => 'monstera.jpg',    'category' => 'Indoor Plants',      'stock' => 15],
    ['name' => 'Snake Plant',        'description' => 'One of the hardiest houseplants available. Excellent air purifier that tolerates low light and infrequent watering. Perfect for beginners and busy plant lovers.',         'price' => 14.99, 'image' => 'snake_plant.jpg', 'category' => 'Indoor Plants',      'stock' => 25],
    ['name' => 'Rose Bush',          'description' => 'Classic fragrant roses in deep red. This vigorous bush produces large blooms from spring through fall. Ideal for garden beds and borders with full sun exposure.',          'price' => 19.99, 'image' => 'rose.jpg',         'category' => 'Flowering Plants',   'stock' => 10],
    ['name' => 'Barrel Cactus',      'description' => 'A striking cylindrical cactus with prominent ribs and sharp spines. Virtually indestructible, needs watering only once a month. Great conversation starter.',              'price' => 12.99, 'image' => 'cactus.jpg',       'category' => 'Succulents & Cacti', 'stock' => 20],
    ['name' => 'Lavender',           'description' => 'Fragrant purple flowering herb that repels insects and promotes relaxation. Drought tolerant once established. Perfect for borders, pots, and herb gardens.',              'price' =>  9.99, 'image' => 'lavender.jpg',     'category' => 'Herbs & Vegetables', 'stock' => 18],
    ['name' => 'Fiddle Leaf Fig',    'description' => 'Trendy large-leafed statement plant perfect for bright living spaces. Grows tall and adds architectural interest. Needs bright indirect light and consistent watering.',   'price' => 45.99, 'image' => 'fiddle_fig.jpg',   'category' => 'Indoor Plants',      'stock' =>  8],
    ['name' => 'Aloe Vera',          'description' => 'Practical and beautiful succulent with thick gel-filled leaves. Soothing properties for skin burns and irritations. Thrives in bright light with minimal watering.',       'price' =>  8.99, 'image' => 'aloe.jpg',         'category' => 'Succulents & Cacti', 'stock' => 30],
    ['name' => 'Basil',              'description' => 'Fresh culinary herb essential for Italian cooking. Grows quickly and produces abundant aromatic leaves. Keep on a sunny windowsill and harvest regularly for bushy growth.', 'price' =>  5.99, 'image' => 'basil.jpg',        'category' => 'Herbs & Vegetables', 'stock' => 40],
];

$plantDocs = [];
foreach ($plantsData as $p) {
    $plantDocs[] = [
        'name'        => $p['name'],
        'description' => $p['description'],
        'price'       => $p['price'],
        'image'       => $p['image'],
        'category_id' => $catMap[$p['category']] ?? null,
        'stock'       => $p['stock'],
        'created_at'  => new MongoDB\BSON\UTCDateTime(),
    ];
}
$db->plants->insertMany($plantDocs);
echo "Plants inserted: " . count($plantDocs) . "\n";

echo "\nSeed complete! Your MongoDB database '$mongoDb' is ready.\n";
echo "Admin login: admin / password\n";
