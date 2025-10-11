<?php
session_start();
require_once 'config/database.php'; // Path correct: VERBAL/ -> VERBAL/config/database.php
global $pdo;

// 1. AUTHENTICATION & REDIRECTION
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

// 2. GET STUDENT ID
$student_id = $_GET['id'] ?? null;
if (!$student_id) {
    header("Location: teacher/teacher_dashboard.php");
    exit();
}

// --- 3. HANDLE FORM SUBMISSION (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $fullname = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $grade = (int)($_POST['grade'] ?? 0);
    $section = trim($_POST['section'] ?? '');
    $password = $_POST['password'] ?? '';

    // Simple validation
    if (empty($fullname) || empty($username) || empty($password)) {
        $error = "Full Name, Username, and Password are required.";
    } else {
        try {
            // Prepare the UPDATE query
            $sql = "UPDATE students SET 
                        fullname = :fullname, 
                        username = :username, 
                        grade = :grade, 
                        section = :section, 
                        password = :password 
                    WHERE id = :id";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'fullname' => $fullname,
                'username' => $username,
                'grade' => $grade,
                'section' => $section,
                'password' => $password,
                'id' => $student_id
            ]);

            // Success! Redirect back to the dashboard.
            header("Location: teacher/teacher_dashboard.php?status=success_edit");
            exit();

        } catch (PDOException $e) {
            $error = "Database Error: Could not update student. " . $e->getMessage();
            error_log($error);
        }
    }
}
// --- END POST HANDLING ---

// --- 4. FETCH CURRENT DATA (GET) ---
try {
    $stmt = $pdo->prepare("SELECT id, fullname, username, grade, section, password FROM students WHERE id = :id");
    $stmt->execute(['id' => $student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        header("Location: teacher/teacher_dashboard.php?status=not_found");
        exit();
    }
} catch (PDOException $e) {
    die("Database error while fetching student data.");
}

$current_data = $student;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background: linear-gradient(135deg, #a3b3fa 0%, #ccf2ff 100%);
            display: flex; justify-content: center; align-items: center; min-height: 100vh;
        }
        .edit-form-container {
            background: #ffffff; padding: 40px; border-radius: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2), 0 5px 0 #8b99df;
            width: 90%; max-width: 500px; text-align: center;
        }
        h2 {
            color: #ff6f61; font-size: 2.5em; margin-bottom: 20px;
            text-shadow: 2px 2px 0 #ffdab9; font-weight: 900;
        }
        label {
            display: block; text-align: left; margin-top: 10px; margin-bottom: 5px;
            color: #4a54ff; font-weight: 700;
        }
        input[type="text"], input[type="number"], input[type="password"] {
            width: 100%; padding: 12px; margin-bottom: 15px; border: 2px solid #ccc;
            border-radius: 15px; box-sizing: border-box; font-size: 1em;
        }
        .btn-group {
            display: flex; justify-content: space-between; margin-top: 20px;
        }
        .save-btn, .cancel-btn {
            padding: 12px 20px; border-radius: 20px; text-decoration: none;
            font-weight: bold; font-size: 1.1em; border: none; cursor: pointer;
            box-shadow: 0 4px 0 rgba(0, 0, 0, 0.2);
        }
        .save-btn {
            background: linear-gradient(145deg, #4CAF50, #388E3C);
            color: white;
            box-shadow: 0 4px 0 #1B5E20; flex-grow: 1; margin-right: 10px;
        }
        .save-btn:active { box-shadow: 0 1px 0 #1B5E20; transform: translateY(3px); }
        .cancel-btn {
            background: linear-gradient(145deg, #f44336, #d32f2f);
            color: white;
            box-shadow: 0 4px 0 #B71C1C; flex-grow: 1; margin-left: 10px;
        }
        .cancel-btn:active { box-shadow: 0 1px 0 #B71C1C; transform: translateY(3px); }
        .error { color: #f44336; margin-bottom: 15px; font-weight: 700; }
    </style>
</head>
<body>

<div class="edit-form-container">
    <h2>✏️ Edit Student: <?= htmlspecialchars($current_data['fullname']); ?></h2>

    <?php if (isset($error)): ?>
        <p class="error">⚠️ <?= htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="POST" action="edit.php?id=<?= htmlspecialchars($student_id); ?>">

        <label for="fullname">Full Name</label>
        <input type="text" id="fullname" name="fullname" value="<?= htmlspecialchars($current_data['fullname']); ?>" required>

        <label for="username">Username</label>
        <input type="text" id="username" name="username" value="<?= htmlspecialchars($current_data['username']); ?>" required>

        <label for="grade">Grade</label>
        <input type="number" id="grade" name="grade" value="<?= htmlspecialchars($current_data['grade']); ?>" required min="1" max="12">

        <label for="section">Section</label>
        <input type="text" id="section" name="section" value="<?= htmlspecialchars($current_data['section']); ?>" required>

        <label for="password">Password</label>
        <input type="text" id="password" name="password" value="<?= htmlspecialchars($current_data['password']); ?>" required>

        <div class="btn-group">
            <button type="submit" class="save-btn">✅ Save Changes</button>
            <a href="teacher/teacher_dashboard.php" class="cancel-btn">❌ Cancel</a>
        </div>
    </form>
</div>

</body>
</html>