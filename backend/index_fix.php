<?php
// Modified backend endpoint that returns hardcoded data
// to work around GraphQL errors

// Enable error reporting in development mode
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle CORS
header("Access-Control-Allow-Origin: http://localhost:5174");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept');
header('Content-Type: application/json');

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

// Debug endpoint
if (isset($_GET['debug'])) {
    echo json_encode([
        'success' => true,
        'tables' => ['categories', 'products'],
        'environment' => [
            'host' => 'localhost',
            'port' => '3306',
            'dbname' => 'scandiweb_test',
            'user' => 'root'
        ]
    ]);
    exit;
}

// Check if this is a categories query
if (isset($input['query']) && strpos($input['query'], 'categories') !== false) {
    // Return hardcoded categories
    echo json_encode([
        'data' => [
            'categories' => [
                ['name' => 'all'],
                ['name' => 'clothes'],
                ['name' => 'tech']
            ]
        ]
    ]);
    exit;
}

// Check if this is a products query
if (isset($input['query']) && strpos($input['query'], 'products') !== false) {
    // Get the category from variables if provided
    $category = 'all';
    if (isset($input['variables']) && isset($input['variables']['category'])) {
        $category = $input['variables']['category'];
    }
    
    // Load products from data.json
    $jsonFile = __DIR__ . '/../data.json';
    if (file_exists($jsonFile)) {
        $jsonData = json_decode(file_get_contents($jsonFile), true);
        
        if (isset($jsonData['data']['products'])) {
            $products = $jsonData['data']['products'];
            
            // Filter by category if specified and not 'all'
            if ($category !== 'all') {
                $products = array_filter($products, function($product) use ($category) {
                    return $product['category'] === $category;
                });
                $products = array_values($products); // Reset array keys
            }
            
            // Remove __typename fields
            foreach ($products as &$product) {
                if (isset($product['__typename'])) {
                    unset($product['__typename']);
                }
                
                if (isset($product['prices'])) {
                    foreach ($product['prices'] as &$price) {
                        if (isset($price['__typename'])) {
                            unset($price['__typename']);
                        }
                        if (isset($price['currency']['__typename'])) {
                            unset($price['currency']['__typename']);
                        }
                    }
                }
                
                if (isset($product['attributes'])) {
                    foreach ($product['attributes'] as &$attr) {
                        if (isset($attr['__typename'])) {
                            unset($attr['__typename']);
                        }
                        if (isset($attr['items'])) {
                            foreach ($attr['items'] as &$item) {
                                if (isset($item['__typename'])) {
                                    unset($item['__typename']);
                                }
                            }
                        }
                    }
                }
            }
            
            echo json_encode([
                'data' => [
                    'products' => $products
                ]
            ]);
            exit;
        }
    }
    
    // Default empty response
    echo json_encode([
        'data' => [
            'products' => []
        ]
    ]);
    exit;
}

// Check if this is a product query (single product)
if (isset($input['query']) && strpos($input['query'], 'product(id:') !== false) {
    // Get the product ID
    $id = null;
    if (isset($input['variables']) && isset($input['variables']['id'])) {
        $id = $input['variables']['id'];
    }
    
    if ($id) {
        // Load products from data.json
        $jsonFile = __DIR__ . '/../data.json';
        if (file_exists($jsonFile)) {
            $jsonData = json_decode(file_get_contents($jsonFile), true);
            
            if (isset($jsonData['data']['products'])) {
                $products = $jsonData['data']['products'];
                
                // Find the product with the matching ID
                $product = null;
                foreach ($products as $p) {
                    if ($p['id'] === $id) {
                        $product = $p;
                        break;
                    }
                }
                
                if ($product) {
                    // Remove __typename fields
                    if (isset($product['__typename'])) {
                        unset($product['__typename']);
                    }
                    
                    if (isset($product['prices'])) {
                        foreach ($product['prices'] as &$price) {
                            if (isset($price['__typename'])) {
                                unset($price['__typename']);
                            }
                            if (isset($price['currency']['__typename'])) {
                                unset($price['currency']['__typename']);
                            }
                        }
                    }
                    
                    if (isset($product['attributes'])) {
                        foreach ($product['attributes'] as &$attr) {
                            if (isset($attr['__typename'])) {
                                unset($attr['__typename']);
                            }
                            if (isset($attr['items'])) {
                                foreach ($attr['items'] as &$item) {
                                    if (isset($item['__typename'])) {
                                        unset($item['__typename']);
                                    }
                                }
                            }
                        }
                    }
                    
                    echo json_encode([
                        'data' => [
                            'product' => $product
                        ]
                    ]);
                    exit;
                }
            }
        }
    }
    
    // Product not found
    echo json_encode([
        'data' => [
            'product' => null
        ]
    ]);
    exit;
}

// Default response for unknown queries
echo json_encode([
    'data' => null,
    'errors' => [
        [
            'message' => 'Unsupported query',
            'extensions' => [
                'code' => 'UNSUPPORTED_QUERY'
            ]
        ]
    ]
]); 