<?php
session_start();
require_once 'config/database.php';

// Safety check for PDO connection
if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("Database connection failed. Check config/database.php.");
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role     = $_POST['role'];

    $table = '';
    if ($role === "student") {
        $table = "students";
    } elseif ($role === "teacher") {
        $table = "teachers";
    } else {
        $message = "Invalid role selected.";
    }

    if (!empty($table)) {
        try {
            // Retrieve id, username, and the PLAINTEXT password
            $stmt = $pdo->prepare("SELECT id, username, password FROM $table WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // **FIXED: Use PLAINTEXT comparison**
            if ($user && $password === $user['password']) {
                // Regenerate session ID upon successful login (Security Best Practice)
                session_regenerate_id(true);

                $_SESSION['user_id']   = $user['id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['role']      = $role;

                if ($role === "student") {
                    header("Location: student/student_dashboard.php");
                } elseif ($role === "teacher") {
                    header("Location: teacher/teacher_dashboard.php");
                }
                exit();
            } else {
                // Generic error message for security
                $message = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            error_log("Login Query Error: " . $e->getMessage());
            // Hide specific database error details from the user
            $message = "Login failed due to an application error.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
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

        .cloud3 { width: 180px; height: 80px; bottom: 20%; left: -200px;
            animation: float 80s linear infinite; }
        .cloud3:before { width: 90px; height: 90px; top: -45px; left: 30px; }
        .cloud3:after { width: 110px; height: 110px; top: -55px; right: 20px; }

        .cloud4 { width: 130px; height: 60px; bottom: 10%; left: -200px;
            animation: float 100s linear infinite; }
        .cloud4:before { width: 60px; height: 60px; top: -30px; left: 15px; }
        .cloud4:after { width: 80px; height: 80px; top: -40px; right: 10px; }

        @keyframes float {
            from { transform: translateX(0); }
            to   { transform: translateX(120vw); }
        }

        .balloon, .animal {
            position: absolute;
            animation: floatY 10s ease-in-out infinite alternate;
            opacity: 0.9;
        }
        @keyframes floatY {
            from { transform: translateY(0px); }
            to   { transform: translateY(-25px); }
        }
        .balloon { font-size: 60px; }
        .b1 { top: 15%; left: 20%; }
        .b2 { bottom: 20%; right: 25%; }

        .animal { font-size: 100px; }
        .a2 { top: 30%; right: 30%; font-size: 110px; }
        .a3 { top: 15%; right: 41%; font-size: 90px; }
        .a4 { bottom: 15%; left: 20%; font-size: 100px; }

        .books {
            position: absolute;
            top: 50%;
            left: 17%;
            transform: translate(-50%, -50%);
            width: 450px;
            height: auto;
        }

        .bag {
            position: absolute;
            top: 20%;
            left: 19%;
            width: 440px;
            height: auto;
        }
        .boy {
            position: absolute;
            top: 45%;
            right: -7%  ;
            transform: translateY(-50%);
            width: 450px;
            height: auto;
        }
        .bubble {
            position: absolute;
            top: 30%;
            right: 13%;
            background: #fff;
            padding: 20px 35px;
            border-radius: 25px;
            border: 3px solid #4fc3f7;
            font-size: 24px;
            font-weight: bold;
            color: #01579b;
            box-shadow: 3px 3px 12px rgba(0,0,0,0.25);
        }
        .bubble::after {
            content: "";
            position: absolute;
            top: 50%;
            right: -20px;
            transform: translateY(-50%);
            border-width: 10px 0 10px 20px;
            border-style: solid;
            border-color: transparent transparent transparent #fff;
            filter: drop-shadow(2px 0px 2px rgba(0,0,0,0.1));
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
            width: 340px;
            text-align: center;
            z-index: 10;

            position: absolute;
            top: 40%;
            right: 25%;
            transform: translateY(-40%);

            animation: pop 0.8s ease;
        }
        @keyframes pop {
            0% { transform: scale(0.8) translateY(-40%); opacity: 0; }
            100% { transform: scale(1) translateY(-40%); opacity: 1; }
        }

        h2 { color: #01579b; }
        input[type="text"] {
            width: 90%;
            padding: 12px;
            margin: 10px 0;
            border: 2px solid #4fc3f7;
            border-radius: 10px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .password-box {
            position: relative;
            width: 90%;
            margin: 10px auto;
        }
        .password-box input[type="password"], .password-box input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #4fc3f7;
            border-radius: 10px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .password-box .toggle-eye {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 16px;
            color: #666;
        }
        input[type="submit"] {
            background: #4fc3f7;
            border: none;
            padding: 12px 20px;
            border-radius: 15px;
            font-size: 18px;
            cursor: pointer;
            color: #fff;
            font-weight: bold;
            width: 95%;
        }
        input[type="submit"]:hover { background: #0288d1; }

        .message { color: red; font-weight: bold; }
        .link { margin-top: 15px; font-size: 14px; }
        .link a { color: #01579b; text-decoration: none; font-weight: bold; }
        .link a:hover { text-decoration: underline; }
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
            padding: 8px 0;
            font-size: 14px;
            border-radius: 20px;
            cursor: pointer;
            transition: 0.3s;
        }
        .toggle button.active {
            background: #4fc3f7;
            color: #fff;
            font-weight: bold;
        }
        .forgot {
            text-align: right;
            width: 90%;
            margin: auto;
            font-size: 13px;
        }
        .forgot a { color: #01579b; text-decoration: none; }
        .forgot a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<img src="upload/books.png" alt="Books" class="books">
<img src="upload/bag.png" alt="Bag" class="bag">
<img src="upload/boy.png" alt="Boy" class="boy">
<div class="bubble">Welcome</div>

<div class="cloud cloud1"></div>
<div class="cloud cloud1b"></div>
<div class="cloud cloud2"></div>
<div class="cloud cloud2b"></div>
<div class="cloud cloud3"></div>
<div class="cloud cloud4"></div>

<div class="balloon b1">🎈</div>
<div class="balloon b2">🎈</div>

<div class="animal a2">🦁</div>
<div class="animal a3">🦊</div>
<div class="animal a4">🐵</div>

<div class="container">
    <div class="toggle">
        <button class="active" id="studentBtn">Student</button>
        <button id="teacherBtn">Teacher</button>
    </div>

    <h2>Log In</h2>
    <?php if ($message): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="role" id="role" value="student">

        <input type="text" name="username" placeholder="Username" required>
        <div class="password-box">
            <input type="password" name="password" id="password" placeholder="Password" required>
            <span class="toggle-eye" onclick="togglePassword()"><i class="fa-regular fa-eye"></i></span>
            <div class="forgot"><a href="#">Forgot Password?</a></div>
        </div>
        <input type="submit" value="Log In">
    </form>

    <div class="link">
        Don't have an account? <a href="signup.php">Sign up here</a>!
    </div>
</div>

<script>
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.querySelector('.toggle-eye i');
        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            eyeIcon.classList.remove('fa-eye');
            eyeIcon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = "password";
            eyeIcon.classList.remove('fa-eye-slash');
            eyeIcon.classList.add('fa-eye');
        }
    }

    function clearLoginFields() {
        document.querySelector('input[name="username"]').value = "";
        document.getElementById('password').value = "";
        // Reset eye icon to 'show password' if you switch modes
        document.querySelector('.toggle-eye i').classList.remove('fa-eye-slash');
        document.querySelector('.toggle-eye i').classList.add('fa-eye');
        document.getElementById('password').type = 'password';
    }

    document.getElementById("studentBtn").addEventListener("click", function(){
        this.classList.add("active");
        document.getElementById("teacherBtn").classList.remove("active");
        document.getElementById("role").value = "student";
        clearLoginFields();
    });

    document.getElementById("teacherBtn").addEventListener("click", function(){
        this.classList.add("active");
        document.getElementById("studentBtn").classList.remove("active");
        document.getElementById("role").value = "teacher";
        clearLoginFields();
    });
</script>
</body>
</html>