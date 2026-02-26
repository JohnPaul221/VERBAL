<?php
session_start();
require_once('../config/database.php');
global $pdo;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : null;
    $username   = isset($_POST['username']) ? $_POST['username'] : '';
    $word       = isset($_POST['word']) ? trim($_POST['word']) : '';
    $score      = isset($_POST['score']) ? (int)$_POST['score'] : 0;

    if (!$student_id || empty($word)) {
        echo json_encode(['success' => false, 'message' => 'Missing required data.']);
        exit();
    }

    try {
        $sql = "INSERT INTO student_ratings (student_id, username, word, score, created_at) 
                VALUES (:student_id, :username, :word, :score, CURRENT_TIMESTAMP)";

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            ':student_id' => $student_id,
            ':username'   => $username,
            ':word'       => $word,
            ':score'      => $score
        ]);

        echo json_encode(['success' => true, 'message' => 'Rating saved!']);

    } catch (PDOException $e) {
        error_log("Rating Save Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>