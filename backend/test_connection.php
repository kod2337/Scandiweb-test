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
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Return a simple JSON response
echo json_encode([
    'success' => true,
    'message' => 'Backend connection successful',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => phpversion()
]); 