<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log request details
error_log('CORS Test - Request Method: ' . $_SERVER['REQUEST_METHOD']);
error_log('CORS Test - Request Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? 'none'));

// Set allowed origins
$allowedOrigins = [
    'https://testproj123.sbca.online',
    'https://www.testproj123.sbca.online',
    'http://localhost:5173',
    'http://localhost:5174'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Set CORS headers
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header("Access-Control-Allow-Origin: https://testproj123.sbca.online");
}

header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Return a simple JSON response
echo json_encode([
    'success' => true,
    'message' => 'CORS test successful',
    'request_info' => [
        'method' => $_SERVER['REQUEST_METHOD'],
        'origin' => $_SERVER['HTTP_ORIGIN'] ?? 'none',
        'headers' => getallheaders()
    ],
    'timestamp' => date('Y-m-d H:i:s')
]); 