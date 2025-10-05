<?php
// signup.php (Student & Teacher with yellow theme)
global $conn;
session_start();
$message = '';

require_once "config/database.php"; // your DB config

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role     = $_POST['role']; // "student" or "teacher"
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $grade    = trim($_POST['grade']);
    $section  = trim($_POST['section']);
    $subject  = isset($_POST['subject']) ? trim($_POST['subject']) : null;

    // AUTO-GENERATED PASSWORD
    $autopass = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'), 0, 8);
    $hashedPassword = password_hash($autopass, PASSWORD_DEFAULT);

    if ($role === "student") {
        $stmt = $conn->prepare("INSERT INTO students (fullname, username, email, grade, section, password) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $fullname, $username, $email, $grade, $section, $hashedPassword);
    } else {
        $stmt = $conn->prepare("INSERT INTO teachers (fullname, username, email, subject, grade, section, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $fullname, $username, $email, $subject, $grade, $section, $hashedPassword);
    }

    if ($stmt->execute()) {
        $message = "✅ $role registered successfully!<br>Your password is: <b>" . $autopass . "</b>";
    } else {
        $message = "❌ Error: " . $stmt->error;
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign Up</title>
    <style>
        body {
            font-family: 'Comic Sans MS', cursive, sans-serif;
            background: #fffbcc;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background: #ffec99;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 0 15px #f9a825;
            width: 350px;
            text-align: center;
        }
        .toggle {
            display: flex;
            justify-content: center;
            margin-bottom: 15px;
        }
        .toggle button {
            flex: 1;
            padding: 10px;
            border: none;
            cursor: pointer;
            background: #fbc02d;
            color: #fff;
            font-weight: bold;
            border-radius: 10px;
            margin: 0 5px;
            transition: 0.3s;
        }
        .toggle button.active {
            background: #f57f17;
        }
        h2 {
            color: #f57f17;
            margin-bottom: 20px;
        }
        input, select {
            width: 90%;
            padding: 10px;
            margin: 8px 0;
            border: 2px solid #fbc02d;
            border-radius: 10px;
            font-size: 15px;
        }
        button[type="submit"] {
            background: #fbc02d;
            border: none;
            padding: 12px;
            border-radius: 12px;
            font-size: 16px;
            cursor: pointer;
            color: #fff;
            font-weight: bold;
            width: 95%;
        }
        button[type="submit"]:hover {
            background: #f9a825;
        }
        .message {
            margin-top: 15px;
            font-weight: bold;
            color: red;
        }
        .form-box { display: none; }
        .form-box.active { display: block; }
    </style>
</head>
<body>
<div class="container">
    <!-- Toggle -->
    <div class="toggle">
        <button class="active" id="studentBtn">Student</button>
        <button id="teacherBtn">Teacher</button>
    </div>

    <h2>Sign Up</h2>
    <?php if ($message): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <!-- Student Form -->
    <div class="form-box active" id="studentForm">
        <form method="POST" action="">
            <input type="hidden" name="role" value="student">
            <input type="text" name="fullname" placeholder="Full Name" required>
            <input type="text" name="username" placeholder="Username" required>
            <input type="email" name="email" placeholder="Parent's Email" required>
            <select name="grade" required>
                <option value="">Select Grade</option>
                <option value="Kinder">Kinder</option>
                <option value="Grade 1">Grade 1</option>
                <option value="Grade 2">Grade 2</option>
                <option value="Grade 3">Grade 3</option>
                <option value="Grade 4">Grade 4</option>
                <option value="Grade 5">Grade 5</option>
                <option value="Grade 6">Grade 6</option>
            </select>
            <input type="text" name="section" placeholder="Section" required>
            <button type="submit">Sign Up</button>
        </form>
    </div>

    <!-- Teacher Form -->
    <div class="form-box" id="teacherForm">
        <form method="POST" action="">
            <input type="hidden" name="role" value="teacher">
            <input type="text" name="fullname" placeholder="Full Name" required>
            <input type="text" name="username" placeholder="Username" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="text" name="subject" placeholder="Subject Handling" required>
            <select name="grade" required>
                <option value="">Select Grade</option>
                <option value="Kinder">Kinder</option>
                <option value="Grade 1">Grade 1</option>
                <option value="Grade 2">Grade 2</option>
                <option value="Grade 3">Grade 3</option>
                <option value="Grade 4">Grade 4</option>
                <option value="Grade 5">Grade 5</option>
                <option value="Grade 6">Grade 6</option>
            </select>
            <input type="text" name="section" placeholder="Section Handling" required>
            <button type="submit">Sign Up</button>
        </form>
    </div>
</div>

<script>
    // Toggle Student / Teacher
    const studentBtn = document.getElementById("studentBtn");
    const teacherBtn = document.getElementById("teacherBtn");
    const studentForm = document.getElementById("studentForm");
    const teacherForm = document.getElementById("teacherForm");

    studentBtn.addEventListener("click", function(){
        this.classList.add("active");
        teacherBtn.classList.remove("active");
        studentForm.classList.add("active");
        teacherForm.classList.remove("active");
    });

    teacherBtn.addEventListener("click", function(){
        this.classList.add("active");
        studentBtn.classList.remove("active");
        teacherForm.classList.add("active");
        studentForm.classList.remove("active");
    });
</script>
</body>
</html>

