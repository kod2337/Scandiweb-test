<?php

namespace App\GraphQL\Resolver;

use App\Model\Product;
use PDO;

class ProductResolver extends AbstractResolver
{
    /**
     * Get all products or filter by category
     * 
     * @param array $args
     * @return array
     */
    public function getProducts(array $args): array
    {
        $category = $args['category'] ?? 'all';
        
        try {
            $productModel = new Product($this->getDb());
            $products = $productModel->getProductsByCategory($category);
            
            // Safety check - ensure we have an array
            if (!is_array($products)) {
                error_log('Products query returned non-array result');
                return [];
            }
            
            // Process products and ensure they have all required fields
            $validProducts = [];
            foreach ($products as $product) {
                try {
                    // Skip products with missing required fields
                    if (!isset($product['id']) || empty($product['id'])) {
                        error_log('Skipping product with missing ID');
                        continue;
                    }
                    
                    // Create a new product with guaranteed fields
                    $validProduct = [
                        'id' => $product['id'],
                        'name' => isset($product['name']) && !empty($product['name']) 
                            ? $product['name'] : 'Untitled Product',
                        'brand' => isset($product['brand']) && !empty($product['brand'])
                            ? $product['brand'] : 'Unknown Brand',
                        'inStock' => isset($product['inStock']) ? (bool)$product['inStock'] : true,
                        'description' => isset($product['description']) ? $product['description'] : '',
                        'category' => isset($product['category']) && !empty($product['category'])
                            ? $product['category'] : 'all',
                    ];
                    
                    // Add remaining fields safely through formatProduct
                    $formattedProduct = $this->formatProduct(array_merge($product, $validProduct));
                    $validProducts[] = $formattedProduct;
                } catch (\Exception $e) {
                    // Log error and skip this product
                    error_log('Error formatting product: ' . $e->getMessage() . ' for product ID: ' . ($product['id'] ?? 'unknown'));
                    continue;
                }
            }
            
            // If we didn't get any valid products, return empty array
            if (empty($validProducts)) {
                error_log('No valid products found for category: ' . $category);
                return [];
            }
            
            return $validProducts;
            
        } catch (\Exception $e) {
            // Log the error
            error_log('Error fetching products from database: ' . $e->getMessage());
            
            // Try to load from data.json as fallback
            try {
                $jsonFile = __DIR__ . '/../../../../data.json';
                if (file_exists($jsonFile)) {
                    $jsonData = json_decode(file_get_contents($jsonFile), true);
                    
                    if (isset($jsonData['data']['products']) && is_array($jsonData['data']['products'])) {
                        $products = $jsonData['data']['products'];
                        
                        // Filter by category if specified
                        if ($category !== 'all') {
                            $products = array_filter($products, function($product) use ($category) {
                                return isset($product['category']) && $product['category'] === $category;
                            });
                        }
                        
                        // Filter out products with missing required fields
                        $validProducts = [];
                        foreach ($products as $product) {
                            // Skip if missing required fields
                            if (!isset($product['id']) || empty($product['id'])) {
                                continue;
                            }
                            
                            // Clean up __typename fields that might be causing trouble
                            $cleanProduct = $this->removeTypenameFields($product);
                            
                            // Apply default values for required fields
                            $cleanProduct['name'] = $cleanProduct['name'] ?? 'Untitled Product';
                            $cleanProduct['brand'] = $cleanProduct['brand'] ?? 'Unknown Brand';
                            $cleanProduct['inStock'] = isset($cleanProduct['inStock']) ? (bool)$cleanProduct['inStock'] : true;
                            $cleanProduct['description'] = $cleanProduct['description'] ?? '';
                            $cleanProduct['gallery'] = isset($cleanProduct['gallery']) && is_array($cleanProduct['gallery']) 
                                ? $cleanProduct['gallery'] : ['https://via.placeholder.com/300x300?text=No+Image'];
                            
                            $validProducts[] = $cleanProduct;
                        }
                        
                        return array_values($validProducts); // Reset array keys
                    }
                }
                
                // If we get here, we couldn't load from JSON either
                return [];
            } catch (\Exception $jsonEx) {
                error_log('Error loading from data.json: ' . $jsonEx->getMessage());
                return [];
            }
        }
    }

