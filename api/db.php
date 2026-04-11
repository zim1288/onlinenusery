<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Set MONGODB_URI environment variable to your MongoDB Atlas connection string, e.g.:
// mongodb+srv://username:password@cluster0.xxxxx.mongodb.net/?retryWrites=true&w=majority
$mongoUri = getenv('MONGODB_URI') ?: 'mongodb://localhost:27017';
$mongoDb  = getenv('MONGODB_DB')  ?: 'nursery';

try {
    $client = new MongoDB\Client($mongoUri, [], [
        'typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array'],
    ]);
    $db = $client->selectDatabase($mongoDb);
} catch (Exception $e) {
    die(json_encode(['error' => 'Connection failed: ' . $e->getMessage()]));
}

/**
 * Recursively convert a MongoDB document (array with BSON types) to a plain
 * PHP array suitable for json_encode, renaming _id to id.
 */
function mongoDoc($doc): ?array {
    if ($doc === null) return null;
    $arr = is_array($doc) ? $doc : (array) $doc;
    $out = [];
    foreach ($arr as $key => $val) {
        $newKey = ($key === '_id') ? 'id' : $key;
        if ($val instanceof MongoDB\BSON\ObjectId) {
            $out[$newKey] = (string) $val;
        } elseif ($val instanceof MongoDB\BSON\UTCDateTime) {
            $out[$newKey] = $val->toDateTime()->format('Y-m-d H:i:s');
        } elseif (is_array($val)) {
            $out[$newKey] = array_map(
                fn($item) => (is_array($item) || is_object($item)) ? mongoDoc($item) : $item,
                $val
            );
        } elseif (is_object($val)) {
            $out[$newKey] = mongoDoc($val);
        } else {
            $out[$newKey] = $val;
        }
    }
    return $out;
}

/**
 * Convert a MongoDB cursor to an array of plain PHP arrays.
 */
function mongoDocs($cursor): array {
    $results = [];
    foreach ($cursor as $doc) {
        $results[] = mongoDoc((array) $doc);
    }
    return $results;
}

/**
 * Convert a hex string to a MongoDB ObjectId; returns null if the string is invalid.
 */
function toObjectId(string $id): ?MongoDB\BSON\ObjectId {
    try {
        return new MongoDB\BSON\ObjectId($id);
    } catch (Exception $e) {
        return null;
    }
}