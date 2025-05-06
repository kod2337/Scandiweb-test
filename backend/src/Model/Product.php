<?php

namespace App\Model;

use PDO;

class Product extends AbstractProduct
{
    /**
     * Get all products
     * 
     * @return array
     */
    public function getAllProducts(): array
    {
        try {
            $stmt = $this->getDb()->prepare("SELECT * FROM products");
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log('Database error in getAllProducts: ' . $e->getMessage());
            throw new \Exception('Error fetching all products: ' . $e->getMessage());
        } catch (\Exception $e) {
            error_log('General error in getAllProducts: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get products by category
     * 
     * @param string $category
     * @return array
     */
    public function getProductsByCategory(string $category): array
    {
        try {
            if ($category === 'all') {
                return $this->getAllProducts();
            }
            
            $stmt = $this->getDb()->prepare("SELECT * FROM products WHERE category = :category");
            $stmt->bindParam(':category', $category, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log('Database error in getProductsByCategory: ' . $e->getMessage());
            throw new \Exception('Error fetching products: ' . $e->getMessage());
        } catch (\Exception $e) {
            error_log('General error in getProductsByCategory: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get a product by ID
     * 
     * @param string $id
     * @return array|null
     */
    public function getProductById(string $id): ?array
    {
        $stmt = $this->getDb()->prepare("SELECT * FROM products WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_STR);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    /**
     * Get product attributes
     * 
     * @param string $productId
     * @return array
     */
    public function getProductAttributes(string $productId): array
    {
        $stmt = $this->getDb()->prepare("
            SELECT a.*, ai.displayValue, ai.value, ai.id as itemId
            FROM product_attributes pa
            JOIN attribute_sets a ON pa.attribute_id = a.id
            JOIN attribute_items ai ON ai.attribute_id = a.id
            WHERE pa.product_id = :productId
            ORDER BY a.id, ai.id
        ");
        $stmt->bindParam(':productId', $productId, PDO::PARAM_STR);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Return empty array if no attributes found
        if (empty($results)) {
            return [];
        }
        
        // Group attributes by ID
        $attributeSets = [];
        foreach ($results as $row) {
            $attributeId = $row['id'];
            
            if (!isset($attributeSets[$attributeId])) {
                $attributeSets[$attributeId] = [
                    'id' => $attributeId,
                    'name' => $row['name'],
                    'type' => $row['type'],
                    'items' => []
                ];
            }
            
            $attributeSets[$attributeId]['items'][] = [
                'id' => $row['itemId'],
                'displayValue' => $row['displayValue'],
                'value' => $row['value']
            ];
        }
        
        return array_values($attributeSets);
    }

    /**
     * Get product prices
     * 
     * @param string $productId
     * @return array
     */
    public function getProductPrices(string $productId): array
    {
        $stmt = $this->getDb()->prepare("
            SELECT amount, currency_label, currency_symbol
            FROM product_prices
            WHERE product_id = :productId
        ");
        $stmt->bindParam(':productId', $productId, PDO::PARAM_STR);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Return empty array if no prices found
        if (empty($results)) {
            return [[
                'amount' => 0,
                'currency' => [
                    'label' => 'USD',
                    'symbol' => '$'
                ]
            ]];
        }
        
        return array_map(function ($row) {
            return [
                'amount' => (float) $row['amount'],
                'currency' => [
                    'label' => $row['currency_label'],
                    'symbol' => $row['currency_symbol']
                ]
            ];
        }, $results);
    }

    /**
     * Get product gallery
     * 
     * @param string $productId
     * @return array
     */
    public function getProductGallery(string $productId): array
    {
        $stmt = $this->getDb()->prepare("
            SELECT image_url
            FROM product_gallery
            WHERE product_id = :productId
            ORDER BY display_order
        ");
        $stmt->bindParam(':productId', $productId, PDO::PARAM_STR);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Return placeholder if no gallery images found
        if (empty($results)) {
            return ['https://via.placeholder.com/300x300?text=No+Image'];
        }
        
        return array_column($results, 'image_url');
    }
} 