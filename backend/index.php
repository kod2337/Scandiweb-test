<?php
// Enable error reporting in development mode
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log errors to a file
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Log request details
error_log('Request Method: ' . $_SERVER['REQUEST_METHOD']);
error_log('Request Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? 'none'));
error_log('Request Headers: ' . json_encode(getallheaders()));

// Autoload classes
require_once __DIR__ . '/vendor/autoload.php';

// Import required classes
use Dotenv\Dotenv;
use GraphQL\GraphQL;
use GraphQL\Utils\BuildSchema;
use GraphQL\Error\DebugFlag;
use GraphQL\Error\FormattedError;
use App\GraphQL\Resolver\CategoryResolver;
use App\GraphQL\Resolver\ProductResolver;
use App\GraphQL\Mutation\OrderMutation;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Get the origin from the request headers
$isDevelopment = !isset($_ENV['ENVIRONMENT']) || $_ENV['ENVIRONMENT'] === 'development';

$allowedOrigins = [
    // Production origins
    'https://testproj123.sbca.online',
    'https://www.testproj123.sbca.online',
    // Development origins
    'http://localhost:5173',
    'http://localhost:5174',
    'http://127.0.0.1:5173',
    'http://127.0.0.1:5174'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Handle CORS
if (in_array($origin, $allowedOrigins)) {
    if (!headers_sent()) {
        header("Access-Control-Allow-Origin: $origin");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept');
    }
}

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Process only POST requests for GraphQL
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['errors' => [['message' => 'Method not allowed. Please use POST.']]]);
    exit;
}

// Get the request content
$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['errors' => [['message' => 'Invalid JSON request.']]]);
    exit;
}

try {
    // Database connection
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $port = $_ENV['DB_PORT'] ?? '3306';
    $dbname = $_ENV['DB_NAME'] ?? 'scandiweb_test';
    $user = $_ENV['DB_USER'] ?? 'root';
    $pass = $_ENV['DB_PASSWORD'] ?? '';
    
    $db = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname}",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Create schema
    $schemaFile = __DIR__ . '/src/GraphQL/Schema/schema.graphql';
    if (!file_exists($schemaFile)) {
        throw new \Exception("Schema file not found: {$schemaFile}");
    }
    
    $schemaContent = file_get_contents($schemaFile);
    
    // Initialize resolvers
    $categoryResolver = new CategoryResolver($db);
    $productResolver = new ProductResolver($db);
    $orderMutation = new OrderMutation($db);
    
    // Set up schema
    $schema = BuildSchema::build($schemaContent);
    
    // Root value (entry points)
    $rootValue = [
        // Query resolvers
        'categories' => function() use ($categoryResolver) {
            try {
                $defaultCategories = [
                    ['name' => 'all'],
                    ['name' => 'clothes'],
                    ['name' => 'tech']
                ];
                
                try {
                    $categories = $categoryResolver->getCategories();
                    if (empty($categories)) {
                        return $defaultCategories;
                    }
                    
                    return array_map(function($category) {
                        return [
                            'name' => $category['name'] ?? 'all'
                        ];
                    }, $categories);
                } catch (\Exception $e) {
                    error_log('Error getting categories from resolver: ' . $e->getMessage());
                    return $defaultCategories;
                }
            } catch (\Exception $e) {
                error_log('Error in categories root resolver: ' . $e->getMessage());
                return [['name' => 'all']];
            }
        },
        'category' => function($rootValue, $args) use ($categoryResolver) {
            try {
                $category = $categoryResolver->getCategory($args);
                if (!$category) {
                    return null;
                }
                return [
                    'name' => $category['name'] ?? 'all',
                    'products' => function() use ($category, $categoryResolver) {
                        return $categoryResolver->getCategoryProducts($category) ?: [];
                    }
                ];
            } catch (\Exception $e) {
                error_log('Error in category resolver: ' . $e->getMessage());
                return null;
            }
        },
        'products' => function($rootValue, $args) use ($productResolver) {
            try {
                $products = $productResolver->getProducts($args);
                return array_map(function($product) use ($productResolver) {
                    return $productResolver->formatProduct($product);
                }, $products);
            } catch (\Exception $e) {
                error_log('Error in products resolver: ' . $e->getMessage());
                return [];
            }
        },
        'product' => function($rootValue, $args) use ($productResolver) {
            try {
                $product = $productResolver->getProduct($args);
                return $product ? $productResolver->formatProduct($product) : null;
            } catch (\Exception $e) {
                error_log('Error in product resolver: ' . $e->getMessage());
                return null;
            }
        },
        
        // Mutation resolvers
        'placeOrder' => [$orderMutation, 'placeOrder']
    ];
    
    // The context object to be passed to all resolvers
    $context = [
        'db' => $db
    ];
    
    // Enable debugging in development environment
    $debugFlag = DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE;
    
    // Execute the query
    $result = GraphQL::executeQuery(
        $schema,
        $input['query'],
        $rootValue,
        $context,
        $input['variables'] ?? null,
        $input['operationName'] ?? null
    );
    
    $output = $result->toArray($debugFlag);
    
    // If we got errors but have data, try to continue
    if (isset($output['errors']) && isset($output['data'])) {
        error_log('GraphQL partial success - errors: ' . json_encode($output['errors']));
        
        // Remove null values from arrays
        $output['data'] = array_map(function($value) {
            return is_array($value) ? array_filter($value, function($v) {
                return $v !== null;
            }) : $value;
        }, $output['data']);
        
        // If we still have valid data, only log the errors
        if (!empty(array_filter($output['data']))) {
            unset($output['errors']);
        }
    }
    
} catch (\Exception $e) {
    error_log('GraphQL Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    
    // Try to load from data.json as fallback
    try {
        $jsonFile = __DIR__ . '/../data.json';
        if (file_exists($jsonFile)) {
            $jsonData = json_decode(file_get_contents($jsonFile), true);
            
            if (isset($jsonData['data'])) {
                $output = ['data' => $jsonData['data']];
            } else {
                $output = [
                    'data' => null,
                    'errors' => [
                        [
                            'message' => 'An error occurred: ' . $e->getMessage(),
                            'extensions' => ['code' => 'INTERNAL_SERVER_ERROR']
                        ]
                    ]
                ];
            }
        }
    } catch (\Exception $jsonEx) {
        $output = [
            'errors' => [
                [
                    'message' => 'An error occurred: ' . $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'locations' => null,
                    'debugMessage' => $e->getMessage(),
                    'extensions' => ['code' => 'INTERNAL_SERVER_ERROR']
                ]
            ]
        ];
    }
}

// Return the result as JSON
echo json_encode($output); 