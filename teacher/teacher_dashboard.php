<?php
session_start();
require_once '../config/database.php';
global $pdo;

// --- FIXED LOGIC: Multi-Role Session Security ---
if (!isset($_SESSION['teacher_logged_in']) || $_SESSION['teacher_logged_in'] !== true || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];

// --- PASSWORD UPDATE LOGIC ---
$update_status = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_pass'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    $stmt_check = $pdo->prepare("SELECT password FROM teachers WHERE id = ?");
    $stmt_check->execute([$teacher_id]);
    $user = $stmt_check->fetch();

    if (password_verify($current_pass, $user['password'])) {
        if ($new_pass === $confirm_pass) {
            $hashed = password_hash($new_pass, PASSWORD_BCRYPT);
            $stmt_up = $pdo->prepare("UPDATE teachers SET password = ? WHERE id = ?");
            $stmt_up->execute([$hashed, $teacher_id]);
            $update_status = "success";
        } else { $update_status = "mismatch"; }
    } else { $update_status = "wrong_old"; }
}

try {
    $stmt = $pdo->prepare("SELECT * FROM teachers WHERE id = ?");
    $stmt->execute([$teacher_id]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$teacher) {
        session_destroy();
        header("Location: ../login.php?error=session_expired");
        exit();
    }

    $t_grade = $teacher['grade_handle'] ?? 'N/A';
    $t_section = $teacher['section'] ?? 'N/A';

    // 1. Fetch Students
    $sql_students = "SELECT s.*, 
                     COALESCE(sp.words_correct, 0) as words_correct, 
                     COALESCE(sp.words_attempted, 0) as words_attempted,
                     (SELECT AVG(duration) FROM student_ratings WHERE student_id = s.id AND duration > 0) as avg_speed
                     FROM students s
                     LEFT JOIN student_progress_main sp ON s.id = sp.student_id
                     WHERE s.grade = ? AND s.section = ?
                     ORDER BY s.fullname ASC";
    $stmt_students = $pdo->prepare($sql_students);
    $stmt_students->execute([$t_grade, $t_section]);
    $students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

    // 2. Stats
    $total_c = array_sum(array_column($students, 'words_correct'));
    $total_a = array_sum(array_column($students, 'words_attempted'));
    $class_progress = ($total_a > 0) ? round(($total_c / $total_a) * 100) : 0;
    $all_speeds = array_filter(array_column($students, 'avg_speed'));
    $class_avg_speed = !empty($all_speeds) ? round(array_sum($all_speeds) / count($all_speeds), 2) : 0;

    $sql_class_stars = "SELECT AVG(sr.score) as class_avg_stars FROM student_ratings sr JOIN students s ON sr.student_id = s.id WHERE s.grade = ? AND s.section = ?";
    $stmt_stars = $pdo->prepare($sql_class_stars);
    $stmt_stars->execute([$t_grade, $t_section]);
    $stars_data = $stmt_stars->fetch(PDO::FETCH_ASSOC);
    $class_stars = ($stars_data['class_avg_stars']) ? round($stars_data['class_avg_stars'], 1) : 0;

    $mastered_count = 0;
    foreach ($students as $s) { if (isset($s['mastery_unlocked']) && $s['mastery_unlocked'] == 1) $mastered_count++; }

    // 3. Top 5
    $sql_top5 = "SELECT s.fullname, AVG(sr.score) AS avg_star_rating FROM students s JOIN student_ratings sr ON s.id = sr.student_id WHERE s.grade = ? AND s.section = ? GROUP BY s.id ORDER BY avg_star_rating DESC LIMIT 5";
    $stmt_top5 = $pdo->prepare($sql_top5);
    $stmt_top5->execute([$t_grade, $t_section]);
    $top5_data = $stmt_top5->fetchAll(PDO::FETCH_ASSOC);

    $chart_labels = json_encode(array_column($top5_data, 'fullname'));
    $chart_stars = json_encode(array_column($top5_data, 'avg_star_rating'));

} catch (PDOException $e) { die("Database Error: " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verbal | Fit-to-Screen Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --accent: #4318FF; --bg-body: #F4F7FE; --text-dark: #1B2559;
            --text-gray: #A3AED0; --sidebar-dark: #111C44; --shadow: 14px 17px 40px 4px rgba(112, 144, 176, 0.08);
        }

        html, body { height: 100vh; width: 100vw; margin: 0; padding: 0; overflow: hidden; background: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; }
        * { box-sizing: border-box; -ms-overflow-style: none; scrollbar-width: none; }
        *::-webkit-scrollbar { display: none; }

        .app-container { display: flex; height: 100vh; width: 100vw; overflow: hidden; }

        /* SIDEBAR */
        .sidebar { width: 280px; background: var(--sidebar-dark); padding: 40px 25px; display: flex; flex-direction: column; color: white; flex-shrink: 0; height: 100vh; }
        .logo { font-size: 1.6rem; font-weight: 800; margin-bottom: 40px; }
        .nav-link { display: flex; align-items: center; padding: 14px 20px; color: var(--text-gray); text-decoration: none; border-radius: 18px; font-weight: 700; transition: 0.3s; margin-bottom: 5px; cursor: pointer; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 255, 255, 0.05); color: white; }
        .nav-link.active { background: var(--accent); box-shadow: 0px 10px 20px rgba(67, 24, 255, 0.3); }
        .logout-btn { margin-top: auto; color: #ff5f5f; border: 1px solid rgba(255, 95, 95, 0.2); text-align: center; cursor: pointer; padding: 12px; border-radius: 15px; font-weight: 700; transition: 0.3s; }
        .logout-btn:hover { background: #ff5f5f; color: white; }

        /* MAIN */
        .main-wrapper { flex: 1; padding: 25px 35px; display: flex; flex-direction: column; height: 100vh; overflow: hidden; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-shrink: 0; }
        .search-wrapper input { width: 350px; padding: 10px 25px; border-radius: 50px; border: none; background: white; box-shadow: var(--shadow); outline: none; font-weight: 600; }
        .profile-info { display: flex; align-items: center; gap: 12px; background: white; padding: 6px 18px; border-radius: 50px; box-shadow: var(--shadow); }
        .profile-info img { width: 32px; height: 32px; border-radius: 50%; border: 2px solid var(--accent); }

        .hero-banner { background: linear-gradient(135deg, #4318FF 0%, #991BFF 100%); padding: 30px; border-radius: 25px; color: white; margin-bottom: 20px; flex-shrink: 0; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px; flex-shrink: 0; }
        .stat-card { background: white; padding: 20px; border-radius: 20px; box-shadow: var(--shadow); }
        .stat-card h2 { font-size: 1.5rem; font-weight: 800; margin-top: 5px; }

        .content-grid { display: grid; grid-template-columns: 1.8fr 1fr; gap: 20px; flex: 1; min-height: 0; }
        .card { background: white; padding: 25px; border-radius: 25px; box-shadow: var(--shadow); display: flex; flex-direction: column; overflow: hidden; }
        .rank-list-scroll { flex: 1; overflow-y: auto; margin-top: 15px; }
        .rank-item { display: flex; align-items: center; padding: 12px 18px; border-radius: 18px; background: #f8faff; margin-bottom: 10px; }
        .rank-num { width: 35px; height: 35px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 800; color: white; margin-right: 15px; }

        /* MODAL & SHOW/HIDE PASSWORD CSS */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; z-index: 9999; }
        .modal-card { background: white; padding: 30px; border-radius: 25px; width: 400px; box-shadow: var(--shadow); }

        /* --- UPDATED: Password Container --- */
        .pass-container { position: relative; width: 100%; }
        .pass-container input { width: 100%; padding: 12px 40px 12px 12px; border-radius: 12px; border: 1px solid #eee; outline: none; font-family: inherit; }
        .toggle-icon { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-gray); transition: 0.2s; }
        .toggle-icon:hover { color: var(--accent); }

        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 0.8rem; font-weight: 700; margin-bottom: 5px; color: var(--text-gray); }
        .modal-btns { display: flex; gap: 10px; margin-top: 20px; }
        .btn-update { background: var(--accent); color: white; border: none; padding: 12px; flex: 1; border-radius: 12px; font-weight: 700; cursor: pointer; }
        .btn-cancel { background: #eee; color: var(--text-dark); border: none; padding: 12px; flex: 1; border-radius: 12px; font-weight: 700; cursor: pointer; }
    </style>
</head>
<body>

<div class="app-container">
    <aside class="sidebar">
        <div class="logo">🟣 Verbal.</div>
        <nav style="display:flex; flex-direction:column; gap:3px;">
            <a href="teacher_dashboard.php" class="nav-link active"><i class="fas fa-th-large"></i> &nbsp; Dashboard</a>
            <a href="student-page.php" class="nav-link"><i class="fas fa-user-graduate"></i> &nbsp; Students</a>
            <a href="class-reports.php" class="nav-link"><i class="fas fa-chart-pie"></i> &nbsp; Reports</a>
            <div class="nav-link" onclick="openSettings()"><i class="fas fa-cog"></i> &nbsp; Settings</div>
        </nav>
        <div class="logout-btn" onclick="confirmLogout()">Logout Account</div>
    </aside>

    <main class="main-wrapper">
        <header class="top-bar">
            <div class="search-wrapper"><input type="text" id="perfSearch" placeholder="🔍 Search performance..." onkeyup="filterTop()"></div>
            <div class="profile-info">
                <div style="text-align: right;"><p style="font-weight:800; font-size:0.8rem; margin:0;"><?= htmlspecialchars($teacher['fullname']) ?></p></div>
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($teacher['fullname']) ?>&background=4318FF&color=fff&bold=true" alt="Profile">
            </div>
        </header>

        <section class="hero-banner">
            <h1 style="font-size:1.8rem; font-weight:800; margin:0;">Section <?= htmlspecialchars($t_section) ?></h1>
            <p style="font-size:0.95rem; opacity:0.9; margin:5px 0 0 0;">Monitoring Class Fluency and Ratings.</p>
        </section>

        <div class="stats-grid">
            <div class="stat-card"><small>Accuracy</small><h2 style="color:var(--accent);"><?= $class_progress ?>%</h2></div>
            <div class="stat-card"><small>Avg. Speed</small><h2 style="color:#991BFF;"><?= $class_avg_speed ?>s</h2></div>
            <div class="stat-card"><small>Stars</small><h2 style="color:#ffb547;"><i class="fas fa-star"></i> <?= $class_stars ?></h2></div>
            <div class="stat-card"><small>Mastery</small><h2 style="color:#05cd99;"><?= $mastered_count ?></h2></div>
        </div>

        <div class="content-grid">
            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <h3 style="font-weight:800; font-size:1.1rem; margin:0;">🏆 Top Performers</h3>
                </div>
                <div class="rank-list-scroll" id="perfList">
                    <?php $colors = ['#4318FF', '#05cd99', '#ffb547', '#ff5f5f', '#991BFF'];
                    foreach($top5_data as $idx => $s): ?>
                        <div class="rank-item" data-name="<?= strtolower($s['fullname']) ?>">
                            <div class="rank-num" style="background: <?= $colors[$idx] ?>;"><?= $idx + 1 ?></div>
                            <div style="flex:1;"><p style="font-weight:800; font-size:0.9rem; margin:0;"><?= htmlspecialchars($s['fullname']) ?></p></div>
                            <div style="text-align:right;"><p style="font-weight:800; font-size:1.1rem; color:#ffb547; margin:0;"><?= number_format($s['avg_star_rating'], 1) ?> <i class="fas fa-star"></i></p></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="card">
                <h3 style="font-weight:800; font-size:1.1rem; margin-bottom:10px;">Star Distribution</h3>
                <div class="chart-wrap"><canvas id="top5Chart"></canvas></div>
            </div>
        </div>
    </main>
</div>

<div class="modal-overlay" id="settingsModal">
    <div class="modal-card animate__animated animate__fadeInDown">
        <h3>Change Password</h3>
        <form method="POST">
            <div class="form-group">
                <label>Current Password</label>
                <div class="pass-container">
                    <input type="password" name="current_password" id="cur_pass" required>
                    <i class="fas fa-eye toggle-icon" onclick="togglePass('cur_pass', this)"></i>
                </div>
            </div>
            <div class="form-group">
                <label>New Password</label>
                <div class="pass-container">
                    <input type="password" name="new_password" id="new_pass" required minlength="6">
                    <i class="fas fa-eye toggle-icon" onclick="togglePass('new_pass', this)"></i>
                </div>
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <div class="pass-container">
                    <input type="password" name="confirm_password" id="conf_pass" required>
                    <i class="fas fa-eye toggle-icon" onclick="togglePass('conf_pass', this)"></i>
                </div>
            </div>
            <div class="modal-btns">
                <button type="button" class="btn-cancel" onclick="closeSettings()">Cancel</button>
                <button type="submit" name="change_pass" class="btn-update">Update Password</button>
            </div>
        </form>
    </div>
</div>

<script>
    // --- UPDATED: Toggle Password Visibility Function ---
    function togglePass(inputId, icon) {
        const input = document.getElementById(inputId);
        if (input.type === "password") {
            input.type = "text";
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = "password";
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }

    new Chart(document.getElementById('top5Chart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: <?= $chart_labels ?>,
            datasets: [{ data: <?= $chart_stars ?>, backgroundColor: ['#4318FF', '#05cd99', '#ffb547', '#ff5f5f', '#991BFF'], borderWidth: 0 }]
        },
        options: { cutout: '80%', maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });

    function filterTop() {
        let val = document.getElementById('perfSearch').value.toLowerCase();
        let items = document.getElementsByClassName('rank-item');
        Array.from(items).forEach(item => { item.style.display = item.getAttribute('data-name').includes(val) ? "flex" : "none"; });
    }

    function confirmLogout() {
        Swal.fire({ title: 'Logout?', text: "Gusto mo na bang lumabas?", icon: 'warning', showCancelButton: true, confirmButtonColor: '#4318FF', confirmButtonText: 'Yes, Logout' })
            .then((result) => { if (result.isConfirmed) window.location.href = "../login.php"; });
    }

    function openSettings() { document.getElementById('settingsModal').style.display = 'flex'; }
    function closeSettings() { document.getElementById('settingsModal').style.display = 'none'; }

    <?php if($update_status === "success"): ?> Swal.fire('Success!', 'Password updated.', 'success');
    <?php elseif($update_status === "wrong_old"): ?> Swal.fire('Error!', 'Mali ang current password.', 'error');
    <?php elseif($update_status === "mismatch"): ?> Swal.fire('Error!', 'Hindi magkatugma ang bagong password.', 'error');
    <?php endif; ?>
</script>
</body>
</html>