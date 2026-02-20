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

    if (strlen($password) < 8) {
        $message = "Password must be at least 8 characters.";
        $message_type = "error";
    } else {
        $checkUser = $pdo->prepare("SELECT id FROM teachers WHERE username = ?");
        $checkUser->execute([$username]);

        $checkClass = $pdo->prepare("SELECT id FROM teachers WHERE grade = ? AND section = ?");
        $checkClass->execute([$grade, $section]);

        if ($checkUser->rowCount() > 0) {
            $message = "Username already taken.";
            $message_type = "error";
        } elseif ($checkClass->rowCount() > 0) {
            $message = "Grade $grade - Section $section already has an assigned teacher.";
            $message_type = "error";
        } else {
            $profile_filename = "default.png";
            if (isset($_FILES['profile_img']) && $_FILES['profile_img']['error'] === 0) {
                $upload_dir = 'uploads/profiles/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                $file_ext = pathinfo($_FILES['profile_img']['name'], PATHINFO_EXTENSION);
                $profile_filename = "teacher_" . time() . "_" . $username . "." . $file_ext;
                move_uploaded_file($_FILES['profile_img']['tmp_name'], $upload_dir . $profile_filename);
            }

            $sql = "INSERT INTO teachers (fullname, username, grade, section, password, profile_img) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);

            if ($stmt->execute([fullname, $username, $grade, $section, $password, $profile_filename])) {
                $message = "Registration successful! Redirecting...";
                $message_type = "success";
                echo "<script>setTimeout(() => { window.location.href = 'login.php'; }, 2000);</script>";
            }
        }
    }
}

