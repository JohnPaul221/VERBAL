<?php
/**
 * Database Configuration using PDO (PHP Data Objects)
 */

$host = 'localhost';
$dbname = 'verbal';
$user = 'root';
$pass = 'diddies4evah_31'; // Your strong password

$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
$options = [
    // 🔥 FIX: Must use the PDO:: syntax for these constants
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // The connection object $pdo is instantiated here
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    error_log('DB CONNECTION FAILED: ' . $e->getMessage());
    http_response_code(500);
    die('A critical database error occurred. Please try again later.');
}

// The variable $pdo is now available for use in login.php
?>