    /**
     * Get a product by ID
     * 
     * @param array $args
     * @return array|null
     */
    public function getProduct(array $args): ?array
    {
        $id = $args['id'] ?? null;
        
        if ($id === null) {
            return null;
        }
        
        $productModel = new Product($this->getDb());
        $product = $productModel->getProductById($id);
        
        if (!$product) {
            return null;
        }
        
        return $this->formatProduct($product);
    }

    /**
     * Format product data for GraphQL, with safe defaults for all fields
     * 
     * @param array $product
     * @return array
     */
    public function formatProduct(array $product): array
    {
        $productModel = new Product($this->getDb());
        
        // Ensure we have a valid product ID
        $productId = !empty($product['id']) ? $product['id'] : ('product-' . uniqid());
        
        // Initialize with default values for required fields
        $formattedProduct = [
            'id' => $productId,
            'name' => !empty($product['name']) ? trim($product['name']) : 'Untitled Product',
            'brand' => !empty($product['brand']) ? trim($product['brand']) : 'Unknown Brand',
            'inStock' => isset($product['inStock']) ? (bool)$product['inStock'] : true,
            'description' => isset($product['description']) ? trim($product['description']) : '',
            'category' => !empty($product['category']) ? trim($product['category']) : 'all',
            'gallery' => ['https://via.placeholder.com/300x300?text=No+Image'],
            'prices' => [[
                'amount' => 0,
                'currency' => [
                    'label' => 'USD',
                    'symbol' => '$'
                ]
            ]],
            'attributes' => []
        ];
        
        // Try to fetch gallery images
        try {
            $gallery = $productModel->getProductGallery($productId);
            if (!empty($gallery) && is_array($gallery)) {
                $formattedProduct['gallery'] = array_values(array_filter($gallery));
            }
        } catch (\Exception $e) {
            error_log("Error fetching gallery for product $productId: " . $e->getMessage());
        }
        
        // Try to fetch attributes
        try {
            $attributes = $productModel->getProductAttributes($productId);
            if (!empty($attributes) && is_array($attributes)) {
                $formattedProduct['attributes'] = array_map(function($attr) {
                    return [
                        'id' => $attr['id'] ?? uniqid(),
                        'name' => $attr['name'] ?? 'Unnamed Attribute',
                        'type' => $attr['type'] ?? 'text',
                        'items' => array_map(function($item) {
                            return [
                                'id' => $item['id'] ?? uniqid(),
                                'displayValue' => $item['displayValue'] ?? $item['value'] ?? '',
                                'value' => $item['value'] ?? ''
                            ];
                        }, $attr['items'] ?? [])
                    ];
                }, $attributes);
            }
        } catch (\Exception $e) {
            error_log("Error fetching attributes for product $productId: " . $e->getMessage());
        }
        
        // Try to fetch prices
        try {
            $prices = $productModel->getProductPrices($productId);
            if (!empty($prices) && is_array($prices)) {
                $formattedProduct['prices'] = array_map(function($price) {
                    return [
                        'amount' => (float)($price['amount'] ?? 0),
                        'currency' => [
                            'label' => $price['currency']['label'] ?? 'USD',
                            'symbol' => $price['currency']['symbol'] ?? '$'
                        ]
                    ];
                }, $prices);
            }
        } catch (\Exception $e) {
            error_log("Error fetching prices for product $productId: " . $e->getMessage());
        }
        
        return $formattedProduct;
    }

    /**
     * Recursively remove __typename fields from an array
     * 
     * @param array $data
     * @return array
     */
    private function removeTypenameFields(array $data): array
    {
        $result = [];
        
        foreach ($data as $key => $value) {
            if ($key === '__typename') {
                continue;
            }
            
            if (is_array($value)) {
                $result[$key] = $this->removeTypenameFields($value);
            } else {
                $result[$key] = $value;
            }
        }
        
        return $result;
    }
} 