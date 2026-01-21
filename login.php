<?php
session_start();
require_once 'config/database.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("Database connection failed.");
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role     = $_POST['role'];

    $table = ($role === "teacher") ? "teachers" : "students";

    if (!empty($table)) {
        try {
            $stmt = $pdo->prepare("SELECT id, username, password FROM $table WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && $password === $user['password']) {
                session_regenerate_id(true);
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['role']      = $role;

                header("Location: " . $role . "/" . $role . "_dashboard.php");
                exit();
            } else {
                $message = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $message = "Application error.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Login | 4K Responsive</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #4fc3f7;
            --primary-dark: #0288d1;
            --text-main: #01579b;
            --white: rgba(255, 255, 255, 0.95);
            /* Scaling factor for 4K */
            font-size: 18px;
        }

        @media (min-width: 2560px) {
            :root { font-size: 24px; } /* Scale everything up for 4K */
        }

        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #87ceeb 0%, #ccf2ff 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
        }

        /* Animated Background Elements */
        .cloud {
            position: absolute;
            background: #fff;
            border-radius: 50rem;
            opacity: 0.8;
            z-index: 1;
        }
        .cloud::before, .cloud::after {
            content: '';
            position: absolute;
            background: #fff;
            border-radius: 50%;
        }

        .cloud1 { width: 15rem; height: 6rem; top: 10%; animation: float 60s linear infinite; }
        .cloud2 { width: 20rem; height: 8rem; top: 25%; animation: float 85s linear infinite reverse; }

        @keyframes float {
            from { transform: translateX(-20vw); }
            to { transform: translateX(110vw); }
        }

        /* Decorative Images - Scaled for high res */
        .decoration {
            position: absolute;
            pointer-events: none;
            transition: all 0.5s ease;
            z-index: 2;
        }
        .books { width: 25rem; left: 5%; bottom: 10%; }
        .boy { width: 28rem; right: 2%; bottom: 5%; }

        /* Login Card */
        .container {
            background: var(--white);
            backdrop-filter: blur(10px);
            padding: 2.5rem;
            border-radius: 2rem;
            box-shadow: 0 20px 50px rgba(0,0,0,0.15);
            width: 100%;
            max-width: 22rem;
            text-align: center;
            z-index: 10;
            border: 1px solid rgba(255,255,255,0.3);
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h2 {
            color: var(--text-main);
            font-size: 2rem;
            margin-bottom: 1.5rem;
            font-weight: 800;
        }

        /* Modern Toggle */
        .toggle {
            display: flex;
            background: #f0f7ff;
            border-radius: 1rem;
            padding: 0.4rem;
            margin-bottom: 2rem;
        }
        .toggle button {
            flex: 1;
            border: none;
            background: transparent;
            padding: 0.8rem;
            font-weight: 600;
            border-radius: 0.7rem;
            cursor: pointer;
            transition: 0.3s;
        }
        .toggle button.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(79, 195, 247, 0.3);
        }

        /* Form Inputs */
        .input-group {
            margin-bottom: 1.2rem;
            text-align: left;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e0eef5;
            border-radius: 0.8rem;
            font-size: 1rem;
            box-sizing: border-box;
            transition: 0.3s;
        }
        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 195, 247, 0.1);
        }

        .password-wrapper {
            position: relative;
        }
        .toggle-eye {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #90a4ae;
        }

        input[type="submit"] {
            background: var(--primary);
            color: white;
            border: none;
            width: 100%;
            padding: 1rem;
            border-radius: 0.8rem;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 1rem;
        }
        input[type="submit"]:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(2, 136, 209, 0.3);
        }

        .message {
            background: #ffebee;
            color: #c62828;
            padding: 0.8rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .link { margin-top: 1.5rem; color: #607d8b; }
        .link a { color: var(--primary-dark); text-decoration: none; font-weight: 700; }

        /* Bubble for 4K */
        .bubble {
            position: absolute;
            top: 15%;
            right: 10%;
            background: white;
            padding: 1.5rem 2.5rem;
            border-radius: 2rem;
            font-weight: bold;
            color: var(--text-main);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            z-index: 5;
        }
    </style>
</head>
<body>

<div class="cloud cloud1"></div>
<div class="cloud cloud2"></div>

<img src="upload/books.png" alt="Books" class="decoration books">
<img src="upload/boy.png" alt="Boy" class="decoration boy">

<div class="bubble">Let's Learn! 🚀</div>

<div class="container">
    <div class="toggle">
        <button class="active" id="studentBtn">Student</button>
        <button id="teacherBtn">Teacher</button>
    </div>

    <h2>Welcome Back</h2>

    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="role" id="role" value="student">

        <div class="input-group">
            <input type="text" name="username" placeholder="Username" required>
        </div>

        <div class="input-group">
            <div class="password-wrapper">
                <input type="password" name="password" id="password" placeholder="Password" required>
                <span class="toggle-eye" onclick="togglePassword()">
                    <i class="fa-regular fa-eye"></i>
                </span>
            </div>
        </div>

        <input type="submit" value="Sign In">
    </form>

    <div class="link">
        New here? <a href="signup.php">Create Account</a>
    </div>
</div>

<script>
    function togglePassword() {
        const passInput = document.getElementById('password');
        const icon = document.querySelector('.toggle-eye i');
        const isPass = passInput.type === "password";

        passInput.type = isPass ? "text" : "password";
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
    }

    const sBtn = document.getElementById("studentBtn");
    const tBtn = document.getElementById("teacherBtn");
    const roleInput = document.getElementById("role");

    function setRole(role) {
        roleInput.value = role;
        if(role === 'student') {
            sBtn.classList.add('active');
            tBtn.classList.remove('active');
        } else {
            tBtn.classList.add('active');
            sBtn.classList.remove('active');
        }
    }

    sBtn.addEventListener("click", () => setRole('student'));
    tBtn.addEventListener("click", () => setRole('teacher'));
</script>
</body>
</html>