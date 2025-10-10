<?php
/**
 * Database Configuration
 */

$servername = 'localhost';
$username   = 'root';
$password   = 'diddies4evah_31'; // Strong password - Good for security!
$dbname     = 'verbal';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    // This will stop the script immediately if the connection fails
    die('DB CONNECTION FAILED: ' . $conn->connect_error);
}
// ------------------------------------------------------------------
// SUGGESTED ADDITIONS START HERE
// ------------------------------------------------------------------

// 1. Set Character Set: Ensures data integrity for all languages/emojis.
// It prevents issues with special characters not displaying correctly.
if ($conn) {
    $conn->set_charset("utf8mb4");
}

// 2. Hide Connection Error in Production:
// The current 'die()' exposes database details. For production, you'd hide this.
// For example:
/*
if ($conn->connect_error) {
    error_log('DB CONNECTION FAILED: ' . $conn->connect_error);
    http_response_code(500);
    die('An application error occurred. Please try again later.');
}
*/
?>