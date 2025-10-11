<?php
// load_progress.php
global $pdo;
header('Content-Type: application/json');
// Path to database connection is relative to the current file (in the root)
require_once('config/database.php');

if (!isset($_GET['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'Student ID not provided.']);
    exit();
}

$student_id = filter_var($_GET['student_id'], FILTER_SANITIZE_NUMBER_INT);

try {
    $stmt = $pdo->prepare("SELECT * FROM student_progress WHERE student_id = ? LIMIT 1");
    $stmt->execute([$student_id]);
    $progress = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($progress) {
        echo json_encode(['success' => true, 'progress' => $progress]);
    } else {
        echo json_encode(['success' => true, 'progress' => null, 'message' => 'No progress found.']);
    }

} catch (PDOException $e) {
    error_log("Database error in load_progress.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>