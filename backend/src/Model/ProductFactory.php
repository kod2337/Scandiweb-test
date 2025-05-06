<?php

namespace App\Model;

use PDO;
use InvalidArgumentException;

class ProductFactory
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function createProduct(string $category, array $data = []): AbstractProduct
    {
        switch ($category) {
            case 'clothes':
                return new ClothingProduct($this->db, $data);
            case 'tech':
                return new TechProduct($this->db, $data);
            default:
                throw new InvalidArgumentException("Invalid product category: {$category}");
        }
    }

    public function createFromData(array $data): AbstractProduct
    {
        $category = $data['category'] ?? 'tech';
        return $this->createProduct($category, $data);
    }
} 