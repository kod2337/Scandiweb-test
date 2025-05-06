<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CORS headers to allow any origin (for testing only)
header("Access-Control-Allow-Origin: *");
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