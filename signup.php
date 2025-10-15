<?php
session_start();
$message = '';
$message_type = '';

// NOTE: Ensure 'config/database.php' connects to the database and sets the $pdo global variable.
require_once "config/database.php";

global $pdo;

const MIN_PASSWORD_LENGTH = 8;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Sanitize and retrieve user input
    $role     = $_POST['role'] ?? '';
    $fullname = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $grade    = trim($_POST['grade'] ?? '');
    $section  = trim($_POST['section'] ?? '');

    if (empty($fullname) || empty($username) || empty($grade) || empty(trim($section)) || empty($password)) {
        $message = "❌ Please fill out all required fields, including the password.";
        $message_type = 'error';
    } elseif (strlen($password) < MIN_PASSWORD_LENGTH) {
        $message = "❌ Your password must be at least " . MIN_PASSWORD_LENGTH . " characters long.";
        $message_type = 'error';
    } else {
        // **FIXED: Use Plaintext Password for storage**
        $plainTextPassword = $password;

        try {
            // Determine the target table
            $table = ($role === "student") ? "students" : "teachers";
            if ($table !== "students" && $table !== "teachers") {
                throw new Exception("Invalid user role specified.");
            }

            // Prepare the SQL statement
            $sql = "INSERT INTO {$table} (fullname, username, grade, section, password) VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);

            // Execute with the plaintext password
            $success = $stmt->execute([$fullname, $username, $grade, $section, $plainTextPassword]);

            if ($success) {
                $message = "✅ **$role** registered successfully! You can now log in.";
                $message_type = 'success';

                // Clear post variables after success
                $_POST = [];
            } else {
                $message = "❌ Registration failed. A server error occurred.";
                $message_type = 'error';
            }

        } catch (PDOException $e) {
            // Error code 23000 typically means a violation of a unique constraint (like username)
            if ($e->getCode() === '23000') {
                $message = "❌ Error: The username '{$username}' is already taken.";
            } else {
                error_log("Signup Database Error: " . $e->getMessage());
                $message = "❌ Registration failed due to a database error. (Code: " . $e->getCode() . ")";
            }
            $message_type = 'error';
        } catch (Exception $e) {
            error_log("Signup Logic Error: " . $e->getMessage());
            $message = "❌ Registration failed due to a system error.";
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
        :root {
            --primary-blue: #01579b;
            --light-blue: #4fc3f7;
            --yellow-accent: #ffb703;
            --yellow-hover: #ff9f1c;
        }

        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            font-family: 'Comic Sans MS', cursive, sans-serif;
            background: linear-gradient(to bottom, #87ceeb, #ccf2ff);
            overflow: hidden;
            position: relative;
        }

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
        .balloon, .animal { position: absolute; animation: floatY 10s ease-in-out infinite alternate; opacity: 0.9; }
        @keyframes floatY { from { transform: translateY(0px); } to { transform: translateY(-25px); } }
        .balloon { font-size: 60px; }
        .b1 { top: 15%; left: 20%; }
        .b2 { bottom: 20%; right: 25%; }
        .animal { font-size: 100px; }
        .a2 { top: 30%; right: 30%; font-size: 110px; }
        .a3 { top: 15%; right: 41%; font-size: 90px; }
        .a4 { bottom: 15%; left: 20%; font-size: 100px; }
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

        .input-group {
            position: relative;
            margin: 15px 0;
            width: 100%;
        }

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
        .message.error { color: #842029; background: #f8d7da; border-color: #f5c2c7; }
        .message.success { color: #0f5132; background: #d1e7dd; border-color: #badbcc; }

        .link { margin-top: 20px; font-size: 14px; }
        .link a { color: var(--primary-blue); text-decoration: none; font-weight: bold; transition: color 0.3s; }
        .link a:hover { color: var(--yellow-accent); text-decoration: underline; }

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
            <input type="text" name="fullname" placeholder="Full Name" required value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>">
        </div>

        <div class="input-group">
            <i class="fas fa-user"></i>
            <input type="text" name="username" placeholder="Username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        </div>

        <div class="input-group">
            <i class="fas fa-lock"></i>
            <input type="password" name="password" id="password" placeholder="Create Password" required minlength="<?= MIN_PASSWORD_LENGTH ?>">
        </div>

        <div class="input-group">
            <i class="fas fa-book"></i>
            <select name="grade" required>
                <option value="">Select Grade/Year Level</option>
                <?php
                $grades = [1, 2, 3, 4, 5, 6];
                $selected_grade = $_POST['grade'] ?? '';
                foreach ($grades as $g) {
                    $selected = ($g == $selected_grade) ? 'selected' : '';
                    echo "<option value=\"$g\" $selected>Grade $g</option>";
                }
                ?>
            </select>
        </div>

        <div class="input-group">
            <i class="fas fa-users"></i>
            <input type="text" name="section" placeholder="Section (e.g., A, Diamond)" required value="<?= htmlspecialchars($_POST['section'] ?? '') ?>">
        </div>

        <p style="font-size: 14px; color: var(--primary-blue); margin-top: 5px;">
            Please choose a **strong password** for your account. (Min. <?= MIN_PASSWORD_LENGTH ?> characters)
        </p>

        <input type="submit" value="Get Started!">
    </form>

    <div class="link">
        Already have an account? <a href="login.php">Log In here</a>!
    </div>
</div>

<script>
    const studentBtn = document.getElementById("studentBtn");
    const teacherBtn = document.getElementById("teacherBtn");
    const roleInput = document.getElementById("role");
    const passwordField = document.getElementById("password");

    const formInputs = document.querySelectorAll('#signupForm input[type="text"], #signupForm input[type="password"], #signupForm select');

    if(passwordField) {
        passwordField.setAttribute("autocomplete", "new-password");
    }

    function clearFormFields() {
        formInputs.forEach(input => {
            if (input.type === 'text' || input.type === 'password') {
                input.value = '';
            } else if (input.tagName === 'SELECT') {
                input.selectedIndex = 0;
            }
        });
    }

    function setRole(role) {
        roleInput.value = role;

        // Only clear the fields if the role is actually changing
        if (role !== roleInput.value) {
            clearFormFields();
        }

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

    // Set the initial role and update button state based on the PHP success/error state
    const initialRole = "<?= $_POST['role'] ?? 'student' ?>";
    setRole(initialRole);

    // If there was an error, re-populate the selection to match the failed submission
    if ('<?= $message_type ?>' === 'error') {
        roleInput.value = initialRole;
    }
</script>
</body>
</html>