<?php
// Test script for database connection and queries

require_once __DIR__ . '/vendor/autoload.php';

// Import required classes
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Database connection info
$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '3306';
$dbname = $_ENV['DB_NAME'] ?? 'scandiweb_test';
$user = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';

echo "=== Database Connection Test ===\n";
echo "Host: $host:$port\n";
echo "Database: $dbname\n";
echo "User: $user\n";

try {
    // Connect to database
    $db = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname}",
        $user,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "Connection successful!\n\n";
    
    // Test category queries
    echo "=== Categories ===\n";
    $stmt = $db->query("SELECT * FROM categories");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($categories)) {
        echo "No categories found in database.\n";
    } else {
        echo "Found " . count($categories) . " categories:\n";
        foreach ($categories as $category) {
            echo "- " . json_encode($category) . "\n";
        }
    }
    
    echo "\n";
    
    // Test product queries
    echo "=== Products ===\n";
    $stmt = $db->query("SELECT id, name, category FROM products LIMIT 5");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($products)) {
        echo "No products found in database.\n";
    } else {
        echo "Found " . count($products) . " products (limited to 5):\n";
        foreach ($products as $product) {
            echo "- " . json_encode($product) . "\n";
        }
    }
    
    // Test product attributes
    echo "\n=== Product Attributes ===\n";
    if (!empty($products)) {
        $productId = $products[0]['id'];
        echo "Testing attributes for product ID: $productId\n";
        
        $stmt = $db->prepare("
            SELECT a.id, a.name, a.type, ai.id as item_id, ai.displayValue, ai.value
            FROM product_attributes pa
            JOIN attribute_sets a ON pa.attribute_id = a.id
            JOIN attribute_items ai ON ai.attribute_id = a.id
            WHERE pa.product_id = ?
        ");
        $stmt->execute([$productId]);
        $attributes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($attributes)) {
            echo "No attributes found for this product.\n";
        } else {
            echo "Found " . count($attributes) . " attribute records.\n";
            $attributeSets = [];
            foreach ($attributes as $attr) {
                $id = $attr['id'];
                if (!isset($attributeSets[$id])) {
                    $attributeSets[$id] = [
                        'id' => $id,
                        'name' => $attr['name'],
                        'type' => $attr['type'],
                        'items' => []
                    ];
                }
                
                $attributeSets[$id]['items'][] = [
                    'id' => $attr['item_id'],
                    'displayValue' => $attr['displayValue'],
                    'value' => $attr['value']
                ];
            }
            
            foreach ($attributeSets as $attrSet) {
                echo "Attribute Set: " . $attrSet['name'] . " (Type: " . $attrSet['type'] . ")\n";
                foreach ($attrSet['items'] as $item) {
                    echo "  - " . $item['displayValue'] . " (" . $item['value'] . ")\n";
                }
            }
        }
    }
    
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
} 