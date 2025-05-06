<?php
// Script to fix non-nullable fields in the database

// Database connection
$host = 'localhost';
$port = '3306';
$dbname = 'scandiweb_test';
$user = 'root';
$pass = '';

try {
    // Connect to database
    $db = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname}",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "Connected to database successfully.\n";
    
    // 1. Fix categories - make sure all categories have a name
    echo "Checking categories...\n";
    $stmt = $db->query("SELECT * FROM categories");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($categories as $category) {
        if (empty($category['name'])) {
            $id = $category['id'];
            echo "Fixing category with ID $id - adding default name.\n";
            $updateStmt = $db->prepare("UPDATE categories SET name = :name WHERE id = :id");
            $name = "category-$id";
            $updateStmt->bindParam(':name', $name);
            $updateStmt->bindParam(':id', $id);
            $updateStmt->execute();
        }
    }
    
    // Make sure 'all' category exists
    $stmt = $db->prepare("SELECT * FROM categories WHERE name = :name");
    $name = "all";
    $stmt->bindParam(':name', $name);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        echo "Adding 'all' category.\n";
        $stmt = $db->prepare("INSERT INTO categories (name) VALUES (:name)");
        $stmt->bindParam(':name', $name);
        $stmt->execute();
    }
    
    // 2. Fix products - make sure all products have an ID, name, and brand
    echo "Checking products...\n";
    $stmt = $db->query("SELECT * FROM products");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($products as $product) {
        $needsUpdate = false;
        $updates = [];
        $params = [];
        
        if (empty($product['id'])) {
            echo "Product with missing ID found - creating new ID.\n";
            $newId = 'product-' . uniqid();
            $updates[] = "id = :id";
            $params[':id'] = $newId;
            $needsUpdate = true;
        } else {
            $params[':id'] = $product['id'];
        }
        
        if (empty($product['name'])) {
            echo "Product with ID {$product['id']} missing name - adding default name.\n";
            $updates[] = "name = :name";
            $params[':name'] = "Untitled Product";
            $needsUpdate = true;
        }
        
        if (empty($product['brand'])) {
            echo "Product with ID {$product['id']} missing brand - adding default brand.\n";
            $updates[] = "brand = :brand";
            $params[':brand'] = "Unknown Brand";
            $needsUpdate = true;
        }
        
        if (empty($product['category'])) {
            echo "Product with ID {$product['id']} missing category - setting to 'all'.\n";
            $updates[] = "category = :category";
            $params[':category'] = "all";
            $needsUpdate = true;
        }
        
        if ($needsUpdate) {
            $updateSql = "UPDATE products SET " . implode(", ", $updates) . " WHERE id = :id";
            $updateStmt = $db->prepare($updateSql);
            $updateStmt->execute($params);
        }
    }
    
    echo "Database fix completed successfully!\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
} 