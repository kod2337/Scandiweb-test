<?php
// Testing GraphQL via PHP built-in server
header('Content-Type: text/plain');

// Test query for products
$productsQuery = <<<'GRAPHQL'
{
  products(category: "all") {
    id
    name
    inStock
    brand
  }
}
GRAPHQL;

$payload = json_encode(['query' => $productsQuery]);

// Make a request to the PHP server
$ch = curl_init('http://localhost:8000');
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

echo "===== TESTING PHP SERVER GRAPHQL =====\n";
echo "Status Code: " . $info['http_code'] . "\n";
if ($error) {
    echo "Error: $error\n";
}

echo "Response:\n";
echo $result; 