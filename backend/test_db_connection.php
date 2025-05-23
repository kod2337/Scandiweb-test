<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CORS headers
$isDevelopment = !isset($_ENV['ENVIRONMENT']) || $_ENV['ENVIRONMENT'] === 'development';

$allowedOrigins = [
    // Production origins
    'https://testproj123.sbca.online',
    'https://www.testproj123.sbca.online'
];

// Add development origins when in development mode
if ($isDevelopment) {
    $developmentOrigins = [
        'http://localhost:5173',
        'http://localhost:5174',
        'http://127.0.0.1:5173',
        'http://127.0.0.1:5174'
    ];
    $allowedOrigins = array_merge($allowedOrigins, $developmentOrigins);
}

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    // Default to the appropriate origin based on environment
    header("Access-Control-Allow-Origin: " . ($isDevelopment ? "http://localhost:5174" : "https://testproj123.sbca.online"));
}
header('Content-Type: application/json');

// Load environment variables
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Get DB connection parameters
$driver = $_ENV['DB_DRIVER'] ?? 'pdo_mysql';
$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '3306';
$dbname = $_ENV['DB_NAME'] ?? 'scandiweb_test';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASSWORD'] ?? '';

// Results array
$result = [
    'connection_test' => false,
    'env_variables' => [
        'driver' => $driver,
        'host' => $host,
        'port' => $port,
        'dbname' => $dbname,
        'user' => $user,
        'pass' => str_repeat('*', strlen($pass)) // Mask password
    ],
    'tables' => [],
    'error' => null
];

// Try to connect to database
try {
    $db = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname}",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $result['connection_test'] = true;
    
    // Test query
    $stmt = $db->query("SHOW TABLES");
    $result['tables'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Check categories table
    if (in_array('categories', $result['tables'])) {
        $stmt = $db->query("SELECT COUNT(*) AS count FROM categories");
        $result['categories_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $db->query("SELECT * FROM categories LIMIT 5");
        $result['categories_sample'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Check products table
    if (in_array('products', $result['tables'])) {
        $stmt = $db->query("SELECT COUNT(*) AS count FROM products");
        $result['products_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $db->query("SELECT id, name, category FROM products LIMIT 5");
        $result['products_sample'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (PDOException $e) {
    $result['error'] = [
        'message' => $e->getMessage(),
        'code' => $e->getCode()
    ];
}

// Return results
echo json_encode($result, JSON_PRETTY_PRINT); 