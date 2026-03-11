<?php
session_start();
require_once '../config/database.php';
global $pdo;

if (!isset($_SESSION['principal_id']) || $_SESSION['role'] !== 'principal') {
    header("Location: ../login.php");
    exit();
}

$principal_id = $_SESSION['principal_id'];

// --- PROFILE & PASSWORD UPDATE LOGIC ---
$update_status = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $new_fullname = $_POST['fullname'];

    $current_pass = $_POST['current_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    // Step 1: Update Basic Info
    $stmt_info = $pdo->prepare("UPDATE principals SET fullname = ? WHERE id = ?");
    if ($stmt_info->execute([$new_fullname, $principal_id])) {
        $_SESSION['fullname'] = $new_fullname;
        $update_status = "success_info";

        // Step 2: Password Update Logic
        if (!empty($current_pass)) {
            $stmt_check = $pdo->prepare("SELECT password FROM principals WHERE id = ?");
            $stmt_check->execute([$principal_id]);
            $user = $stmt_check->fetch();

            if (password_verify($current_pass, $user['password'])) {
                if (!empty($new_pass) && $new_pass === $confirm_pass) {
                    $hashed = password_hash($new_pass, PASSWORD_BCRYPT);
                    $stmt_up = $pdo->prepare("UPDATE principals SET password = ? WHERE id = ?");
                    $stmt_up->execute([$hashed, $principal_id]);
                    $update_status = "success_all";
                } else { $update_status = "mismatch"; }
            } else { $update_status = "wrong_old"; }
        }
    }
}

