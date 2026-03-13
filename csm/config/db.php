<?php
require __DIR__ . '/../vendor/autoload.php'; // Adjust path to Composer autoload

// Load .env from the same folder as db.php (config folder)
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Get credentials from environment
$host = $_ENV['DB_HOST'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];
$dbname = $_ENV['DB_NAME'];

// Connect to the database
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
