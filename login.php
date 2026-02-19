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
            // Get user based on username
            $stmt = $pdo->prepare("SELECT id, username, password FROM $table WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // CHANGED: Direct comparison ($password === $user['password'])
            if ($user && $password === $user['password']) {
                session_regenerate_id(true);

                if ($role === 'teacher') {
                    $_SESSION['teacher_id'] = $user['id'];
                }

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
    <title>V.E.R.B.A.L</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --bg: linear-gradient(180deg, #4facfe 0%, #00f2fe 35%, #a8e063 85%, #56ab2f 100%);
            --container-bg: linear-gradient(180deg, rgba(255, 255, 255, 0.95) 0%, rgba(241, 248, 233, 0.95) 100%);
            --text-main: #2d5a27;
            --input-bg: #f1f8e9;
            --input-border: #8bc34a;
            --btn-bg: linear-gradient(to right, #689f38 0%, #8bc34a 100%);
            --btn-text: #ffffff;
            --cosmic-opacity: 0;
            --cloud-opacity: 1;
            --landscape-opacity: 1;
            --sun-top: 10%;
            --moon-top: -200px;
            --sun-pointer: auto;
            --moon-pointer: none;
        }

        .dark-mode {
            --bg: radial-gradient(circle at center, #1a0633 0%, #050505 100%);
            --container-bg: rgba(65, 63, 81, 0.6);
            --text-main: #ffffff;
            --input-bg: rgba(30, 30, 45, 0.8);
            --input-border: #7e57c2;
            --btn-bg: linear-gradient(to right, #ff4081, #f50057);
            --btn-text: #ffffff;
            --cosmic-opacity: 1;
            --cloud-opacity: 0;
            --landscape-opacity: 0;
            --sun-top: -200px;
            --moon-top: 10%;
            --sun-pointer: none;
            --moon-pointer: auto;
        }

        body {
            margin: 0; padding: 0; height: 100vh; width: 100vw;
            font-family: 'Segoe UI', sans-serif;
            background: var(--bg);
            background-attachment: fixed;
            display: flex; align-items: center; justify-content: center;
            overflow: hidden;
            transition: 1s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* --- DAGDAG NA ANIMATIONS --- */
        @keyframes containerEntrance {
            from { opacity: 0; transform: scale(0.8) translateY(50px); filter: blur(10px); }
            to { opacity: 1; transform: scale(1) translateY(0); filter: blur(0); }
        }

        @keyframes celestialEntrance {
            0% { transform: scale(0) rotate(-180deg); opacity: 0; }
            80% { transform: scale(1.1) rotate(10deg); }
            100% { transform: scale(1) rotate(0); opacity: 1; }
        }

        /* Improved Leaf Fall Animation */
        .leaf {
            position: absolute;
            width: 15px;
            height: 10px;
            background: #8bc34a;
            border-radius: 10px 0;
            opacity: 0.6;
            pointer-events: none;
            z-index: 5;
            animation: fallRotate linear infinite;
        }

        @keyframes fallRotate {
            0% { top: -10%; transform: translateX(0) rotate(0deg); }
            25% { transform: translateX(50px) rotate(90deg); }
            50% { transform: translateX(-50px) rotate(180deg); }
            75% { transform: translateX(50px) rotate(270deg); }
            100% { top: 110%; transform: translateX(0) rotate(360deg); }
        }

        /* Star Twinkle */
        .star { position: absolute; background: white; border-radius: 50%; opacity: 0; transition: 1s; }
        .dark-mode .star { opacity: 0.7; animation: twinkle var(--d) infinite; }
        @keyframes twinkle { 0%, 100% { opacity: 0.3; transform: scale(1); } 50% { opacity: 1; transform: scale(1.2); } }

        /* --------------------------- */

        .landscape-bg, .cloud-bg, .cosmic-bg {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            pointer-events: none;
        }

        .landscape-bg {
            z-index: 1; transition: 0.8s ease-in-out;
            opacity: var(--landscape-opacity);
            transform: translateY(calc((1 - var(--landscape-opacity)) * 100px));
        }

        .mountain { position: absolute; bottom: -20px; background: #689f38; border-radius: 50% 50% 0 0; }
        .m1 { width: 900px; height: 300px; left: -150px; opacity: 0.7; background: #9ccc65; }
        .m2 { width: 700px; height: 250px; right: -100px; background: #558b2f; }

        .tree { position: absolute; bottom: 20px; width: 60px; height: 100px; display: flex; flex-direction: column; align-items: center; }
        .tree-top { width: 60px; height: 75px; background: #43a047; border-radius: 50% 50% 40% 40%; box-shadow: inset -5px -5px 10px rgba(0,0,0,0.1); }
        .tree-trunk { width: 12px; height: 25px; background: #5d4037; border-radius: 0 0 4px 4px; }
        .t1 { left: 10%; transform: scale(1.2); }
        .t2 { left: 22%; transform: scale(0.9); opacity: 0.8; }
        .t3 { right: 15%; transform: scale(1.4); }

        .sun, .moon {
            position: fixed; right: 10%;
            width: 100px; height: 100px; border-radius: 50%;
            transition: 1.2s cubic-bezier(0.68, -0.55, 0.27, 1.55);
            z-index: 100;
            cursor: pointer;
            animation: celestialEntrance 1.2s ease-out backwards;
        }
        .sun {
            top: var(--sun-top);
            background: radial-gradient(circle, #fff9c4, #fbc02d);
            box-shadow: 0 0 50px #fbc02d;
            pointer-events: var(--sun-pointer);
        }
        .moon {
            top: var(--moon-top);
            background: radial-gradient(circle at 30% 30%, #ffffff, #bdc3c7);
            box-shadow: 0 0 30px rgba(255, 255, 255, 0.4);
            pointer-events: var(--moon-pointer);
        }

        .cloud-bg { opacity: var(--cloud-opacity); transition: 0.8s; z-index: 1; }
        .cloud { position: absolute; background: #ffffff; width: 150px; height: 50px; border-radius: 50px; animation: moveClouds linear infinite; }
        .cloud::after, .cloud::before { content: ''; position: absolute; background: #ffffff; border-radius: 50%; }
        .cloud::after { width: 70px; height: 70px; top: -35px; left: 25px; }
        .cloud::before { width: 50px; height: 50px; top: -20px; left: 70px; }
        @keyframes moveClouds { from { left: -200px; } to { left: 100%; } }
        .c1 { top: 15%; animation-duration: 40s; transform: scale(0.8); }
        .c2 { top: 40%; animation-duration: 60s; animation-delay: -10s; transform: scale(1.2); }
        .c3 { top: 70%; animation-duration: 35s; animation-delay: -5s; transform: scale(0.6); }

        .cosmic-bg { opacity: var(--cosmic-opacity); transition: 0.8s; z-index: 1; }
        .meteor { position: absolute; width: 2px; height: 100px; background: linear-gradient(to bottom, transparent, #fff); animation: fall linear infinite; }
        @keyframes fall { 0% { transform: translateY(-150px) rotate(-45deg); opacity: 0; } 10% { opacity: 1; } 100% { transform: translateY(110vh) translateX(500px) rotate(-45deg); opacity: 0; } }
        .meteor:nth-child(1) { left: 10%; animation-duration: 4s; }
        .meteor:nth-child(2) { left: 40%; animation-duration: 6s; animation-delay: 2s; }
        .meteor:nth-child(3) { left: 70%; animation-duration: 3.5s; animation-delay: 1s; }

        .planet { position: absolute; border-radius: 50%; }
        .p1 { width: 180px; height: 180px; top: -40px; left: 5%; background: radial-gradient(circle at 30% 30%, #5e35b1, #1a0633); box-shadow: inset -20px -20px 50px rgba(0,0,0,0.7); }
        .p2 { width: 100px; height: 100px; bottom: 10%; right: 10%; background: radial-gradient(circle at 30% 30%, #d81b60, #4a001f); box-shadow: inset -15px -15px 40px rgba(0,0,0,0.8); }
        .p3 { width: 40px; height: 40px; top: 20%; right: 20%; background: #3949ab; opacity: 0.6; filter: blur(1px); }
        .p4 { width: 60px; height: 60px; bottom: 20%; left: 15%; background: #00acc1; box-shadow: inset -10px -10px 20px rgba(0,0,0,0.5); }
        .p5 { width: 15px; height: 15px; top: 40%; left: 35%; background: #fff; box-shadow: 0 0 15px #fff; animation: pulse 3s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 0.4; transform: scale(1); } 50% { opacity: 1; transform: scale(1.2); } }

        .container {
            background: var(--container-bg);
            backdrop-filter: blur(20px);
            padding: 3rem 2.5rem;
            border-radius: 40px;
            width: 90%;
            max-width: 320px;
            text-align: center;
            z-index: 10;
            border: 2px solid var(--input-border);
            box-shadow: 0 25px 50px rgba(0,0,0,0.2);
            transition: 0.8s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            animation: containerEntrance 1s cubic-bezier(0.22, 1, 0.36, 1) backwards;
        }

        h2 { color: var(--text-main); font-weight: 900; text-transform: uppercase; margin: 0; font-size: 2rem; }
        input { width: 100%; padding: 0.9rem 1.2rem; background: var(--input-bg); border: 1px solid var(--input-border); border-radius: 12px; color: #333; margin-bottom: 12px; outline: none; box-sizing: border-box; }
        .dark-mode input { color: #ffffff; }
        .login-btn { background: var(--btn-bg); color: var(--btn-text); width: 100%; padding: 1rem; border-radius: 50px; border: none; font-weight: 900; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; transition: 0.3s; }
    </style>
</head>
<body>

<div class="sun" onclick="toggleTheme()"></div>
<div class="moon" onclick="toggleTheme()"></div>

<div class="cloud-bg" id="cloudContainer">
    <div class="cloud c1"></div><div class="cloud c2"></div><div class="cloud c3"></div>
</div>
<div class="landscape-bg" id="landscapeContainer">
    <div class="mountain m1"></div><div class="mountain m2"></div>
    <div class="tree t1"><div class="tree-top"></div><div class="tree-trunk"></div></div>
    <div class="tree t2"><div class="tree-top"></div><div class="tree-trunk"></div></div>
    <div class="tree t3"><div class="tree-top"></div><div class="tree-trunk"></div></div>
</div>

<div class="cosmic-bg" id="cosmicContainer">
    <div class="meteor"></div><div class="meteor"></div><div class="meteor"></div>
    <div class="planet p1"></div><div class="planet p2"></div>
    <div class="planet p3"></div><div class="planet p4"></div>
    <div class="planet p5"></div>
</div>

<div class="container">
    <h2>LOGIN</h2>

    <?php if ($message): ?>
        <div style="color: #ff4081; font-size: 0.8rem; margin-bottom: 15px; font-weight: bold;"><?= $message ?></div>
    <?php endif; ?>

    <form method="POST">
        <div style="display: flex; gap: 10px; margin-bottom: 20px;">
            <button type="button" id="sBtn" onclick="setRole('student')" style="flex:1; padding: 6px; border-radius: 15px; border: 1px solid var(--input-border); background: transparent; color: var(--text-main); cursor: pointer; font-size: 0.8rem;">Student</button>
            <button type="button" id="tBtn" onclick="setRole('teacher')" style="flex:1; padding: 6px; border-radius: 15px; border: 1px solid var(--input-border); background: transparent; color: var(--text-main); cursor: pointer; font-size: 0.8rem;">Teacher</button>
        </div>
        <input type="hidden" name="role" id="role" value="student">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" class="login-btn">LOGIN <i class="fas fa-arrow-right"></i></button>

        <a href="signup.php" id="signupLink" style="display: none; margin-top: 25px; font-size: 0.8rem; color: var(--text-main); text-decoration: none; opacity: 0.7;">Create Account</a>
    </form>
</div>

<script>
    function toggleTheme() {
        document.body.classList.toggle('dark-mode');
    }

    function setRole(r) {
        document.getElementById('role').value = r;
        const sBtn = document.getElementById('sBtn');
        const tBtn = document.getElementById('tBtn');
        const signupLink = document.getElementById('signupLink');

        if (r === 'student') {
            sBtn.style.background = 'var(--input-border)';
            sBtn.style.color = 'white';
            tBtn.style.background = 'transparent';
            tBtn.style.color = 'var(--text-main)';
            signupLink.style.display = 'none';
        } else {
            tBtn.style.background = 'var(--input-border)';
            tBtn.style.color = 'white';
            sBtn.style.background = 'transparent';
            sBtn.style.color = 'var(--text-main)';
            signupLink.style.display = 'block';
        }
    }

    function initDynamicAssets() {
        const cosmic = document.getElementById('cosmicContainer');
        const landscape = document.getElementById('landscapeContainer');

        // Stars generator
        for (let i = 0; i < 60; i++) {
            const star = document.createElement('div');
            star.className = 'star';
            const size = Math.random() * 3 + 'px';
            star.style.width = size;
            star.style.height = size;
            star.style.top = Math.random() * 100 + '%';
            star.style.left = Math.random() * 100 + '%';
            star.style.setProperty('--d', (Math.random() * 3 + 2) + 's');
            cosmic.appendChild(star);
        }

        // Improved Leaves generator
        for (let i = 0; i < 15; i++) {
            const leaf = document.createElement('div');
            leaf.className = 'leaf';
            leaf.style.left = Math.random() * 100 + '%';
            leaf.style.animationDuration = (Math.random() * 5 + 7) + 's';
            leaf.style.animationDelay = (Math.random() * -10) + 's';
            landscape.appendChild(leaf);
        }
    }

    window.onload = () => {
        setRole('student');
        initDynamicAssets();
    };
</script>
</body>
</html>