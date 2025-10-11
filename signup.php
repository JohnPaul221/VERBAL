<?php
// signup.php

session_start();
$message = '';
$message_type = '';

// ⚠️ IMPORTANT: The database.php file is assumed to successfully create a PDO object named $pdo
require_once "config/database.php";

// 🔥 Get the PDO connection object (from database.php)
global $pdo;

// --- CONSTANT FOR PASSWORD LENGTH VALIDATION ---
const MIN_PASSWORD_LENGTH = 8;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Sanitize and collect input.
    $role     = $_POST['role'] ?? ''; // "student" or "teacher"
    $fullname = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? ''; // PLAIN TEXT password collected here
    $grade    = trim($_POST['grade'] ?? '');
    $section  = trim($_POST['section'] ?? '');

    // 2. Basic Validation.
    if (empty($fullname) || empty($username) || empty($grade) || empty(trim($section)) || empty($password)) {
        $message = "❌ Please fill out all required fields, including the password.";
        $message_type = 'error';
    } elseif (strlen($password) < MIN_PASSWORD_LENGTH) {
        $message = "❌ Your password must be at least " . MIN_PASSWORD_LENGTH . " characters long.";
        $message_type = 'error';
    } else {
        // ❌ SECURITY REMOVAL: The password hashing function has been removed.
        // The password will now be stored as plain text. (INSECURE)
        $hashedPassword = $password;

        try {
            $table = ($role === "student") ? "students" : "teachers";

            // SQL: fullname, username, grade, section, password (5 columns)
            $sql = "INSERT INTO {$table} (fullname, username, grade, section, password) VALUES (?, ?, ?, ?, ?)";

            // 🔥 FIX: Use $pdo->prepare() instead of $conn->prepare()
            $stmt = $pdo->prepare($sql);

            // 🔥 FIX: PDO execute accepts an array of values, no bind_param needed
            // The values match the positional placeholders (?) in the SQL query
            $success = $stmt->execute([$fullname, $username, $grade, $section, $hashedPassword]);

            if ($success) {
                $message = "✅ **$role** registered successfully! You can now log in.";
                $message_type = 'success';
                // Optional: Redirect after a successful signup
                // header("Refresh: 3; URL=login.php");
            } else {
                // This block is often redundant with PDO/try-catch, but kept for clarity
                $message = "❌ Registration failed. A server error occurred.";
                $message_type = 'error';
            }

        } catch (PDOException $e) {
            // Check for duplicate username (Error code 23000 is common for unique constraint violation)
            if ($e->getCode() === '23000') {
                $message = "❌ Error: The username '{$username}' is already taken.";
            } else {
                // Log detailed error and show generic message
                error_log("Signup Database Error: " . $e->getMessage());
                $message = "❌ Registration failed due to a database error. (Code: " . $e->getCode() . ")";
            }
            $message_type = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Kids Sky Theme</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* Define theme colors for consistency */
        :root {
            --primary-blue: #01579b; /* Dark Blue for text */
            --light-blue: #4fc3f7; /* Light Blue for borders/accents */
            --yellow-accent: #ffb703; /* Yellow for primary buttons/highlights */
            --yellow-hover: #ff9f1c;
        }

        /* --- Global & Background Styling (UNTOUCHED) --- */
        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            font-family: 'Comic Sans MS', cursive, sans-serif;
            background: linear-gradient(to bottom, #87ceeb, #ccf2ff);
            overflow: hidden;
            position: relative;
        }

        /* Clouds and Animation (UNTOUCHED) */
        .cloud {
            position: absolute;
            background: #fff;
            border-radius: 50%;
            opacity: 0.9;
        }
        .cloud:before, .cloud:after {
            content: '';
            position: absolute;
            background: #fff;
            border-radius: 50%;
        }
        .cloud1, .cloud1b { width: 120px; height: 60px; top: 10%; }
        .cloud1:before, .cloud1b:before { width: 60px; height: 60px; top: -30px; left: 10px; }
        .cloud1:after, .cloud1b:after { width: 80px; height: 80px; top: -40px; right: 15px; }
        .cloud1 { animation: float 70s linear infinite; left: -200px; animation-delay: 0s; }
        .cloud1b { animation: float 70s linear infinite; left: -200px; animation-delay: 35s; }
        .cloud2, .cloud2b { width: 150px; height: 70px; top: 20%; }
        .cloud2:before, .cloud2b:before { width: 70px; height: 70px; top: -35px; left: 20px; }
        .cloud2:after, .cloud2b:after { width: 90px; height: 90px; top: -45px; right: 25px; }
        .cloud2 { animation: float 90s linear infinite; left: -200px; animation-delay: 0s; }
        .cloud2b { animation: float 90s linear infinite; left: -200px; animation-delay: 45s; }
        .cloud3 { width: 180px; height: 80px; bottom: 20%; left: -200px; animation: float 80s linear infinite; }
        .cloud3:before { width: 90px; height: 90px; top: -45px; left: 30px; }
        .cloud3:after { width: 110px; height: 110px; top: -55px; right: 20px; }
        .cloud4 { width: 130px; height: 60px; bottom: 10%; left: -200px; animation: float 100s linear infinite; }
        .cloud4:before { width: 60px; height: 60px; top: -30px; left: 15px; }
        .cloud4:after { width: 80px; height: 80px; top: -40px; right: 10px; }
        @keyframes float {
            from { transform: translateX(0); }
            to   { transform: translateX(120vw); }
        }
        /* Fun extras (UNTOUCHED) */
        .balloon, .animal { position: absolute; animation: floatY 10s ease-in-out infinite alternate; opacity: 0.9; }
        @keyframes floatY { from { transform: translateY(0px); } to { transform: translateY(-25px); } }
        .balloon { font-size: 60px; }
        .b1 { top: 15%; left: 20%; }
        .b2 { bottom: 20%; right: 25%; }
        .animal { font-size: 100px; }
        .a2 { top: 30%; right: 30%; font-size: 110px; }
        .a3 { top: 15%; right: 41%; font-size: 90px; }
        .a4 { bottom: 15%; left: 20%; font-size: 100px; }
        /* Image for Sign Up (UNTOUCHED) */
        .globe {
            position: absolute;
            top: 50%;
            left: 23%;
            transform: translate(-50%, -50%);
            width: 450px;
            height: auto;
            animation: bounce 4s ease-in-out infinite;
        }
        @keyframes bounce {
            0%, 100% { transform: translate(-50%, -50%) translateY(0); }
            50% { transform: translate(-50%, -50%) translateY(-20px); }
        }

        /* --- Container and Form Styling (UNTOUCHED) --- */
        .container {
            background: rgba(255, 255, 255, 0.98);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.15);
            width: 340px;
            text-align: center;
            z-index: 10;
            position: absolute;
            top: 50%;
            right: 25%;
            transform: translateY(-50%);
            animation: pop 0.8s ease;
            max-height: 90vh;
            overflow-y: auto;
        }
        @keyframes pop {
            0% { transform: scale(0.8) translateY(-50%); opacity: 0; }
            100% { transform: scale(1) translateY(-50%); opacity: 1; }
        }

        h2 { color: var(--primary-blue); margin-bottom: 25px; }

        /* Input Field Grouping for Icons */
        .input-group {
            position: relative;
            margin: 15px 0;
            width: 100%;
        }

        /* Input/Select Styling */
        input[type="text"], input[type="email"], input[type="password"], select {
            width: 100%;
            padding: 12px 12px 12px 45px;
            border: 2px solid var(--light-blue);
            border-radius: 15px;
            font-size: 16px;
            box-sizing: border-box;
            background: #fff;
            transition: border-color 0.3s;
        }
        input[type="text"]:focus, input[type="email"]:focus, input[type="password"]:focus, select:focus {
            border-color: var(--yellow-accent);
            outline: none;
        }

        /* Icon Styling */
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

        /* Submit button styling (Primary yellow) */
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

        /* Message Styling */
        .message { font-weight: bold; padding: 10px; border-radius: 10px; margin: 10px 0; border: 1px solid; text-align: left;}
        .message.error { color: #842029; background: #f8d7da; border-color: #f5c2c7; }
        .message.success { color: #0f5132; background: #d1e7dd; border-color: #badbcc; }

        /* Login Link */
        .link { margin-top: 20px; font-size: 14px; }
        .link a { color: var(--primary-blue); text-decoration: none; font-weight: bold; transition: color 0.3s; }
        .link a:hover { color: var(--yellow-accent); text-decoration: underline; }

        /* Role Toggle */
        .toggle {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
            background: #e3f2fd;
            border-radius: 30px;
            padding: 4px;
        }
        .toggle button {
            flex: 1;
            border: none;
            background: transparent;
            padding: 10px 0;
            font-size: 14px;
            border-radius: 25px;
            cursor: pointer;
            transition: 0.3s;
        }
        .toggle button.active {
            background: var(--light-blue);
            color: #fff;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
<img src="upload/globe.png" alt="Globe" class="globe">

<div class="cloud cloud1"></div>
<div class="cloud cloud1b"></div>
<div class="cloud cloud2"></div>
<div class="cloud cloud2b"></div>
<div class="cloud cloud3"></div>
<div class="cloud cloud4"></div>


<div class="container">
    <div class="toggle">
        <button class="active" id="studentBtn"><i class="fas fa-user-graduate"></i> Student</button>
        <button id="teacherBtn"><i class="fas fa-chalkboard-teacher"></i> Teacher</button>
    </div>

    <h2>Sign Up Now!</h2>

    <?php if ($message): ?>
        <p class="message <?= htmlspecialchars($message_type) ?>">
            <?= $message ?>
        </p>
    <?php endif; ?>

    <form method="POST" action="" id="signupForm"> <input type="hidden" name="role" id="role" value="student">

        <div class="input-group">
            <i class="fas fa-signature"></i>
            <input type="text" name="fullname" placeholder="Full Name" required>
        </div>

        <div class="input-group">
            <i class="fas fa-user"></i>
            <input type="text" name="username" placeholder="Username" required>
        </div>

        <div class="input-group">
            <i class="fas fa-lock"></i>
            <input type="password" name="password" id="password" placeholder="Create Password" required minlength="<?= MIN_PASSWORD_LENGTH ?>">
        </div>

        <div class="input-group">
            <i class="fas fa-book"></i>
            <select name="grade" required>
                <option value="">Select Grade/Year Level</option>
                <option value="1">Grade 1</option>
                <option value="2">Grade 2</option>
                <option value="3">Grade 3</option>
                <option value="4">Grade 4</option>
                <option value="5">Grade 5</option>
                <option value="6">Grade 6</option>
            </select>
        </div>

        <div class="input-group">
            <i class="fas fa-users"></i>
            <input type="text" name="section" placeholder="Section (e.g., A, Diamond)" required>
        </div>

        <p style="font-size: 14px; color: var(--primary-blue); margin-top: 5px;">
            Please choose a strong password for your account. (Min. <?= MIN_PASSWORD_LENGTH ?> characters)
        </p>

        <input type="submit" value="Get Started!">
    </form>

    <div class="link">
        Already have an account? <a href="login.php">Log In here</a>!
    </div>
</div>

<script>
    // --- Role Toggle Logic (Updated to clear fields) ---
    const studentBtn = document.getElementById("studentBtn");
    const teacherBtn = document.getElementById("teacherBtn");
    const roleInput = document.getElementById("role");
    const passwordField = document.getElementById("password");

    // Get all form input fields (text, password, and select)
    const formInputs = document.querySelectorAll('#signupForm input[type="text"], #signupForm input[type="password"], #signupForm select');

    if(passwordField) {
        passwordField.setAttribute("autocomplete", "new-password");
    }

    function clearFormFields() {
        formInputs.forEach(input => {
            if (input.type === 'text' || input.type === 'password') {
                input.value = ''; // Clear text and password inputs
            } else if (input.tagName === 'SELECT') {
                input.selectedIndex = 0; // Reset select dropdown to the first option (Select Grade/Year Level)
            }
        });
    }

    function setRole(role) {
        roleInput.value = role;

        // 1. Clear the form fields before setting the new role
        clearFormFields();

        // 2. Set the active button state
        if (role === "teacher") {
            teacherBtn.classList.add("active");
            studentBtn.classList.remove("active");
        } else {
            studentBtn.classList.add("active");
            teacherBtn.classList.remove("active");
        }
    }

    studentBtn.addEventListener("click", () => setRole("student"));
    teacherBtn.addEventListener("click", () => setRole("teacher"));

    // Initialize on load
    setRole(roleInput.value);
</script>
</body>
</html>