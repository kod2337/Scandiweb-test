<?php
// Debug script for product resolver issues

require_once __DIR__ . '/vendor/autoload.php';

// Import required classes
use Dotenv\Dotenv;
use App\Model\Product;
use App\GraphQL\Resolver\ProductResolver;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Database connection info
$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '3306';
$dbname = $_ENV['DB_NAME'] ?? 'scandiweb_test';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASSWORD'] ?? '';

try {
    echo "===== PRODUCT RESOLVER DEBUG =====\n\n";
    
    // Connect to database
    $db = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname}",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "Connected to database successfully\n\n";
    
    // Instantiate model and resolver
    $productModel = new Product($db);
    $productResolver = new ProductResolver($db);
    
    // Test connection
    $connectionTest = $productModel->debugConnection();
    echo "Connection test: " . ($connectionTest['success'] ? "Success" : "Failed") . "\n";
    echo "Message: " . $connectionTest['message'] . "\n\n";
    
    // Fetch products directly from model
    echo "Fetching products from Model...\n";
    $products = $productModel->getProductsByCategory('all');
    echo "Found " . count($products) . " products in the database\n";
    
    if (count($products) > 0) {
        echo "First product: \n";
        print_r($products[0]);
        
        // Test retrieving additional product data
        $productId = $products[0]['id'];
        echo "\nTesting gallery, attributes, and prices for product '{$productId}':\n";
        
        $gallery = $productModel->getProductGallery($productId);
        echo "Gallery: " . (count($gallery) ? "Found " . count($gallery) . " images" : "No images found") . "\n";
        
        $attributes = $productModel->getProductAttributes($productId);
        echo "Attributes: " . (count($attributes) ? "Found " . count($attributes) . " attribute sets" : "No attributes found") . "\n";
        
        $prices = $productModel->getProductPrices($productId);
        echo "Prices: " . (count($prices) ? "Found " . count($prices) . " price entries" : "No prices found") . "\n";
        
        // Now test the resolver to see if it transforms the data correctly
        echo "\nTesting ProductResolver with the same product...\n";
        $formattedProduct = $productResolver->formatProduct($products[0]);
        
        // Check for null values in required fields
        $requiredFields = ['id', 'name', 'inStock', 'gallery', 'description', 'category', 'attributes', 'prices', 'brand'];
        foreach ($requiredFields as $field) {
            if (!isset($formattedProduct[$field]) || $formattedProduct[$field] === null) {
                echo "⚠️ ERROR: Field '{$field}' is NULL or not set in formatted product!\n";
            } else {
                echo "✓ Field '{$field}' exists and is not null\n";
            }
        }
        
        // Try the resolver's getProducts method
        echo "\nTesting ProductResolver::getProducts method...\n";
        $resolverProducts = $productResolver->getProducts(['category' => 'all']);
        echo "Resolver returned " . count($resolverProducts) . " products\n";
        
        if (count($resolverProducts) > 0) {
            echo "First resolved product:\n";
            print_r($resolverProducts[0]);
        }
    } else {
        echo "⚠️ ERROR: No products found in database! Please check the seed data.\n";
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
} 