<?php

namespace App\Model;

use PDO;

abstract class AbstractCategory extends AbstractModel
{
    /**
     * Get all categories
     * 
     * @return array
     */
    abstract public function getAllCategories(): array;

    /**
     * Get a category by name
     * 
     * @param string $name
     * @return array|null
     */
    abstract public function getCategoryByName(string $name): ?array;
} 