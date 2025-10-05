<?php
/**
 * Database Configuration
 */

$servername = 'localhost';
$username   = 'root';
$password   = 'diddies4evah_31';
$dbname     = 'verbal';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die('DB CONNECTION FAILED: ' . $conn->connect_error);
}