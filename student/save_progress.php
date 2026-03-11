<?php
session_start();
require_once('../config/database.php');
global $pdo;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
    $difficulty = isset($_POST['difficulty']) ? $_POST['difficulty'] : 'beginner';
    $word_index = isset($_POST['word_index']) ? (int)$_POST['word_index'] : 0;
    $words_attempted = isset($_POST['words_attempted']) ? (int)$_POST['words_attempted'] : 0;
    $words_correct = isset($_POST['words_correct']) ? (int)$_POST['words_correct'] : 0;

    // Kunin ang Grade mula sa session para accurate ang record
    $grade = isset($_SESSION['grade']) ? (int)$_SESSION['grade'] : 0;

    if ($student_id === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid Student ID']);
        exit;
    }

    try {
        $sql = "INSERT INTO student_progress_main 
                (student_id, grade, difficulty, word_index, words_attempted, words_correct, last_updated) 
                VALUES 
                (:sid, :grade, :diff, :widx, :watt, :wcor, CURRENT_TIMESTAMP) 
                ON DUPLICATE KEY UPDATE 
                grade = VALUES(grade),
                word_index = VALUES(word_index), 
                words_attempted = VALUES(words_attempted), 
                words_correct = VALUES(words_correct),
                last_updated = CURRENT_TIMESTAMP";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'sid'   => $student_id,
            'grade' => $grade,
            'diff'  => $difficulty,
            'widx'  => $word_index,
            'watt'  => $words_attempted,
            'wcor'  => $words_correct
        ]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        error_log("Save Progress Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}
?>