<?php global $pdo;
/**
 * save_rating.php
 * Handles the AJAX POST request from the student dashboard to save a word rating.
 * Uses the PDO connection ($pdo) from config/database.php.
 */

// Include the PDO database connection object ($pdo).
require_once('./config/database.php');

// Set the response header to JSON.
header('Content-Type: application/json');

// Check the request method.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

// Collect and Sanitize Data using PHP's input filters for security.
$studentId = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
$username  = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
$word      = filter_input(INPUT_POST, 'word', FILTER_SANITIZE_SPECIAL_CHARS);
$score     = filter_input(INPUT_POST, 'score', FILTER_VALIDATE_INT);

// Check if all required fields are valid
if (!$studentId || $username === false || $word === false || $score === false) {
    echo json_encode(['success' => false, 'message' => 'Missing or invalid data provided (ID, Username, Word, Score).']);
    exit();
}

try {
    // Prepare the SQL INSERT Statement using named placeholders.
    $sql = "INSERT INTO student_ratings (student_id, username, word, score) 
            VALUES (:studentId, :username, :word, :score)";

    $stmt = $pdo->prepare($sql);

    // Execute with an array of parameters.
    $executionResult = $stmt->execute([
        ':studentId' => $studentId,
        ':username'  => $username,
        ':word'      => $word,
        ':score'     => $score
    ]);

    if ($executionResult) {
        // Success Response
        echo json_encode(['success' => true, 'message' => 'Rating saved successfully.']);
    } else {
        // Log detailed database execution failure
        $errorInfo = $stmt->errorInfo();
        error_log("SQL Execution Error: " . $errorInfo[2]);
        echo json_encode(['success' => false, 'message' => 'Failed to save rating due to database execution error.']);
    }

} catch (PDOException $e) {
    // Handle database connection or constraint errors (like Foreign Key violation)
    error_log('PDO Error (Save Rating): ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Critical database error: ' . $e->getMessage()]);
}
?>