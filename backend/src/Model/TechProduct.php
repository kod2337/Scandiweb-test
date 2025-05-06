<?php

namespace App\Model;

use PDO;

class TechProduct extends AbstractProduct
{
    private array $data;

    public function __construct(PDO $db, array $data = [])
    {
        parent::__construct($db);
        $this->data = $data;
    }

    public function getAllProducts(): array
    {
        return $this->data;
    }

    public function getProductsByCategory(string $category): array
    {
        if ($category === 'all' || $category === 'tech') {
            return $this->data;
        }
        return [];
    }

    public function getProductById(string $id): ?array
    {
        foreach ($this->data as $product) {
            if ($product['id'] === $id) {
                return $product;
            }
        }
        return null;
    }

    public function getProductAttributes(string $productId): array
    {
        $product = $this->getProductById($productId);
        return $product['attributes'] ?? [];
    }

    public function getProductPrices(string $productId): array
    {
        $product = $this->getProductById($productId);
        return $product['prices'] ?? [];
    }

    public function getProductGallery(string $productId): array
    {
        $product = $this->getProductById($productId);
        return $product['gallery'] ?? [];
    }
} 