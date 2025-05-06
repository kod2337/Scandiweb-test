<?php
/**
 * Database Seeder Script
 * 
 * This script imports data from data.json into the database.
 * Run this script from the command line: php seed.php
 */

// Autoload
require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
use Dotenv\Dotenv;

// If .env file doesn't exist, create one with default values
if (!file_exists(__DIR__ . '/.env')) {
    file_put_contents(__DIR__ . '/.env', "
DB_HOST=localhost
DB_PORT=3306
DB_USER=root
DB_PASSWORD=
DB_NAME=scandiweb_test
APP_ENV=development
    ");
    echo "Created .env file with default values.\n";
}

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Path to the JSON data file
$jsonFilePath = __DIR__ . '/../data.json';

// Import the Database Setup class
use App\Database\Setup;

// Display banner
echo "\n";
echo "======================================\n";
echo "        SCANDIWEB PROJECT SEEDER      \n";
echo "======================================\n\n";

// Initialize database setup
echo "Initializing database setup...\n";
$setup = new Setup();

// Set up the database (create database, tables, and import data)
echo "Setting up database...\n";
$setup->setup($jsonFilePath);

echo "\nDatabase seeding completed successfully!\n";
echo "You can now run your application.\n\n"; 