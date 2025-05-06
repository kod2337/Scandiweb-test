<?php

namespace App\GraphQL\Resolver;

use PDO;

abstract class AbstractResolver
{
    protected PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Get the database connection
     * 
     * @return PDO
     */
    protected function getDb(): PDO
    {
        return $this->db;
    }
} 