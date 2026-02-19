<?php
session_start();
require_once "config/database.php";
global $pdo;

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $grade    = $_POST['grade'];
    $section  = trim($_POST['section']);
    $password = $_POST['password'];

    // 1. Password Length Validation
    if (strlen($password) < 8) {
        $message = "Password must be at least 8 characters.";
        $message_type = "error";
    } else {
        // 2. Check if Username is already taken
        $checkUser = $pdo->prepare("SELECT id FROM teachers WHERE username = ?");
        $checkUser->execute([$username]);

        // 3. Check if Grade & Section combination already has a teacher
        $checkClass = $pdo->prepare("SELECT id FROM teachers WHERE grade = ? AND section = ?");
        $checkClass->execute([$grade, $section]);

        if ($checkUser->rowCount() > 0) {
            $message = "Username already taken.";
            $message_type = "error";
        } elseif ($checkClass->rowCount() > 0) {
            $message = "Grade $grade - Section $section already has an assigned teacher.";
            $message_type = "error";
        } else {
            // Profile Image Upload
            $profile_filename = "default.png";
            if (isset($_FILES['profile_img']) && $_FILES['profile_img']['error'] === 0) {
                $upload_dir = 'uploads/profiles/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                $file_ext = pathinfo($_FILES['profile_img']['name'], PATHINFO_EXTENSION);
                $profile_filename = "teacher_" . time() . "_" . $username . "." . $file_ext;
                move_uploaded_file($_FILES['profile_img']['tmp_name'], $upload_dir . $profile_filename);
            }

            // INSERT DIRECTLY (No Hashing as requested)
            $sql = "INSERT INTO teachers (fullname, username, grade, section, password, profile_img) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);

            if ($stmt->execute([$fullname, $username, $grade, $section, $password, $profile_filename])) {
                $message = "Registration successful! Redirecting...";
                $message_type = "success";
                echo "<script>setTimeout(() => { window.location.href = 'login.php'; }, 2000);</script>";
            }
        }
    }
}

