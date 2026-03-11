<?php
session_start();
// Siguraduhin na tama ang path patungo sa config folder
require_once '../config/database.php';
global $pdo;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kinukuha ang data mula sa Game (Frontend)
    $student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : null;
    $username   = isset($_POST['username']) ? $_POST['username'] : '';
    $word       = isset($_POST['word']) ? trim($_POST['word']) : '';
    $score      = isset($_POST['score']) ? (int)$_POST['score'] : 0;

    // NAPAKAHALAGA: Dapat ipasa ng game kung 'beginner', 'intermediate', o 'advanced'
    $difficulty = isset($_POST['difficulty']) ? $_POST['difficulty'] : 'beginner';

    $duration   = isset($_POST['duration']) ? (float)$_POST['duration'] : 0.00;

    // Logic: 3 stars pataas ay "Correct" para sa reports
    $is_correct = ($score >= 3) ? 1 : 0;

    // Validation
    if (!$student_id || empty($word)) {
        echo json_encode(['success' => false, 'message' => 'Missing student_id or word.']);
        exit();
    }

    try {
        // Step 1: I-save ang rating sa student_ratings table
        $sql = "INSERT INTO student_ratings (student_id, username, word, score, duration, difficulty, is_correct, created_at) 
                VALUES (:student_id, :username, :word, :score, :duration, :difficulty, :is_correct, CURRENT_TIMESTAMP)";

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            ':student_id' => $student_id,
            ':username'   => $username,
            ':word'       => $word,
            ':score'      => $score,
            ':duration'   => $duration,
            ':difficulty' => $difficulty,
            ':is_correct' => $is_correct
        ]);

        if ($result) {
            // Step 2: CHECK KUNG DAPAT NA MAG-UNLOCK NG NEXT LEVEL
            // Kung Beginner ang nilaro at naka-5 stars, i-check ang total mastery
            if ($difficulty === 'beginner' && $score === 5) {
                $check_sql = "SELECT COUNT(DISTINCT word) FROM student_ratings 
                             WHERE student_id = ? AND difficulty = 'beginner' AND score = 5";
                $check_stmt = $pdo->prepare($check_sql);
                $check_stmt->execute([$student_id]);
                $count = $check_stmt->fetchColumn();

                // Kung umabot na sa 100, i-unlock ang Intermediate
                if ($count >= 100) {
                    $up_sql = "UPDATE students SET mastery_unlocked = 1 WHERE id = ?";
                    $up_stmt = $pdo->prepare($up_sql);
                    $up_stmt->execute([$student_id]);
                }
            }

            echo json_encode(['success' => true, 'message' => 'Rating saved successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to execute query.']);
        }

    } catch (PDOException $e) {
        // Debugging: Ipakita ang error kung meron
        echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Request Method.']);
}
?>