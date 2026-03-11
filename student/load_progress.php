<?php
global $pdo;
session_start();
require_once('../config/database.php');

header('Content-Type: application/json');

$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$difficulty = isset($_GET['difficulty']) ? $_GET['difficulty'] : 'beginner';

if ($student_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid Student ID']);
    exit;
}

try {
    // 1. Mastery Status mula sa students table
    // Tinitingnan kung in-unlock na ng teacher ang Advanced Mode
    $stmtUser = $pdo->prepare("SELECT mastery_unlocked FROM students WHERE id = ?");
    $stmtUser->execute([$student_id]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
    $mastery_status = $user ? (int)$user['mastery_unlocked'] : 0;

    // 2. Progress mula sa student_progress_main
    // Para sa 0/10 na progress bar at accuracy stats
    $stmtProgress = $pdo->prepare("SELECT word_index, words_attempted, words_correct FROM student_progress_main WHERE student_id = ? AND difficulty = ?");
    $stmtProgress->execute([$student_id, $difficulty]);
    $progress = $stmtProgress->fetch(PDO::FETCH_ASSOC);

    // 3. Mastered Words List (ANG DAGDAG NA FIX)
    // Kinukuha lahat ng unique na salita na nakakuha ng perfect 5 score
    $stmtMastery = $pdo->prepare("SELECT DISTINCT word FROM student_ratings WHERE student_id = ? AND score = 5");
    $stmtMastery->execute([$student_id]);
    $masteredResults = $stmtMastery->fetchAll(PDO::FETCH_ASSOC);

    $mastered_list = [];
    foreach ($masteredResults as $row) {
        // Ginagawang lowercase para mag-match sa JavaScript wordBank keys
        $mastered_list[] = strtolower($row['word']);
    }

    // 4. I-return ang lahat ng data sa JavaScript
    echo json_encode([
        'success' => true,
        'mastery_unlocked' => $mastery_status,
        'mastered_list' => $mastered_list, // Ito ang magpapagana sa Advanced Mode sentences
        'progress' => [
            'word_index' => $progress ? (int)$progress['word_index'] : 0,
            'words_attempted' => $progress ? (int)$progress['words_attempted'] : 0,
            'words_correct' => $progress ? (int)$progress['words_correct'] : 0
        ]
    ]);

} catch (PDOException $e) {
    // Mag-log ng error sa server para sa troubleshooting
    error_log("Load Progress Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>