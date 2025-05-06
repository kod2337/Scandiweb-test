<?php

namespace App\Database;

use PDO;
use PDOException;
use Dotenv\Dotenv;

class Setup
{
    private PDO $pdo;
    
    public function __construct()
    {
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $user = $_ENV['DB_USER'] ?? 'root';
        $pass = $_ENV['DB_PASSWORD'] ?? '';
        
        try {
            $this->pdo = new PDO(
                "mysql:host={$host};port={$port}",
                $user,
                $pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            echo "Connected to MySQL database.\n";
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage() . "\n");
        }
    }
    
    public function createDatabase(): void
    {
        $dbName = $_ENV['DB_NAME'] ?? 'scandiweb_test';
        
        try {
            $this->pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}`");
            $this->pdo->exec("USE `{$dbName}`");
            echo "Database '{$dbName}' created or already exists.\n";
        } catch (PDOException $e) {
            die("Failed to create database: " . $e->getMessage() . "\n");
        }
    }
    
    public function createTables(): void
    {
        try {
            // Create Categories table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `categories` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `name` VARCHAR(255) NOT NULL UNIQUE,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            
            // Create Products table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `products` (
                    `id` VARCHAR(255) PRIMARY KEY,
                    `name` VARCHAR(255) NOT NULL,
                    `description` TEXT,
                    `inStock` BOOLEAN DEFAULT TRUE,
                    `category` VARCHAR(255) NOT NULL,
                    `brand` VARCHAR(255) NOT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (`category`) REFERENCES `categories`(`name`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            
            // Create Product Gallery table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `product_gallery` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `product_id` VARCHAR(255) NOT NULL,
                    `image_url` TEXT NOT NULL,
                    `display_order` INT NOT NULL DEFAULT 0,
                    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            
            // Create Product Prices table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `product_prices` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `product_id` VARCHAR(255) NOT NULL,
                    `amount` DECIMAL(10, 2) NOT NULL,
                    `currency_label` VARCHAR(10) NOT NULL,
                    `currency_symbol` VARCHAR(5) NOT NULL,
                    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            
            // Create Attribute Sets table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `attribute_sets` (
                    `id` VARCHAR(255) PRIMARY KEY,
                    `name` VARCHAR(255) NOT NULL,
                    `type` VARCHAR(50) NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            
            // Create Attribute Items table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `attribute_items` (
                    `id` VARCHAR(255) NOT NULL,
                    `attribute_id` VARCHAR(255) NOT NULL,
                    `displayValue` VARCHAR(255) NOT NULL,
                    `value` VARCHAR(255) NOT NULL,
                    PRIMARY KEY (`id`, `attribute_id`),
                    FOREIGN KEY (`attribute_id`) REFERENCES `attribute_sets`(`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            
            // Create Product Attributes junction table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `product_attributes` (
                    `product_id` VARCHAR(255) NOT NULL,
                    `attribute_id` VARCHAR(255) NOT NULL,
                    PRIMARY KEY (`product_id`, `attribute_id`),
                    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
                    FOREIGN KEY (`attribute_id`) REFERENCES `attribute_sets`(`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            
            // Create Orders table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `orders` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `customer_name` VARCHAR(255),
                    `customer_email` VARCHAR(255),
                    `total` DECIMAL(10, 2) NOT NULL,
                    `currency` VARCHAR(10) NOT NULL,
                    `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            
            // Create Order Items table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `order_items` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `order_id` INT NOT NULL,
                    `product_id` VARCHAR(255) NOT NULL,
                    `quantity` INT NOT NULL,
                    `unit_price` DECIMAL(10, 2) NOT NULL,
                    `attributes` JSON,
                    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
                    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            
            echo "Database tables created successfully.\n";
        } catch (PDOException $e) {
            die("Failed to create tables: " . $e->getMessage() . "\n");
        }
    }
    
