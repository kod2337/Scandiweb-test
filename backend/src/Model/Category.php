<?php

namespace App\Model;

use PDO;

class Category extends AbstractCategory
{
    /**
     * Get all categories
     * 
     * @return array
     */
    public function getAllCategories(): array
    {
        try {
            $stmt = $this->getDb()->prepare("SELECT * FROM categories ORDER BY name");
            $stmt->execute();
            
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Ensure "all" category exists
            $hasAllCategory = false;
            foreach ($categories as $category) {
                if ($category['name'] === 'all') {
                    $hasAllCategory = true;
                    break;
                }
            }
            
            if (!$hasAllCategory) {
                // Add the "all" category if it doesn't exist
                $categories[] = ['name' => 'all'];
            }
            
            return $categories;
        } catch (\PDOException $e) {
            error_log("Database error in getAllCategories: " . $e->getMessage());
            // Return a minimal set of categories to prevent app failure
            return [['name' => 'all']];
        }
    }

    /**
     * Get a category by name
     * 
     * @param string $name
     * @return array|null
     */
    public function getCategoryByName(string $name): ?array
    {
        // Special handling for "all" category
        if ($name === 'all') {
            return ['name' => 'all'];
        }
        
        try {
            $stmt = $this->getDb()->prepare("SELECT * FROM categories WHERE name = :name");
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ?: null;
        } catch (\PDOException $e) {
            error_log("Database error in getCategoryByName: " . $e->getMessage());
            return null;
        }
    }
} 