$quotes = [
    ["text" => "Teaching is the greatest act of optimism.", "author" => "Colleen Wilcox"],
    ["text" => "The seeds you plant today will shade someone tomorrow.", "author" => "Unknown"]
];
$random_quote = $quotes[array_rand($quotes)];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>V.E.R.B.A.L | Teacher Sign Up</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #1E3A8A;
            --secondary: #3B82F6;
            --bg-color: #F8FAFC;
            --gradient: linear-gradient(135deg, #1E3A8A 0%, #3B82F6 100%);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-color);
            background-image: radial-gradient(#cbd5e1 0.5px, transparent 0.5px);
            background-size: 30px 30px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .wrapper {
            display: flex;
            width: 100%;
            max-width: 950px;
            max-height: 90vh;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 40px 100px -20px rgba(30, 58, 138, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.5);
            animation: zoomIn 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes zoomIn {
            from { opacity: 0; transform: scale(0.9) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .quote-side {
            flex: 1;
            background: var(--gradient);
            padding: 40px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }

        .quote-side::before {
            content: "";
            position: absolute;
            width: 200px; height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            top: -50px; right: -50px;
        }

        .form-side {
            flex: 1.2;
            padding: 40px;
            background: white;
            overflow-y: auto;
        }

        .logo {
            font-size: 2.2rem;
            font-weight: 900;
            text-align: center;
            margin-bottom: 25px;
            color: var(--primary);
            letter-spacing: -1px;
        }

        .alert {
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 0.85rem;
        }
        .alert-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }

        .profile-upload {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 25px;
        }
        .profile-circle {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            border: 3px solid #f1f5f9;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            display: flex;
            justify-content: center; align-items: center;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            overflow: hidden;
            background: #fff;
        }
        .profile-circle:hover {
            transform: scale(1.08) rotate(3deg);
            border-color: var(--secondary);
        }
        .profile-circle img { width: 100%; height: 100%; object-fit: cover; }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-size: 0.8rem;
            font-weight: 600;
            color: #64748b;
        }

        input, select {
            width: 100%;
            padding: 12px 18px;
            border: 2px solid #f1f5f9;
            border-radius: 14px;
            background: #f8fafc;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        input:focus {
            outline: none;
            border-color: var(--secondary);
            background: white;
            box-shadow: 0 10px 20px -5px rgba(59, 130, 246, 0.15);
            transform: translateY(-2px);
        }

        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }

        .btn-submit {
            width: 100%;
            padding: 15px;
            background: var(--gradient);
            color: white;
            border: none;
            border-radius: 16px;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.4s ease;
            margin-top: 15px;
            box-shadow: 0 15px 30px -5px rgba(30, 58, 138, 0.3);
        }
        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 40px -5px rgba(30, 58, 138, 0.4);
            filter: brightness(1.1);
        }

        .footer { margin-top: 25px; text-align: center; color: #94a3b8; font-size: 0.85rem; }
        .footer a { color: var(--secondary); text-decoration: none; font-weight: 700; }

        @media (max-width: 850px) {
            .wrapper { flex-direction: column; max-height: 95vh; max-width: 500px; }
            .quote-side { display: none; }
            .form-side { padding: 30px; }
        }
        @media (max-width: 480px) {
            .grid { grid-template-columns: 1fr; }
            .wrapper { border-radius: 20px; }
            .logo { font-size: 1.8rem; }
        }
    </style>
</head>
<body>

<div class="wrapper">
    <div class="quote-side">
        <div class="quote-container animate__animated animate__fadeIn">
            <h2 class="animate__animated animate__lightSpeedInLeft">Welcome, Educator!</h2>
            <p style="font-size: 1.2rem; font-style: italic; margin-top: 10px;">"<?= $random_quote['text'] ?>"</p>
            <p style="margin-top: 10px; font-weight: 600;">— <?= $random_quote['author'] ?></p>
        </div>
    </div>

    <div class="form-side">
        <div class="logo animate__animated animate__pulse animate__infinite">V.E.R.B.A.L.</div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?> animate__animated animate__shakeX">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <form id="regForm" action="" method="POST" enctype="multipart/form-data">
            <div class="profile-upload animate__animated animate__fadeIn animate__delay-1s">
                <div class="profile-circle" onclick="document.getElementById('profile_input').click()">
                    <i class="fa-solid fa-camera" id="upload-icon" style="color: var(--secondary); font-size: 1.2rem;"></i>
                    <img id="image-preview" style="display: none;">
                </div>
                <input type="file" name="profile_img" id="profile_input" accept="image/*" hidden onchange="previewImage(event)">
                <label style="margin-top: 8px; font-size: 0.75rem; color: #94a3b8;">Upload Photo</label>
            </div>

            <div class="form-group animate__animated animate__fadeInLeft animate__delay-1s">
                <label>Full Name</label>
                <input type="text" name="fullname" placeholder="Juan Dela Cruz" required>
            </div>

            <div class="form-group animate__animated animate__fadeInLeft" style="animation-delay: 1.1s;">
                <label>Username</label>
                <input type="text" name="username" placeholder="juan_01" required>
            </div>

            <div class="grid animate__animated animate__fadeInLeft" style="animation-delay: 1.2s;">
                <div class="form-group">
                    <label>Grade Level</label>
                    <select name="grade" required>
                        <option value="" disabled selected>Select</option>
                        <?php for($i=1; $i<=6; $i++) echo "<option value='$i'>Grade $i</option>"; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Section</label>
                    <input type="text" name="section" placeholder="e.g. Rizal" required>
                </div>
            </div>

            <div class="form-group animate__animated animate__fadeInLeft" style="animation-delay: 1.3s;">
                <label>Password</label>
                <input type="password" name="password" placeholder="Min. 8 characters" required>
            </div>

            <button type="submit" class="btn-submit" id="submitBtn">
                <span>Register Account</span>
            </button>
        </form>

        <div class="footer animate__animated animate__fadeInUp animate__delay-1s">
            Already have an account? <a href="login.php">Log In</a>
        </div>
    </div>
</div>

<script>
    function previewImage(event) {
        const output = document.getElementById('image-preview');
        const icon = document.getElementById('upload-icon');
        const reader = new FileReader();
        reader.onload = function() {
            output.src = reader.result;
            output.style.display = "block";
            output.classList.add('animate__animated', 'animate__zoomIn');
            icon.style.display = "none";
        };
        reader.readAsDataURL(event.target.files[0]);
    }

    document.getElementById('regForm').onsubmit = function() {
        const btn = document.getElementById('submitBtn');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        btn.style.opacity = '0.8';
        btn.style.pointerEvents = 'none';
    };
</script>

</body>
</html>