try {
    $stmt_p = $pdo->prepare("SELECT * FROM principals WHERE id = ?");
    $stmt_p->execute([$principal_id]);
    $principal_data = $stmt_p->fetch(PDO::FETCH_ASSOC);
    $principal_name = $principal_data['fullname'];

    $total_students = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
    $total_teachers = $pdo->query("SELECT COUNT(*) FROM teachers")->fetchColumn();

    $sql_sections = "SELECT 
                        t.fullname as teacher_name, 
                        t.grade_handle, 
                        t.section,
                        (SELECT COUNT(*) FROM students s WHERE s.grade = t.grade_handle AND s.section = t.section) as student_count,
                        (SELECT COALESCE(SUM(score), 0) FROM student_ratings sr 
                         JOIN students s2 ON sr.student_id = s2.id 
                         WHERE s2.grade = t.grade_handle AND s2.section = t.section) as total_section_xp
                    FROM teachers t 
                    ORDER BY t.grade_handle ASC, t.section ASC";

    $stmt_sections = $pdo->prepare($sql_sections);
    $stmt_sections->execute();
    $sections = $stmt_sections->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { die("Database Error: " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Principal Dashboard | Verbal Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --accent: #4318FF; --bg-body: #F4F7FE; --text-dark: #1B2559;
            --text-gray: #A3AED0; --sidebar-dark: #111C44; --white: #ffffff;
            --shadow: 14px 17px 40px 4px rgba(112, 144, 176, 0.08);
        }
        body { margin: 0; display: flex; height: 100vh; background: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; overflow: hidden; }
        .sidebar { width: 280px; background: var(--sidebar-dark); padding: 40px 25px; display: flex; flex-direction: column; color: white; flex-shrink: 0; }
        .logo { font-size: 1.6rem; font-weight: 800; margin-bottom: 50px; }
        .nav-link { display: flex; align-items: center; padding: 16px 20px; color: var(--text-gray); text-decoration: none; border-radius: 20px; font-weight: 700; transition: 0.3s; margin-bottom: 8px; cursor: pointer; }
        .nav-link.active { background: var(--accent); color: white; box-shadow: 0px 10px 20px rgba(67, 24, 255, 0.3); }
        .main-wrapper { flex: 1; padding: 25px 35px; display: flex; flex-direction: column; overflow-y: auto; }
        .stats-top { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .top-card { background: white; padding: 20px; border-radius: 20px; box-shadow: var(--shadow); display: flex; align-items: center; gap: 15px; }
        .icon-box { width: 50px; height: 50px; border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
        .folder-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 25px; margin-top: 20px; }
        .section-folder { background: var(--white); border-radius: 24px; padding: 25px; box-shadow: var(--shadow); transition: 0.3s; border: 1px solid transparent; cursor: pointer; }
        .section-folder:hover { transform: translateY(-5px); border-color: var(--accent); }
        .folder-icon { font-size: 2.5rem; color: #FFB547; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; }
        .grade-tag { background: rgba(67, 24, 255, 0.1); color: var(--accent); padding: 4px 12px; border-radius: 10px; font-size: 0.75rem; font-weight: 800; }
        .folder-title { font-size: 1.2rem; font-weight: 800; color: var(--text-dark); margin: 0 0 5px 0; }
        .teacher-name { color: var(--text-gray); font-size: 0.85rem; font-weight: 600; margin-bottom: 15px; display: block; }
        .folder-stats { display: flex; justify-content: space-between; background: #F4F7FE; padding: 12px; border-radius: 15px; }
        .stat-box { text-align: center; flex: 1; }
        .stat-box small { display: block; font-size: 0.65rem; color: var(--text-gray); font-weight: 700; text-transform: uppercase; }
        .stat-box span { font-size: 0.9rem; font-weight: 800; color: var(--text-dark); }

        /* MODAL */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(17, 28, 68, 0.6); backdrop-filter: blur(8px); display: none; align-items: center; justify-content: center; z-index: 9999; }
        .modal-card { background: white; padding: 35px; border-radius: 30px; width: 100%; max-width: 420px; box-shadow: 0px 20px 50px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 8px; color: var(--text-dark); }
        .edit-input, .pass-container input { width: 100%; padding: 12px 15px; border-radius: 12px; border: 2px solid #F4F7FE; background: #F4F7FE; font-family: inherit; font-weight: 600; outline: none; transition: 0.3s; box-sizing: border-box; }
        .edit-input:focus, .pass-container input:focus { border-color: var(--accent); background: white; }
        .pass-container { position: relative; }
        .toggle-icon { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-gray); }
        .modal-btns { display: flex; gap: 12px; margin-top: 25px; }
        .btn-update { background: var(--accent); color: white; border: none; padding: 14px; flex: 2; border-radius: 12px; font-weight: 800; cursor: pointer; }
        .btn-cancel { background: #EDF2F7; color: #718096; border: none; padding: 14px; flex: 1; border-radius: 12px; font-weight: 700; cursor: pointer; }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="logo">🟣 <span>Verbal.</span></div>
    <nav>
        <a href="principal_dashboard.php" class="nav-link active"><i class="fas fa-folder-open"></i> &nbsp; Sections</a>
        <a href="manage_teachers.php" class="nav-link"><i class="fas fa-chalkboard-teacher"></i> &nbsp; Teachers</a>
        <a href="all_reports.php" class="nav-link"><i class="fas fa-chart-pie"></i> &nbsp; School Reports</a>
        <div class="nav-link" onclick="openSettings()"><i class="fas fa-cog"></i> &nbsp; Settings</div>
    </nav>
    <a href="../logout.php" style="margin-top: auto; color: #ff5f5f; text-decoration: none; font-weight: 700; padding: 15px; text-align: center; border: 1px solid rgba(255,95,95,0.2); border-radius: 15px;">Logout</a>
</aside>

<main class="main-wrapper">
    <div style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1 style="color: var(--text-dark); font-weight: 800; margin: 0;">School Sections</h1>
            <p style="color: var(--text-gray); font-weight: 600;">Welcome, <?= htmlspecialchars($principal_name) ?>!</p>
        </div>
        <img src="https://ui-avatars.com/api/?name=<?= urlencode($principal_name) ?>&background=4318FF&color=fff&bold=true" style="width: 50px; height: 50px; border-radius: 50%; border: 3px solid white; box-shadow: var(--shadow);">
    </div>

    <div class="stats-top">
        <div class="top-card">
            <div class="icon-box" style="background: rgba(67, 24, 255, 0.1); color: var(--accent);"><i class="fas fa-user-graduate"></i></div>
            <div><small style="color:var(--text-gray); font-weight:700;">TOTAL STUDENTS</small><div style="font-weight:800; font-size:1.2rem;"><?= $total_students ?></div></div>
        </div>
        <div class="top-card">
            <div class="icon-box" style="background: rgba(5, 205, 153, 0.1); color: #05CD99;"><i class="fas fa-chalkboard-teacher"></i></div>
            <div><small style="color:var(--text-gray); font-weight:700;">TOTAL TEACHERS</small><div style="font-weight:800; font-size:1.2rem;"><?= $total_teachers ?></div></div>
        </div>
        <div class="top-card">
            <div class="icon-box" style="background: rgba(255, 181, 71, 0.1); color: #FFB547;"><i class="fas fa-layer-group"></i></div>
            <div><small style="color:var(--text-gray); font-weight:700;">ACTIVE FOLDERS</small><div style="font-weight:800; font-size:1.2rem;"><?= count($sections) ?></div></div>
        </div>
    </div>

    <div class="folder-grid">
        <?php foreach ($sections as $row): ?>
            <div class="section-folder" onclick="window.location.href='section_view.php?section=<?= urlencode($row['section']) ?>&grade=<?= $row['grade_handle'] ?>'">
                <div class="folder-icon">
                    <i class="fas fa-folder"></i>
                    <span class="grade-tag">Grade <?= $row['grade_handle'] ?></span>
                </div>
                <h3 class="folder-title">Section <?= htmlspecialchars($row['section']) ?></h3>
                <span class="teacher-name">Adviser: <?= htmlspecialchars($row['teacher_name']) ?></span>
                <div class="folder-stats">
                    <div class="stat-box"><small>Students</small><span><?= $row['student_count'] ?></span></div>
                    <div style="width: 1px; background: #E0E5F2; margin: 0 10px;"></div>
                    <div class="stat-box"><small>Total XP</small><span style="color: var(--accent);"><?= number_format($row['total_section_xp'] ?? 0) ?></span></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<div class="modal-overlay" id="settingsModal">
    <div class="modal-card animate__animated animate__fadeInDown">
        <h3>Edit Profile</h3>
        <form method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="fullname" class="edit-input" value="<?= htmlspecialchars($principal_data['fullname']) ?>" required>
            </div>
            <div style="border-top: 2px solid #F4F7FE; margin: 20px 0; padding-top: 15px;">
                <p style="font-size: 0.75rem; color: var(--text-gray); font-weight: 800; margin-bottom: 15px;">SECURITY</p>
                <div class="form-group">
                    <label>Current Password</label>
                    <div class="pass-container">
                        <input type="password" name="current_password" id="cur_pass" placeholder="••••••••">
                        <i class="fas fa-eye toggle-icon" onclick="togglePass('cur_pass', this)"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <div class="pass-container">
                        <input type="password" name="new_password" id="new_pass" placeholder="Enter new password">
                        <i class="fas fa-eye toggle-icon" onclick="togglePass('new_pass', this)"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <div class="pass-container">
                        <input type="password" name="confirm_password" id="conf_pass" placeholder="Confirm new password">
                        <i class="fas fa-eye toggle-icon" onclick="togglePass('conf_pass', this)"></i>
                    </div>
                </div>
            </div>
            <div class="modal-btns">
                <button type="button" class="btn-cancel" onclick="closeSettings()">Cancel</button>
                <button type="submit" name="update_profile" class="btn-update">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openSettings() { document.getElementById('settingsModal').style.display = 'flex'; }
    function closeSettings() { document.getElementById('settingsModal').style.display = 'none'; }

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

    // FIXED SWEETALERT LOGIC
    document.addEventListener('DOMContentLoaded', function() {
        <?php if($update_status === "success_all" || $update_status === "success_info"): ?>
        Swal.fire({
            title: 'Updated!',
            text: 'Your profile has been successfully updated.',
            icon: 'success',
            confirmButtonColor: '#4318FF'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'principal_dashboard.php';
            }
        });
        <?php elseif($update_status === "wrong_old"): ?>
        Swal.fire({ title: 'Error!', text: 'Incorrect current password.', icon: 'error', confirmButtonColor: '#4318FF' });
        <?php elseif($update_status === "mismatch"): ?>
        Swal.fire({ title: 'Error!', text: 'New passwords do not match.', icon: 'error', confirmButtonColor: '#4318FF' });
        <?php endif; ?>
    });
</script>
</body>
</html>