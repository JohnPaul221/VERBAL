<?php
/**
 * config/database.php
 * Database Configuration using PDO (PHP Data Objects).
 * Establishes and provides the $pdo connection object.
 */

$host = 'localhost';
$dbname = 'verbal';
$user = 'root';
// IMPORTANT: Use your actual, strong password here
$pass = 'diddies4evah_31';

$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
$options = [
    // Throw exceptions on errors, which is necessary for try/catch blocks
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    // Fetch results as associative arrays by default
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    // Disable emulation for better performance and security with prepared statements
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // The connection object $pdo is instantiated here and is available globally
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Log the error and display a friendly message
    error_log('DB CONNECTION FAILED: ' . $e->getMessage());
    http_response_code(500);
    die('A critical database error occurred. Please try again later.');
}
?>