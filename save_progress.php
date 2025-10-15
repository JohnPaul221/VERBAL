<?php
global $pdo;
header('Content-Type: application/json');
// Assuming save_progress.php is in the root directory, sibling to 'config'
require_once('./config/database.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

// Sanitize and validate input
$student_id = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
$difficulty = filter_input(INPUT_POST, 'difficulty', FILTER_SANITIZE_SPECIAL_CHARS);
$word_index = filter_input(INPUT_POST, 'word_index', FILTER_VALIDATE_INT);
$words_attempted = filter_input(INPUT_POST, 'words_attempted', FILTER_VALIDATE_INT);
$words_correct = filter_input(INPUT_POST, 'words_correct', FILTER_VALIDATE_INT);

// Use named parameters for clarity and safety with ON DUPLICATE KEY UPDATE
$sql = "INSERT INTO student_progress 
            (student_id, difficulty, word_index, words_attempted, words_correct)
        VALUES 
            (:student_id, :difficulty, :word_index, :words_attempted, :words_correct)
        ON DUPLICATE KEY UPDATE
            word_index = VALUES(word_index),
            words_attempted = VALUES(words_attempted),
            words_correct = VALUES(words_correct),
            last_updated = CURRENT_TIMESTAMP"; // <--- FIX: Added last_updated update

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':student_id' => $student_id,
        ':difficulty' => $difficulty,
        ':word_index' => $word_index,
        ':words_attempted' => $words_attempted,
        ':words_correct' => $words_correct
    ]);

    echo json_encode(['success' => true, 'message' => 'Progress state saved.']);

} catch (PDOException $e) {
    error_log("Database error in save_progress.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error saving progress.']);
}