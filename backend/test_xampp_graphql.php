<?php
// Simple script to test the GraphQL API through XAMPP

// Set headers for output
header('Content-Type: text/plain');

// Simulate a GraphQL query for categories
$categoriesQuery = <<<'GRAPHQL'
{
  categories {
    name
  }
}
GRAPHQL;

// Simulate a GraphQL query for products
$productsQuery = <<<'GRAPHQL'
{
  products(category: "all") {
    id
    name
    brand
  }
}
GRAPHQL;

// Create request payloads
$categoriesPayload = json_encode(['query' => $categoriesQuery]);
$productsPayload = json_encode(['query' => $productsQuery]);

// Function to make a GraphQL request
function makeGraphQLRequest($payload) {
    $ch = curl_init('http://localhost/ScandiwebProj/backend/index.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload)
    ]);
    curl_setopt($ch, CURLOPT_POST, 1);
    
    $result = curl_exec($ch);
    $error = curl_error($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    return [
        'status' => $info['http_code'],
        'error' => $error,
        'result' => $result
    ];
}

// Test categories query
echo "===== TESTING CATEGORIES QUERY =====\n";
$categoriesResponse = makeGraphQLRequest($categoriesPayload);
echo "Status Code: " . $categoriesResponse['status'] . "\n";
if ($categoriesResponse['error']) {
    echo "Error: " . $categoriesResponse['error'] . "\n";
}
echo "Response:\n";
echo $categoriesResponse['result'] . "\n\n";

// Test products query
echo "===== TESTING PRODUCTS QUERY =====\n";
$productsResponse = makeGraphQLRequest($productsPayload);
echo "Status Code: " . $productsResponse['status'] . "\n";
if ($productsResponse['error']) {
    echo "Error: " . $productsResponse['error'] . "\n";
}
echo "Response:\n";
echo $productsResponse['result'] . "\n"; 