    public function importData(string $jsonFilePath): void
    {
        if (!file_exists($jsonFilePath)) {
            die("JSON data file not found: {$jsonFilePath}\n");
        }
        
        $jsonData = file_get_contents($jsonFilePath);
        $data = json_decode($jsonData, true);
        
        if (!$data || !isset($data['data'])) {
            die("Invalid JSON data format\n");
        }
        
        $data = $data['data'];
        
        try {
            // Begin transaction
            $this->pdo->beginTransaction();
            
            // Import categories
            $categoryStmt = $this->pdo->prepare("
                INSERT IGNORE INTO `categories` (`name`) VALUES (?)
            ");
            
            echo "Importing categories...\n";
            foreach ($data['categories'] as $category) {
                $categoryStmt->execute([$category['name']]);
            }
            
            // Import products with related data
            $productStmt = $this->pdo->prepare("
                INSERT INTO `products` 
                (`id`, `name`, `description`, `inStock`, `category`, `brand`) 
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                `name` = VALUES(`name`),
                `description` = VALUES(`description`),
                `inStock` = VALUES(`inStock`),
                `category` = VALUES(`category`),
                `brand` = VALUES(`brand`)
            ");
            
            $galleryStmt = $this->pdo->prepare("
                INSERT INTO `product_gallery` 
                (`product_id`, `image_url`, `display_order`) 
                VALUES (?, ?, ?)
            ");
            
            $priceStmt = $this->pdo->prepare("
                INSERT INTO `product_prices` 
                (`product_id`, `amount`, `currency_label`, `currency_symbol`) 
                VALUES (?, ?, ?, ?)
            ");
            
            $attrSetStmt = $this->pdo->prepare("
                INSERT IGNORE INTO `attribute_sets` 
                (`id`, `name`, `type`) 
                VALUES (?, ?, ?)
            ");
            
            $attrItemStmt = $this->pdo->prepare("
                INSERT IGNORE INTO `attribute_items` 
                (`id`, `attribute_id`, `displayValue`, `value`) 
                VALUES (?, ?, ?, ?)
            ");
            
            $prodAttrStmt = $this->pdo->prepare("
                INSERT IGNORE INTO `product_attributes` 
                (`product_id`, `attribute_id`) 
                VALUES (?, ?)
            ");
            
            // Clear existing data for products
            $this->pdo->exec("DELETE FROM `product_gallery`");
            $this->pdo->exec("DELETE FROM `product_prices`");
            $this->pdo->exec("DELETE FROM `product_attributes`");
            
            echo "Importing products and related data...\n";
            foreach ($data['products'] as $product) {
                // Insert product
                $productStmt->execute([
                    $product['id'],
                    $product['name'],
                    $product['description'],
                    $product['inStock'] ? 1 : 0,
                    $product['category'],
                    $product['brand']
                ]);
                
                // Insert gallery images
                foreach ($product['gallery'] as $index => $imageUrl) {
                    $galleryStmt->execute([$product['id'], $imageUrl, $index]);
                }
                
                // Insert prices
                foreach ($product['prices'] as $price) {
                    $priceStmt->execute([
                        $product['id'],
                        $price['amount'],
                        $price['currency']['label'],
                        $price['currency']['symbol']
                    ]);
                }
                
                // Insert attributes
                if (isset($product['attributes']) && is_array($product['attributes'])) {
                    foreach ($product['attributes'] as $attrSet) {
                        // Insert attribute set
                        $attrSetStmt->execute([
                            $attrSet['id'],
                            $attrSet['name'],
                            $attrSet['type']
                        ]);
                        
                        // Link product to attribute
                        $prodAttrStmt->execute([
                            $product['id'],
                            $attrSet['id']
                        ]);
                        
                        // Insert attribute items
                        foreach ($attrSet['items'] as $item) {
                            $attrItemStmt->execute([
                                $item['id'],
                                $attrSet['id'],
                                $item['displayValue'],
                                $item['value']
                            ]);
                        }
                    }
                }
            }
            
            // Commit transaction
            $this->pdo->commit();
            echo "Data imported successfully.\n";
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            die("Failed to import data: " . $e->getMessage() . "\n");
        }
    }
    
    public function setup(string $jsonFilePath): void
    {
        $this->createDatabase();
        $this->createTables();
        $this->importData($jsonFilePath);
        echo "Database setup completed successfully.\n";
    }
}

// Setup the database when this file is run directly
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    require_once __DIR__ . '/../../vendor/autoload.php';
    
    // Load environment variables
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../..');
    $dotenv->load();
    
    $setup = new Setup();
    $setup->setup(__DIR__ . '/../../data.json');
} 