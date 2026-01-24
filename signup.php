<?php
session_start();
$message = '';
$message_type = '';

require_once "config/database.php";
global $pdo;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role     = $_POST['role'] ?? 'student';
    $fullname = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $grade    = trim($_POST['grade'] ?? '');
    $section  = trim($_POST['section'] ?? '');

    if (empty($fullname) || empty($username) || empty($grade) || empty($section) || empty($password)) {
        $message = "❌ Pakisagutan ang lahat ng fields.";
        $message_type = 'error';
    } else {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $table = ($role === "student") ? "students" : "teachers";
            $sql = "INSERT INTO {$table} (fullname, username, grade, section, password) VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);

            if ($stmt->execute([$fullname, $username, $grade, $section, $hashedPassword])) {
                $message = "✅ Welcome $fullname! Registered successfully.";
                $message_type = 'success';
                $_POST = [];
            }
        } catch (PDOException $e) {
            $message = ($e->getCode() === '23000') ? "❌ Username taken na po." : "❌ Error sa database.";
            $message_type = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>V.E.R.B.A.L</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #0288d1;
            --yellow-3d: #ffca28;
            --yellow-push: #ffa000;
            --bg-gradient: linear-gradient(-45deg, #4facfe, #00f2fe, #a8e063, #56ab2f);
            --card-bg: white;
            --text-color: #37474f;
            --guide-bg: rgba(255, 255, 255, 0.9);
            --input-bg: #ffffff;
            --sun-top: 20px;
            --moon-top: -150px;
            --cloud-opacity: 1;
            --meteor-opacity: 0;
        }

        .dark-mode {
            --primary-blue: #7e57c2;
            --yellow-3d: #ff4081;
            --yellow-push: #c2185b;
            --bg-gradient: radial-gradient(circle at center, #1a0633 0%, #050505 100%);
            --card-bg: rgba(30, 30, 45, 0.95);
            --text-color: #ffffff;
            --guide-bg: rgba(65, 63, 81, 0.9);
            --input-bg: #2a2a3d;
            --sun-top: -150px;
            --moon-top: 20px;
            --cloud-opacity: 0;
            --meteor-opacity: 1;
        }

        body {
            margin: 0; padding: 0; min-height: 100vh;
            font-family: 'Comic Sans MS', cursive, sans-serif;
            background: var(--bg-gradient);
            background-size: 400% 400%;
            display: flex; align-items: center; justify-content: center;
            overflow: hidden; perspective: 1200px;
            transition: 1s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* --- CLOUDS (UMAGA) --- */
        .cloud-container {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            pointer-events: none; opacity: var(--cloud-opacity); transition: 1s; z-index: 1;
        }
        .cloud {
            position: absolute; background: white; width: 120px; height: 40px;
            border-radius: 50px; animation: moveClouds linear infinite; opacity: 0.8;
        }
        .cloud::after, .cloud::before { content: ''; position: absolute; background: white; border-radius: 50%; }
        .cloud::after { width: 50px; height: 50px; top: -25px; left: 15px; }
        .cloud::before { width: 40px; height: 40px; top: -15px; left: 45px; }
        @keyframes moveClouds { from { left: -150px; } to { left: 100%; } }
        .c1 { top: 10%; animation-duration: 25s; }
        .c2 { top: 30%; animation-duration: 40s; animation-delay: -5s; }
        .c3 { top: 60%; animation-duration: 30s; animation-delay: -10s; }

        /* --- METEORS (GABI) --- */
        .meteor-container {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            pointer-events: none; opacity: var(--meteor-opacity); transition: 1s; z-index: 1;
        }
        .meteor {
            position: absolute; width: 2px; height: 80px;
            background: linear-gradient(to bottom, transparent, #fff);
            animation: shoot linear infinite; opacity: 0;
        }
        @keyframes shoot {
            0% { transform: translateY(-100px) translateX(0) rotate(-45deg); opacity: 0; }
            10% { opacity: 1; }
            100% { transform: translateY(100vh) translateX(400px) rotate(-45deg); opacity: 0; }
        }
        .m1 { left: 20%; animation-duration: 3s; }
        .m2 { left: 50%; animation-duration: 5s; animation-delay: 1s; }
        .m3 { left: 80%; animation-duration: 4s; animation-delay: 2s; }

        /* CELESTIAL SWITCHES */
        .sun, .moon {
            position: fixed; right: 5%; width: 80px; height: 80px; border-radius: 50%;
            cursor: pointer; z-index: 1000; transition: 1.2s cubic-bezier(0.68, -0.55, 0.27, 1.55);
        }
        .sun { top: var(--sun-top); background: radial-gradient(circle, #fff9c4, #fbc02d); box-shadow: 0 0 40px #fbc02d; }
        .moon { top: var(--moon-top); background: radial-gradient(circle at 30% 30%, #ffffff, #bdc3c7); box-shadow: 0 0 30px rgba(255, 255, 255, 0.4); }

        /* MAIN CONTAINER (Centered & 3D) */
        .container {
            background: var(--card-bg); padding: 2.5rem; border-radius: 2.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1), 0 20px 0 rgba(0,0,0,0.1);
            width: 100%; max-width: 350px; text-align: center;
            z-index: 10; transform: rotateX(2deg) rotateY(-5deg);
            margin-left: 150px; transition: 0.5s; color: var(--text-color);
        }
        .container:hover { transform: rotateX(0deg) rotateY(0deg); }

        /* FORM ELEMENTS (Original Preserved) */
        .toggle { display: flex; background: rgba(0,0,0,0.05); border-radius: 50px; padding: 5px; margin-bottom: 25px; }
        .toggle button { flex: 1; border: none; background: transparent; padding: 12px; font-weight: bold; border-radius: 40px; cursor: pointer; transition: 0.3s; color: #90a4ae; font-family: inherit; }
        .toggle button.active { background: var(--primary-blue); color: white; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        .input-group { position: relative; margin-bottom: 15px; }
        .input-group i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--primary-blue); z-index: 2; }
        input, select { width: 100%; padding: 14px 14px 14px 45px; border: 2px solid rgba(0,0,0,0.1); border-radius: 15px; background: var(--input-bg); color: var(--text-color); font-size: 1rem; box-sizing: border-box; transition: 0.3s; font-family: inherit; }
        input[type="submit"] { background: var(--yellow-3d); border: none; padding: 16px; border-radius: 18px; font-size: 1.2rem; color: #5d4037; font-weight: 900; width: 100%; cursor: pointer; box-shadow: 0 6px 0 var(--yellow-push); margin-top: 10px; }
        input[type="submit"]:active { transform: translateY(4px); box-shadow: 0 2px 0 var(--yellow-push); }

        #studentGuide {
            position: absolute; left: 5%; top: 50%; transform: translateY(-50%) rotateY(20deg);
            background: var(--guide-bg); padding: 1.5rem; border-radius: 2rem; width: 260px;
            box-shadow: 15px 15px 30px rgba(0,0,0,0.1); border: 4px solid var(--primary-blue);
            z-index: 5; transition: 0.5s; color: var(--text-color);
        }

        @media (max-width: 850px) {
            body { flex-direction: column; overflow-y: auto; padding: 40px 20px; }
            #studentGuide { position: relative; left: 0; top: 0; transform: none; margin-bottom: 30px; width: 90%; }
            .container { margin-left: 0; transform: none; }
        }
    </style>
</head>
<body id="mainBody">

<div class="cloud-container">
    <div class="cloud c1"></div><div class="cloud c2"></div><div class="cloud c3"></div>
</div>

<div class="meteor-container">
    <div class="meteor m1"></div><div class="meteor m2"></div><div class="meteor m3"></div>
</div>

<div class="sun" onclick="toggleTheme()"></div>
<div class="moon" onclick="toggleTheme()"></div>

<div id="studentGuide">
    <span style="font-weight:900; color:var(--primary-blue); display:block; margin-bottom:10px;"><i class="fas fa-magic"></i> Quick Guide</span>
    <ul style="list-style:none; padding:0; font-size:0.9rem; font-weight:bold;">
        <li style="margin-bottom:8px;">1. Full Name</li>
        <li style="margin-bottom:8px;">2. Create Username</li>
        <li style="margin-bottom:8px;">3. Select Grade</li>
    </ul>
</div>

<div class="container">
    <div class="toggle">
        <button type="button" id="studentBtn" class="active">Student</button>
        <button type="button" id="teacherBtn">Teacher</button>
    </div>

    <h2 style="margin-bottom: 20px;">Create Account</h2>

    <?php if ($message): ?>
        <div style="padding:10px; border-radius:10px; margin-bottom:15px;" class="<?= $message_type === 'error' ? 'error' : 'success' ?>"><?= $message ?></div>
    <?php endif; ?>

    <form method="POST" action="" id="signupForm">
        <input type="hidden" name="role" id="role" value="student">
        <div class="input-group"><i class="fas fa-signature"></i><input type="text" name="fullname" placeholder="Full Name" required></div>
        <div class="input-group"><i class="fas fa-user"></i><input type="text" name="username" placeholder="Username" required></div>
        <div class="input-group"><i class="fas fa-lock"></i><input type="password" name="password" placeholder="Password" required></div>
        <div class="input-group"><i class="fas fa-graduation-cap"></i>
            <select name="grade" id="gradeSelect" required>
                <option value="">Select Grade</option>
                <?php for($i=1; $i<=6; $i++): ?><option value="<?= $i ?>">Grade <?= $i ?></option><?php endfor; ?>
            </select>
        </div>
        <div class="input-group"><i class="fas fa-users"></i><input type="text" name="section" placeholder="Section" required></div>
        <input type="submit" value="GET STARTED!">
    </form>

    <div style="margin-top:20px; font-size: 0.9rem;">
        May account na? <a href="login.php" style="color:var(--primary-blue); font-weight:bold; text-decoration:none;">Log In here</a>
    </div>
</div>

<script>
    function toggleTheme() {
        document.getElementById('mainBody').classList.toggle('dark-mode');
    }

    const studentBtn = document.getElementById("studentBtn");
    const teacherBtn = document.getElementById("teacherBtn");
    const roleInput = document.getElementById("role");
    const guide = document.getElementById("studentGuide");

    function setRole(role) {
        roleInput.value = role;
        if (role === "student") {
            studentBtn.classList.add("active");
            teacherBtn.classList.remove("active");
            guide.style.opacity = "1";
            guide.style.visibility = "visible";
        } else {
            teacherBtn.classList.add("active");
            studentBtn.classList.remove("active");
            guide.style.opacity = "0";
            guide.style.visibility = "hidden";
        }
    }

    studentBtn.addEventListener("click", () => setRole("student"));
    teacherBtn.addEventListener("click", () => setRole("teacher"));
</script>
</body>
</html>