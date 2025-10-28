<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}
require_once "../config/database.php";

global $pdo;

const MIN_PASSWORD_LENGTH = 8;
$message = '';
$message_type = '';
$default_grade = $_SESSION['grade'] ?? '';
$default_section = $_SESSION['section'] ?? '';
if (empty($default_grade) || empty($default_section)) {
    try {
        $sql_teacher_info = "SELECT grade, section FROM teachers WHERE id = ?";
        $stmt_teacher_info = $pdo->prepare($sql_teacher_info);
        $stmt_teacher_info->execute([$_SESSION['user_id']]);
        $teacher_info = $stmt_teacher_info->fetch(PDO::FETCH_ASSOC);

        if ($teacher_info) {
            $default_grade = $teacher_info['grade'];
            $default_section = $teacher_info['section'];
            $_SESSION['grade'] = $default_grade;
            $_SESSION['section'] = $default_section;
        }
    } catch (PDOException $e) {
        error_log("Teacher Info Fetch Error: " . $e->getMessage());
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role     = 'student';
    $fullname = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $grade    = trim($_POST['grade'] ?? $default_grade);
    $section  = trim($_POST['section'] ?? $default_section);

    if ($grade !== $default_grade || $section !== $default_section) {
        $message = "❌ Error: You can only add students to your assigned class (Grade {$default_grade} - Section {$default_section}).";
        $message_type = 'error';
    } elseif (empty($fullname) || empty($username) || empty($grade) || empty(trim($section)) || empty($password)) {
        $message = "❌ Please fill out all required fields, including the password.";
        $message_type = 'error';
    } elseif (strlen($password) < MIN_PASSWORD_LENGTH) {
        $message = "❌ Password must be at least " . MIN_PASSWORD_LENGTH . " characters long.";
        $message_type = 'error';
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        if ($hashedPassword === false) {
            $message = "❌ Registration failed. Password hashing failed.";
            $message_type = 'error';
        } else {
            try {
                $table = "students";

                $sql = "INSERT INTO {$table} (fullname, username, grade, section, password) VALUES (?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);

                $success = $stmt->execute([$fullname, $username, $grade, $section, $hashedPassword]);

                if ($success) {
                    $_SESSION['status_message'] = "✅ **Student** '{$fullname}' registered successfully for your class!";

                    // FIX: Corrected path to teacher_dashboard.php (upload/ -> teacher/)
                    header("Location: ../teacher/teacher_dashboard.php");
                    exit();
                } else {
                    $message = "❌ Registration failed. A server error occurred.";
                    $message_type = 'error';
                }

            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $message = "❌ Error: The username '{$username}' is already taken.";
                } else {
                    error_log("Add Student Database Error: " . $e->getMessage());
                    $message = "❌ Registration failed due to a database error. (Code: " . $e->getCode() . ")";
                }
                $message_type = 'error';
            } catch (Exception $e) {
                error_log("Add Student Logic Error: " . $e->getMessage());
                $message = "❌ Registration failed due to a system error.";
                $message_type = 'error';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student - Teacher Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary-blue: #01579b;
            --light-blue: #4fc3f7;
            --yellow-accent: #ffb703;
            --yellow-hover: #ff9f1c;
            --success-color: #0f5132;
            --success-bg: #d1e7dd;
            --error-color: #842029;
            --error-bg: #f8d7da;
        }

        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            font-family: 'Comic Sans MS', cursive, sans-serif;
            background: linear-gradient(to bottom, #87ceeb, #ccf2ff);
            overflow: auto;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            background: rgba(255, 255, 255, 0.98);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.15);
            width: 380px;
            max-width: 90%;
            text-align: center;
            z-index: 10;
            animation: pop 0.8s ease;
            margin: 20px auto;
        }
        @keyframes pop {
            0% { transform: scale(0.8); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        h2 { color: var(--primary-blue); margin-bottom: 25px; }

        .input-group {
            position: relative;
            margin: 15px 0;
            width: 100%;
        }

        input[type="text"], input[type="password"], select {
            width: 100%;
            padding: 12px 12px 12px 45px;
            border: 2px solid var(--light-blue);
            border-radius: 15px;
            font-size: 16px;
            box-sizing: border-box;
            background: #fff;
            transition: border-color 0.3s;
        }
        input:focus, select:focus {
            border-color: var(--yellow-accent);
            outline: none;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--light-blue);
            font-size: 18px;
            pointer-events: none;
            z-index: 1;
        }
        .readonly-input {
            background-color: #f0f0f0;
            cursor: not-allowed;
            color: #777;
        }

        input[type="submit"] {
            background: var(--yellow-accent);
            border: none;
            padding: 14px 20px;
            border-radius: 15px;
            font-size: 18px;
            cursor: pointer;
            color: #fff;
            font-weight: bold;
            width: 100%;
            margin-top: 25px;
            transition: background 0.3s, transform 0.1s;
            box-shadow: 0 4px 0 var(--yellow-hover);
        }
        input[type="submit"]:active {
            transform: translateY(2px);
            box-shadow: 0 2px 0 var(--yellow-hover);
        }
        input[type="submit"]:hover { background: var(--yellow-hover); }

        .message { font-weight: bold; padding: 10px; border-radius: 10px; margin: 10px 0; border: 1px solid; text-align: left;}
        .message.error { color: var(--error-color); background: var(--error-bg); border-color: #f5c2c7; }
        .message.success { color: var(--success-color); background: var(--success-bg); border-color: #badbcc; }

        .link { margin-top: 20px; font-size: 14px; }
        .link a { color: var(--primary-blue); text-decoration: none; font-weight: bold; transition: color 0.3s; }
        .link a:hover { color: var(--yellow-accent); text-decoration: underline; }

        .role-display {
            background: var(--light-blue);
            color: white;
            padding: 10px 15px;
            border-radius: 15px;
            margin-bottom: 20px;
            font-weight: bold;
            display: inline-block;
        }
    </style>
</head>
<body>

<div class="container">
    <span class="role-display"><i class="fas fa-user-graduate"></i> Adding Student</span>

    <h2>Register New Student!</h2>

    <?php if ($message): ?>
        <p class="message <?= htmlspecialchars($message_type) ?>">
            <?= $message ?>
        </p>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="role" value="student">

        <div class="input-group">
            <i class="fas fa-signature"></i>
            <input type="text" name="fullname" placeholder="Student's Full Name" required value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>">
        </div>

        <div class="input-group">
            <i class="fas fa-user"></i>
            <input type="text" name="username" placeholder="Student's Username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        </div>

        <div class="input-group">
            <i class="fas fa-lock"></i>
            <input type="password" name="password" placeholder="Temporary Password" required minlength="<?= MIN_PASSWORD_LENGTH ?>" autocomplete="new-password">
        </div>

        <div class="input-group">
            <i class="fas fa-book"></i>
            <input type="text" name="grade" placeholder="Grade/Year Level" required readonly class="readonly-input"
                   value="<?= htmlspecialchars($_POST['grade'] ?? $default_grade) ?>">
        </div>

        <div class="input-group">
            <i class="fas fa-users"></i>
            <input type="text" name="section" placeholder="Section" required readonly class="readonly-input"
                   value="<?= htmlspecialchars($_POST['section'] ?? $default_section) ?>">
        </div>

        <p style="font-size: 14px; color: var(--primary-blue); margin-top: 5px;">
            This student will be registered for **Grade <?= htmlspecialchars($default_grade) ?> - Section <?= htmlspecialchars($default_section) ?>**.
        </p>

        <input type="submit" value="Register Student">
    </form>

    <div class="link">
        <a href="../">← Back to Dashboard</a>
    </div>
</div>

</body>
</html>