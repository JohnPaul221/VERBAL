<?php
session_start();
require_once('../config/database.php');
global $pdo; // Siguraduhing PDO ang gamit mo rito gaya ng sa ibang files

header('Content-Type: application/json');

// 1. Security Check - Siguraduhing may student_id na pinasa
if (!isset($_GET['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'No student ID provided']);
    exit();
}

$student_id = intval($_GET['student_id']);

try {
    // 2. Kunin ang mga salitang may 5-star rating (Mastered)
    // Ginagamit ang DISTINCT para walang duplicate na salita
    $query = "SELECT DISTINCT word FROM student_ratings WHERE student_id = ? AND score = 5";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$student_id]);
    $masteredResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $masteredWords = [];
    foreach ($masteredResults as $row) {
        $masteredWords[] = strtolower($row['word']);
    }

    /**
     * 3. Word Bank Definition
     * Dito kinukuha ang example sentences para sa Advanced/Hard Mode
     */
    $wordBank = [
        "cat" => "The cat is fat.",
        "rat" => "The rat is big.",
        "sun" => "The sun is up.",
        "dad" => "I love my dad.",
        "pen" => "The pen is red.",
        "bag" => "My bag is new.",
        "cup" => "The cup is hot.",
        "sit" => "Sit on the mat.",
        "fan" => "The fan is on.",
        "box" => "It is a big box.",
        "play" => "I want to play.",
        "jump" => "The boy can jump.",
        "happy" => "She is very happy.",
        "green" => "The leaf is green.",
        "water" => "Drink your water.",
        "school" => "I go to school.",
        "friend" => "You are my friend.",
        "house" => "This is our house.",
        "books" => "I read my books.",
        "clean" => "Keep the room clean."
    ];

    // 4. I-filter ang mastered words para makuha ang kanilang sentences
    $finalList = [];
    foreach ($masteredWords as $word) {
        if (isset($wordBank[$word])) {
            $finalList[] = [
                "word" => $word,
                "example_sentence" => $wordBank[$word]
            ];
        }
    }

    // 5. I-return ang listahan sa Student Dashboard
    echo json_encode($finalList);

} catch (PDOException $e) {
    error_log("Mastery Fetch Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>