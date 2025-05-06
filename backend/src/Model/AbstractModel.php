<?php

namespace App\Model;

use PDO;

abstract class AbstractModel
{
    protected PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Get the database connection, validating it first
     * 
     * @return PDO
     */
    protected function getDb(): PDO
    {
        try {
            // Test the connection with a simple query
            $this->db->query("SELECT 1");
            return $this->db;
        } catch (\PDOException $e) {
            // Log the error
            error_log('Database connection error in getDb: ' . $e->getMessage());
            
            // Try to reconnect
            try {
                $host = $_ENV['DB_HOST'] ?? 'localhost';
                $port = $_ENV['DB_PORT'] ?? '3306';
                $dbname = $_ENV['DB_NAME'] ?? 'scandiweb_test';
                $user = $_ENV['DB_USER'] ?? 'root';
                $pass = $_ENV['DB_PASSWORD'] ?? '';
                
                $this->db = new PDO(
                    "mysql:host={$host};port={$port};dbname={$dbname}",
                    $user,
                    $pass,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                
                return $this->db;
            } catch (\PDOException $reconnectEx) {
                error_log('Failed to reconnect to database: ' . $reconnectEx->getMessage());
                throw $reconnectEx;
            }
        }
    }
    
    /**
     * Debug method to test database connection
     */
    public function debugConnection(): array
    {
        try {
            $stmt = $this->db->query("SELECT 1");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'message' => 'Database connection is working',
                'result' => $result
            ];
        } catch (\PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database connection error: ' . $e->getMessage(),
                'code' => $e->getCode()
            ];
        }
    }
} 