$quotes = [
    ["text" => "Your hard work today is the success of a child tomorrow. Take it one breath at a time.", "author" => "Teacher's Heart"],
    ["text" => "It's okay to be tired. It means you've given your heart to something that matters.", "author" => "Unknown"],
    ["text" => "To your students, you are the safe harbor in a stormy world.", "author" => "Unknown"],
    ["text" => "You are doing a better job than you think you are. Be kind to yourself.", "author" => "Unknown"],
    ["text" => "The world needs the light that only you can bring to your classroom.", "author" => "Unknown"],
    ["text" => "Teaching is a work of heart. Your love is never wasted.", "author" => "Unknown"],
    ["text" => "When you feel like you're not making a difference, remember that seeds grow in silence.", "author" => "Unknown"],
    ["text" => "One day, a student will look back and say, 'Because of you, I didn't give up.'", "author" => "Unknown"],
    ["text" => "You are more than just a teacher; you are a builder of dreams.", "author" => "Unknown"],
    ["text" => "Small progress is still progress. You are moving mountains, one student at a time.", "author" => "Unknown"],
    ["text" => "Don't forget to refill your own cup so you can continue to pour into others.", "author" => "Unknown"],
    ["text" => "Your classroom is a garden, and you are the sun. Everything grows better because of you.", "author" => "Unknown"],
    ["text" => "The kids might not remember every lesson, but they will never forget how you made them feel.", "author" => "Maya Angelou"],
    ["text" => "Behind every successful person is a teacher who once believed in them.", "author" => "Unknown"],
    ["text" => "You are the quiet hero the world needs today.", "author" => "Unknown"],
    ["text" => "Take pride in how far you've come and have faith in how far you can go.", "author" => "Unknown"],
    ["text" => "A teacher's smile can change a student's entire day. Thank you for smiling.", "author" => "Unknown"],
    ["text" => "Your patience is your power. Your kindness is your legacy.", "author" => "Unknown"],
    ["text" => "In a world where you can be anything, thank you for choosing to be a teacher.", "author" => "Unknown"],
    ["text" => "The best thing about being a teacher is seeing the light bulb go on in a child's eyes.", "author" => "Unknown"],
    ["text" => "God gave you this calling because He knew you have the heart to handle it.", "author" => "Unknown"],
    ["text" => "Your influence is like a ripple in water; it spreads further than you can see.", "author" => "Unknown"],
    ["text" => "Teaching is the art of planting hope in the soul of a child.", "author" => "Unknown"],
    ["text" => "A great teacher is someone who looks at a 'difficult' child and sees 'hidden potential.'", "author" => "Unknown"],
    ["text" => "You are making a difference, even on the days it doesn't feel like it.", "author" => "Unknown"],
    ["text" => "Teacher, you are enough. Your presence matters more than your perfection.", "author" => "Unknown"],
    ["text" => "The lessons you teach are the wings your students will use to fly.", "author" => "Unknown"],
    ["text" => "Your kindness may be the only kindness a child experiences today.", "author" => "Unknown"],
    ["text" => "Thank you for being the person you needed when you were younger.", "author" => "Unknown"],
    ["text" => "Teaching is hard because it matters. Don't lose heart.", "author" => "Unknown"],
    ["text" => "The seeds you plant today will become the shade for someone else tomorrow.", "author" => "Unknown"],
    ["text" => "Your passion for teaching is the spark that ignites a student's future.", "author" => "Unknown"],
    ["text" => "May you always find joy in the little victories inside your classroom.", "author" => "Unknown"],
    ["text" => "You are not just teaching a subject; you are shaping a soul.", "author" => "Unknown"],
    ["text" => "The world is a better place because you chose to be an educator.", "author" => "Unknown"],
    ["text" => "Even the smallest gesture of care can change a student's life forever.", "author" => "Unknown"],
    ["text" => "You are a rockstar in a cardigan. Keep shining!", "author" => "Unknown"],
    ["text" => "Rest when you are tired, but never forget why you started.", "author" => "Unknown"],
    ["text" => "A teacher's heart is a compass that activates the magnets of curiosity.", "author" => "Unknown"],
    ["text" => "You are the reason someone believes in their own greatness.", "author" => "Unknown"],
    ["text" => "The impact of a great teacher can never be erased from a child's heart.", "author" => "Unknown"],
    ["text" => "You are brave, you are strong, and you are making the world brighter.", "author" => "Unknown"],
    ["text" => "A child's life is a piece of paper on which every person leaves a mark. Make yours beautiful.", "author" => "Chinese Proverb"],
    ["text" => "Your classroom is a place where miracles happen every single day.", "author" => "Unknown"],
    ["text" => "You have the power to turn a 'cannot' into a 'can.'", "author" => "Unknown"],
    ["text" => "Teaching is the greatest act of love anyone can offer society.", "author" => "Unknown"],
    ["text" => "Never underestimate the power of a teacher who cares.", "author" => "Unknown"],
    ["text" => "Success is seeing your students become good human beings.", "author" => "Unknown"],
    ["text" => "You are a lighthouse. Your job is to stay bright so others can find their way.", "author" => "Unknown"],
    ["text" => "Be proud of the work you do, for you are creating the future.", "author" => "Unknown"]
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #1E3A8A;
            --secondary: #3B82F6;
            --accent: #60A5FA;
            --bg-color: #f0f4f8;
            --gradient: linear-gradient(135deg, #1E3A8A 0%, #3B82F6 100%);
            --glass: rgba(255, 255, 255, 0.9);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-color);
            background-image:
                    radial-gradient(at 0% 0%, rgba(30, 58, 138, 0.08) 0px, transparent 50%),
                    radial-gradient(at 100% 100%, rgba(59, 130, 246, 0.08) 0px, transparent 50%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }

        /* --- Floating Particle Design --- */
        .particle {
            position: absolute;
            background: var(--gradient);
            border-radius: 50%;
            filter: blur(50px);
            opacity: 0.15;
            z-index: -1;
            animation: floating 20s infinite alternate;
        }
        .p1 { width: 450px; height: 450px; top: -150px; left: -150px; }
        .p2 { width: 350px; height: 350px; bottom: -80px; right: -80px; animation-duration: 15s; }
        .p3 { width: 200px; height: 200px; top: 15%; right: 5%; background: var(--accent); opacity: 0.1; }

        @keyframes floating {
            0% { transform: translate(0, 0) scale(1) rotate(0deg); }
            100% { transform: translate(50px, 50px) scale(1.1) rotate(15deg); }
        }

        /* --- Main Wrapper --- */
        .wrapper {
            display: flex;
            width: 100%;
            max-width: 1050px;
            height: auto;
            max-height: 95vh;
            background: var(--glass);
            backdrop-filter: blur(15px);
            border-radius: 40px;
            overflow: hidden;
            box-shadow: 0 40px 120px -20px rgba(30, 58, 138, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.6);
            animation: zoomIn 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        /* --- Quote Side (Left) --- */
        .quote-side {
            flex: 1;
            background: var(--gradient);
            padding: 50px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            z-index: 1;
        }

        .quote-side::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            z-index: -1;
        }

        .quote-side h2 {
            font-size: 2.5rem;
            line-height: 1.1;
            text-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }

        .quote-line {
            width: 60px;
            height: 5px;
            background: var(--accent);
            margin: 25px 0;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(96, 165, 250, 0.6);
        }

        /* --- Form Side (Right) --- */
        .form-side {
            flex: 1.2;
            padding: 35px 60px;
            background: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow-y: auto;
        }

        .logo {
            font-size: 2.2rem;
            font-weight: 900;
            text-align: center;
            margin-bottom: 20px;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -2px;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.05));
        }

        /* Profile Upload Design */
        .profile-upload {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
        }

        .profile-circle {
            width: 80px; height: 80px;
            border-radius: 50%;
            border: 3px solid #f1f5f9;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            display: flex;
            justify-content: center; align-items: center;
            cursor: pointer;
            transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            background: #f8fafc;
            position: relative;
        }
        .profile-circle:hover {
            transform: scale(1.08) rotate(5deg);
            border-color: var(--secondary);
            background: #fff;
        }
        .profile-circle img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }

        .form-group { margin-bottom: 12px; position: relative; }

        label {
            display: block;
            margin-bottom: 5px;
            font-size: 0.7rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        input, select {
            width: 100%;
            padding: 12px 18px;
            border: 2px solid #f1f5f9;
            border-radius: 14px;
            background: #f8fafc;
            transition: 0.3s all ease;
            font-size: 0.9rem;
            color: #1e293b;
        }

        input:focus {
            outline: none;
            border-color: var(--secondary);
            background: white;
            box-shadow: 0 8px 20px -5px rgba(59, 130, 246, 0.15);
            transform: translateY(-1px);
        }

        /* Password Toggle Decoration */
        .password-container { position: relative; }
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #94a3b8;
            transition: 0.3s;
        }
        .toggle-password:hover { color: var(--secondary); }

        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }

        .btn-submit {
            width: 100%;
            padding: 16px;
            background: var(--gradient);
            color: white;
            border: none;
            border-radius: 18px;
            font-weight: 800;
            font-size: 1rem;
            cursor: pointer;
            transition: 0.4s;
            margin-top: 15px;
            box-shadow: 0 15px 30px -10px rgba(30, 58, 138, 0.4);
            position: relative;
            overflow: hidden;
            letter-spacing: 1px;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 35px -10px rgba(30, 58, 138, 0.5);
            filter: brightness(1.1);
        }

        .footer { margin-top: 18px; text-align: center; color: #94a3b8; font-size: 0.85rem; }
        .footer a { color: var(--secondary); text-decoration: none; font-weight: 800; border-bottom: 2px solid transparent; transition: 0.3s; }
        .footer a:hover { border-bottom-color: var(--secondary); }

        /* Tablet/Mobile Fix */
        @media (max-width: 850px) {
            .wrapper { flex-direction: column; height: auto; max-height: none; margin: 15px; border-radius: 30px; }
            .quote-side { display: none; }
            .form-side { padding: 30px; }
            body { overflow-y: auto; height: auto; }
        }
    </style>
</head>
<body>

<div class="particle p1"></div>
<div class="particle p2"></div>
<div class="particle p3"></div>

<div class="wrapper">
    <div class="quote-side">
        <div class="quote-container animate__animated animate__fadeIn">
            <h2 class="animate__animated animate__lightSpeedInLeft">Create Your<br>Teacher Account</h2>
            <div class="quote-line"></div>
            <p style="font-size: 1.1rem; font-style: italic; opacity: 0.9;">"Education is the most powerful weapon which you can use to change the world."</p>
            <p style="margin-top: 15px; font-weight: 700; color: var(--accent);">— Nelson Mandela</p>
        </div>
    </div>

    <div class="form-side">
        <div class="logo animate__animated animate__pulse animate__infinite">V.E.R.B.A.L.</div>

        <form id="regForm" action="" method="POST" enctype="multipart/form-data" autocomplete="off">
            <div class="profile-upload animate__animated animate__fadeIn">
                <div class="profile-circle" onclick="document.getElementById('profile_input').click()">
                    <i class="fa-solid fa-camera-retro" id="upload-icon" style="color: var(--secondary); font-size: 1.5rem;"></i>
                    <img id="image-preview" style="display: none;">
                </div>
                <input type="file" name="profile_img" id="profile_input" accept="image/*" hidden onchange="previewImage(event)">
                <label style="margin-top: 8px; font-size: 0.6rem; color: #94a3b8; text-transform: none; letter-spacing: 0;">Tap to set profile picture</label>
            </div>

            <div class="form-group animate__animated animate__fadeInLeft">
                <label>Full Name</label>
                <input type="text" name="fullname" required>
            </div>

            <div class="form-group animate__animated animate__fadeInLeft" style="animation-delay: 0.1s;">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>

            <div class="grid animate__animated animate__fadeInLeft" style="animation-delay: 0.2s;">
                <div class="form-group">
                    <label>Grade Handle</label>
                    <select name="grade" required>
                        <option value="" disabled selected></option>
                        <option value="1">Grade 1</option>
                        <option value="2">Grade 2</option>
                        <option value="3">Grade 3</option>
                        <option value="4">Grade 4</option>
                        <option value="5">Grade 5</option>
                        <option value="6">Grade 6</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Section Handle</label>
                    <input type="text" name="section" required>
                </div>
            </div>

            <div class="form-group animate__animated animate__fadeInLeft" style="animation-delay: 0.3s;">
                <label>Password</label>
                <div class="password-container">
                    <input type="password" name="password" id="passwordField" required autocomplete="new-password">
                    <i class="fa-solid fa-eye toggle-password" onclick="togglePass()"></i>
                </div>
            </div>

            <button type="submit" class="btn-submit" id="submitBtn">
                <span>REGISTER ACCOUNT</span>
            </button>
        </form>

        <div class="footer animate__animated animate__fadeInUp">
            Already registered? <a href="login.php">LOG IN HERE</a>
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

    function togglePass() {
        const passField = document.getElementById('passwordField');
        const icon = document.querySelector('.toggle-password');
        if (passField.type === "password") {
            passField.type = "text";
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            passField.type = "password";
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }

    document.getElementById('regForm').onsubmit = function() {
        const btn = document.getElementById('submitBtn');
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> CREATING ACCOUNT...';
        btn.style.opacity = '0.9';
        btn.style.pointerEvents = 'none';
    };
</script>
</body>
</html>