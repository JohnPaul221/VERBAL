<?php
// Ensure this script uses the same connection setup as the leaderboard
global $conn;
require_once 'config/database.php';

header('Content-Type: application/json');

// Check if the request is a POST request
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    $conn->close();
    exit();
}

// Collect and Sanitize Data from the AJAX POST request
$student_id = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
$username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
$word = filter_input(INPUT_POST, 'word', FILTER_SANITIZE_STRING);
$score = filter_input(INPUT_POST, 'score', FILTER_VALIDATE_INT);

// Basic validation
if (!$student_id || !$username || !$word || $score === null) {
    echo json_encode(['success' => false, 'message' => 'Missing or invalid data provided.']);
    $conn->close();
    exit();
}

// Prepare and Execute the SQL INSERT statement using prepared statements for security
$sql = "INSERT INTO student_ratings (student_id, username, word, score) VALUES (?, ?, ?, ?)";

if ($stmt = $conn->prepare($sql)) {
    // Bind parameters: 'issi' stands for integer, string, string, integer
    $stmt->bind_param("issi", $student_id, $username, $word, $score);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Rating saved successfully.']);
    } else {
        // Log the actual MySQL error for debugging
        echo json_encode(['success' => false, 'message' => 'Failed to save rating: ' . $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'SQL prepare failed: ' . $conn->error]);
}

$conn->close();
?>