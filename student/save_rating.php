<?php
session_start();
require_once('../config/database.php');
global $pdo;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kinukuha ang mga data mula sa POST request
    $student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : null;
    $username   = isset($_POST['username']) ? $_POST['username'] : '';
    $word       = isset($_POST['word']) ? trim($_POST['word']) : '';
    $score      = isset($_POST['score']) ? (int)$_POST['score'] : 0;

    // Kinukuha ang duration (seconds) mula sa mic timer
    $duration   = isset($_POST['duration']) ? (float)$_POST['duration'] : 0.00;

    // Validation: Siguraduhing may student_id at word
    if (!$student_id || empty($word)) {
        echo json_encode(['success' => false, 'message' => 'Missing required data.']);
        exit();
    }

    try {
        // SQL query na may kasamang duration column
        $sql = "INSERT INTO student_ratings (student_id, username, word, score, duration, created_at) 
                VALUES (:student_id, :username, :word, :score, :duration, CURRENT_TIMESTAMP)";

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            ':student_id' => $student_id,
            ':username'   => $username,
            ':word'       => $word,
            ':score'      => $score,
            ':duration'   => $duration
        ]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Rating and duration saved!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save rating.']);
        }

    } catch (PDOException $e) {
        // Nagla-log ng error sa server para sa debugging
        error_log("Rating Save Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    // Kapag hindi POST ang request
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>