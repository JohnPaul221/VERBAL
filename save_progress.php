<?php
// save_progress.php
header('Content-Type: application/json');
// Path to database connection is relative to the current file (in the root)
require_once('config/database.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

// Sanitize and validate input
$student_id = filter_var($_POST['student_id'], FILTER_SANITIZE_NUMBER_INT);
$difficulty = filter_var($_POST['difficulty'], FILTER_SANITIZE_STRING);
$word_index = filter_var($_POST['word_index'], FILTER_SANITIZE_NUMBER_INT);
$words_attempted = filter_var($_POST['words_attempted'], FILTER_SANITIZE_NUMBER_INT);
$words_correct = filter_var($_POST['words_correct'], FILTER_SANITIZE_NUMBER_INT);

// UPSERT (Insert or Update) using ON DUPLICATE KEY UPDATE
$sql = "INSERT INTO student_progress 
            (student_id, difficulty, word_index, words_attempted, words_correct)
        VALUES 
            (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            difficulty = VALUES(difficulty),
            word_index = VALUES(word_index),
            words_attempted = VALUES(words_attempted),
            words_correct = VALUES(words_correct),
            last_updated = CURRENT_TIMESTAMP";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$student_id, $difficulty, $word_index, $words_attempted, $words_correct]);

    echo json_encode(['success' => true, 'message' => 'Progress state saved.']);

} catch (PDOException $e) {
    error_log("Database error in save_progress.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error saving progress.']);
}
?>