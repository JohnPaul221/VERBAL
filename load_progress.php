<?php
global $pdo;
header('Content-Type: application/json');
require_once('../config/database.php'); // Siguraduhing nandito ang $pdo connection

$student_id = $_GET['student_id'] ?? null;

if (!$student_id) {
    echo json_encode(['success' => false, 'message' => 'Student ID missing']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT difficulty, word_index, words_attempted, words_correct 
                           FROM student_progress WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $progress = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'progress' => $progress ?: null
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>