<?php
require_once('../config/database.php');
global $pdo;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
    $difficulty = isset($_POST['difficulty']) ? $_POST['difficulty'] : 'beginner';
    $word_index = isset($_POST['word_index']) ? (int)$_POST['word_index'] : 0;
    $words_attempted = isset($_POST['words_attempted']) ? (int)$_POST['words_attempted'] : 0;
    $words_correct = isset($_POST['words_correct']) ? (int)$_POST['words_correct'] : 0;

    if ($student_id === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid Student ID']);
        exit;
    }

    try {
        // INAYOS: Ginamit ang 'student_progress_main' at inayos ang VALUES logic
        $sql = "INSERT INTO student_progress_main (student_id, difficulty, word_index, words_attempted, words_correct) 
                VALUES (?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                word_index = VALUES(word_index), 
                words_attempted = VALUES(words_attempted), 
                words_correct = VALUES(words_correct),
                last_updated = CURRENT_TIMESTAMP";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$student_id, $difficulty, $word_index, $words_attempted, $words_correct]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        error_log("Save Progress Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Request']);
}
?>