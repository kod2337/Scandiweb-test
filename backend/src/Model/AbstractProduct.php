<?php

namespace App\Model;

use PDO;

abstract class AbstractProduct extends AbstractModel
{
    /**
     * Get all products
     * 
     * @return array
     */
    abstract public function getAllProducts(): array;

    /**
     * Get products by category
     * 
     * @param string $category
     * @return array
     */
    abstract public function getProductsByCategory(string $category): array;

    /**
     * Get a product by ID
     * 
     * @param string $id
     * @return array|null
     */
    abstract public function getProductById(string $id): ?array;

    /**
     * Get product attributes
     * 
     * @param string $productId
     * @return array
     */
    abstract public function getProductAttributes(string $productId): array;

    /**
     * Get product prices
     * 
     * @param string $productId
     * @return array
     */
    abstract public function getProductPrices(string $productId): array;

    /**
     * Get product gallery
     * 
     * @param string $productId
     * @return array
     */
    abstract public function getProductGallery(string $productId): array;
} 