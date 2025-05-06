<?php
// Simple script to test GraphQL endpoint

// Make a POST request to the GraphQL endpoint
$url = 'http://localhost/ScandiwebProj/backend/index.php';
$data = [
    'query' => 'query { categories { name } }'
];

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data)
    ]
];

$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

if ($result === FALSE) {
    echo "Error making request\n";
} else {
    echo "Response:\n";
    echo json_encode(json_decode($result), JSON_PRETTY_PRINT);
    echo "\n\n";
}

// Also test products query
$data = [
    'query' => 'query { products(category: "all") { id name brand } }'
];

$options['http']['content'] = json_encode($data);
$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

if ($result === FALSE) {
    echo "Error making request for products\n";
} else {
    echo "Products Response:\n";
    echo json_encode(json_decode($result), JSON_PRETTY_PRINT);
    echo "\n";
} 