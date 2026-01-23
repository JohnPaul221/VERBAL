<?php
/**
 * config/database.php
 * Database Configuration using PDO (PHP Data Objects).
 * Establishes and provides the $pdo connection object.
 */

$host = 'localhost';
$dbname = 'verbal';
$user = 'root';
$pass = '';

$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    error_log('DB CONNECTION FAILED: ' . $e->getMessage());
    http_response_code(500);
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Critical database connection error.']);
    } else {
        die('A critical database error occurred. Please try again later.');
    }
    exit();
}