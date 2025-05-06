<?php

namespace App\GraphQL\Resolver;

use App\Model\Category;
use App\Model\Product;
use PDO;

class CategoryResolver extends AbstractResolver
{
    /**
     * Get all categories
     * 
     * @return array
     */
    public function getCategories(): array
    {
        try {
            $categoryModel = new Category($this->getDb());
            $categories = $categoryModel->getAllCategories();

            // Default categories as fallback
            $defaultCategories = [
                ['name' => 'all'],
                ['name' => 'clothes'],
                ['name' => 'tech']
            ];
            
            // If we got categories from DB, validate and clean them
            if (!empty($categories) && is_array($categories)) {
                $validCategories = [];
                foreach ($categories as $category) {
                    // Ensure each category has a valid name
                    if (isset($category['name']) && !empty(trim($category['name']))) {
                        $validCategories[] = [
                            'name' => trim($category['name'])
                        ];
                    }
                }
                
                if (!empty($validCategories)) {
                    // Ensure 'all' category exists
                    $hasAll = false;
                    foreach ($validCategories as $category) {
                        if ($category['name'] === 'all') {
                            $hasAll = true;
                            break;
                        }
                    }
                    
                    if (!$hasAll) {
                        array_unshift($validCategories, ['name' => 'all']);
                    }
                    
                    return $validCategories;
                }
            }
            
            return $defaultCategories;
            
        } catch (\Exception $e) {
            error_log('Error in getCategories: ' . $e->getMessage());
            // Always return default categories on error
            return [
                ['name' => 'all'],
                ['name' => 'clothes'],
                ['name' => 'tech']
            ];
        }
    }

    /**
     * Get a specific category by name
     * 
     * @param array $args
     * @return array|null
     */
    public function getCategory(array $args): ?array
    {
        $name = $args['name'] ?? null;
        
        if ($name === null) {
            return null;
        }
        
        $categoryModel = new Category($this->getDb());
        $category = $categoryModel->getCategoryByName($name);
        
        if (!$category) {
            return null;
        }
        
        // Ensure name is never null
        if (!isset($category['name']) || $category['name'] === null) {
            throw new \Exception("Category name is missing");
        }
        
        return [
            'name' => $category['name']
        ];
    }

    /**
     * Get products for a category (used as field resolver)
     * 
     * @param array $category
     * @return array
     */
    public function getCategoryProducts(array $category): array
    {
        $name = $category['name'];
        
        if ($name === null) {
            throw new \Exception("Category name is required to fetch products");
        }
        
        try {
            $productModel = new Product($this->getDb());
            $products = $productModel->getProductsByCategory($name);
            
            $productResolver = new ProductResolver($this->getDb());
            
            // Filter products to ensure they have required fields before formatting
            $validProducts = [];
            foreach ($products as $product) {
                try {
                    // Skip products with missing required fields
                    if (!isset($product['id']) || empty($product['id']) ||
                        !isset($product['name']) || empty($product['name']) ||
                        !isset($product['brand']) || empty($product['brand'])) {
                        error_log('Skipping product with missing required fields in getCategoryProducts');
                        continue;
                    }
                    
                    $validProducts[] = $productResolver->formatProduct($product);
                } catch (\Exception $e) {
                    error_log('Error formatting product in getCategoryProducts: ' . $e->getMessage());
                    continue;
                }
            }
            
            return $validProducts;
        } catch (\Exception $e) {
            error_log('Error fetching category products: ' . $e->getMessage());
            
            // Try to load from data.json as fallback
            try {
                $jsonFile = __DIR__ . '/../../../../data.json';
                if (file_exists($jsonFile)) {
                    $jsonData = json_decode(file_get_contents($jsonFile), true);
                    
                    if (isset($jsonData['data']['products'])) {
                        $products = $jsonData['data']['products'];
                        
                        // Filter by category
                        if ($name !== 'all') {
                            $products = array_filter($products, function($product) use ($name) {
                                return $product['category'] === $name;
                            });
                        }
                        
                        // Further filter to ensure all required fields exist
                        $validProducts = [];
                        foreach ($products as $product) {
                            if (isset($product['id']) && !empty($product['id']) && 
                                isset($product['name']) && !empty($product['name']) &&
                                isset($product['brand']) && !empty($product['brand'])) {
                                
                                // Remove __typename fields which might cause issues
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
                                
                                $validProducts[] = $product;
                            }
                        }
                        
                        return array_values($validProducts); // Reset array keys
                    }
                }
                
                // If we get here, we couldn't load from JSON either
                return [];
            } catch (\Exception $jsonEx) {
                error_log('Error loading from data.json for category products: ' . $jsonEx->getMessage());
                return [];
            }
        }
    }
} 