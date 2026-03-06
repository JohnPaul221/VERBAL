<?php
session_start();
require_once '../config/database.php';
global $pdo;

// 1. SECURITY CHECK
if (!isset($_SESSION['principal_id']) || $_SESSION['role'] !== 'principal') {
    header("Location: ../login.php");
    exit();
}

$status = $_GET['status'] ?? "";

// --- ADD TEACHER LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_teacher'])) {
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // PRO TIP: Always Hash Passwords
    $grade    = $_POST['grade_handle'];
    $section  = trim($_POST['section']);

    try {
        $check = $pdo->prepare("SELECT id FROM teachers WHERE username = ?");
        $check->execute([$username]);
        if ($check->rowCount() > 0) { $status = "exists"; }
        else {
            $insert = $pdo->prepare("INSERT INTO teachers (fullname, username, password, grade_handle, section, role) VALUES (?, ?, ?, ?, ?, 'teacher')");
            $insert->execute([$fullname, $username, $password, $grade, $section]);
            header("Location: manage_teachers.php?status=success");
            exit();
        }
    } catch (PDOException $e) { $status = "error"; }
}

// --- DELETE TEACHER LOGIC ---
if (isset($_GET['delete_id'])) {
    $del = $pdo->prepare("DELETE FROM teachers WHERE id = ?");
    $del->execute([$_GET['delete_id']]);
    header("Location: manage_teachers.php?status=deleted");
    exit();
}

$stmt = $pdo->query("SELECT * FROM teachers ORDER BY grade_handle ASC, section ASC");
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Management | Pro Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --accent: #4318FF;
            --accent-light: #EEF0FF;
            --bg-body: #F4F7FE;
            --text-dark: #1B2559;
            --text-gray: #8F9BBA;
            --sidebar-dark: #111C44;
            --white: #ffffff;
            --shadow-sm: 0px 4px 12px rgba(0, 0, 0, 0.03);
            --shadow-lg: 14px 17px 40px 4px rgba(112, 144, 176, 0.15);
            --radius: 20px;
        }

        * { box-sizing: border-box; transition: all 0.2s ease-in-out; }
        body { margin: 0; display: flex; height: 100vh; background: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; color: var(--text-dark); overflow: hidden; }

        /* Sidebar Styling */
        .sidebar { width: 290px; background: var(--sidebar-dark); padding: 40px 25px; display: flex; flex-direction: column; flex-shrink: 0; }
        .logo { font-size: 1.8rem; font-weight: 800; color: white; margin-bottom: 50px; display: flex; align-items: center; gap: 12px; }
        .nav-link { display: flex; align-items: center; padding: 16px 22px; color: #A3AED0; text-decoration: none; border-radius: 15px; font-weight: 700; margin-bottom: 10px; }
        .nav-link.active { background: var(--accent); color: white; box-shadow: 0px 12px 20px -5px rgba(67, 24, 255, 0.4); }
        .nav-link:hover:not(.active) { background: rgba(255, 255, 255, 0.08); color: white; }

        /* Main Layout */
        .main-content { flex: 1; overflow-y: auto; padding: 40px; }
        .header-section { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 35px; }

        /* Stats Bar */
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 35px; }
        .stat-card { background: white; padding: 20px; border-radius: var(--radius); display: flex; align-items: center; gap: 15px; box-shadow: var(--shadow-sm); }
        .stat-icon { width: 45px; height: 45px; border-radius: 12px; background: var(--accent-light); color: var(--accent); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }

        /* Search & Controls */
        .controls-row { display: flex; gap: 15px; margin-bottom: 25px; }
        .search-wrapper { position: relative; flex: 1; }
        .search-wrapper i { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: var(--text-gray); }
        .search-input { width: 100%; padding: 14px 14px 14px 45px; border-radius: 15px; border: none; background: white; font-weight: 600; box-shadow: var(--shadow-sm); outline: none; }
        .search-input:focus { box-shadow: 0 0 0 2px var(--accent); }

        /* Teacher Cards */
        .grid-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; }
        .teacher-card { background: white; padding: 24px; border-radius: 24px; box-shadow: var(--shadow-sm); border: 1px solid transparent; position: relative; }
        .teacher-card:hover { transform: translateY(-8px); box-shadow: var(--shadow-lg); border-color: var(--accent-light); }

        .profile-stack { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; }
        .avatar { width: 50px; height: 50px; border-radius: 14px; background: linear-gradient(135deg, #4318FF 0%, #7B5CFF 100%); color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.2rem; }

        .badge { display: inline-block; padding: 4px 12px; border-radius: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; margin-top: 8px; }
        .badge-grade { background: var(--accent-light); color: var(--accent); }

        .action-btns { position: absolute; top: 20px; right: 20px; display: flex; gap: 8px; }
        .btn-icon { width: 32px; height: 32px; border-radius: 8px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; background: #F4F7FE; color: var(--text-gray); }
        .btn-icon:hover { background: #fee2e2; color: #ef4444; }

        /* Modal Redesign */
        .modal { position: fixed; inset: 0; background: rgba(11, 20, 50, 0.7); backdrop-filter: blur(6px); display: none; align-items: center; justify-content: center; z-index: 2000; padding: 20px; }
        .modal-content { background: white; padding: 40px; border-radius: 30px; width: 100%; max-width: 480px; position: relative; animation: slideUp 0.3s ease-out; }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 12px; font-weight: 800; color: var(--text-dark); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px; }
        .form-control { width: 100%; padding: 14px; border-radius: 12px; border: 2px solid #E9EDF7; font-family: inherit; font-weight: 600; outline: none; }
        .form-control:focus { border-color: var(--accent); }

        .btn-primary { background: var(--accent); color: white; border: none; padding: 16px; border-radius: 14px; font-weight: 800; width: 100%; cursor: pointer; box-shadow: 0 10px 20px -5px rgba(67, 24, 255, 0.4); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 14px 24px -5px rgba(67, 24, 255, 0.5); }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="logo">
        <i class="fas fa-bolt" style="color: #FFD700;"></i>
        <span>Verbal<span style="color: var(--accent);">.</span></span>
    </div>
    <nav>
        <a href="principal_dashboard.php" class="nav-link"><i class="fas fa-th-large"></i> &nbsp;&nbsp; Dashboard</a>
        <a href="manage_teachers.php" class="nav-link active"><i class="fas fa-user-tie"></i> &nbsp;&nbsp; Faculty</a>
        <a href="all_reports.php" class="nav-link"><i class="fas fa-chart-line"></i> &nbsp;&nbsp; Reports</a>
    </nav>
    <a href="../logout.php" style="margin-top: auto; color: #ff5f5f; text-decoration: none; font-weight: 800; padding: 15px; text-align: center; background: rgba(255, 95, 95, 0.05); border-radius: 12px; border: 1px dashed rgba(255,95,95,0.3);">
        <i class="fas fa-sign-out-alt"></i> Sign Out
    </a>
</aside>

<main class="main-content">
    <header class="header-section">
        <div>
            <h1 style="font-size: 2.2rem; font-weight: 800; margin: 0; letter-spacing: -1px;">Faculty Management</h1>
            <p style="color: var(--text-gray); font-weight: 600; margin-top: 5px;">Register and monitor teaching staff</p>
        </div>
        <button onclick="toggleModal(true)" class="btn-primary" style="width: auto; padding: 14px 30px;">
            <i class="fas fa-plus"></i> &nbsp; Add New Teacher
        </button>
    </header>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div>
                <div style="font-size: 0.8rem; color: var(--text-gray); font-weight: 700;">Total Faculty</div>
                <div style="font-size: 1.4rem; font-weight: 800;"><?= count($teachers) ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #E6FFFA; color: #00A389;"><i class="fas fa-graduation-cap"></i></div>
            <div>
                <div style="font-size: 0.8rem; color: var(--text-gray); font-weight: 700;">Grades Covered</div>
                <div style="font-size: 1.4rem; font-weight: 800;">6 Levels</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #FFF5F5; color: #E53E3E;"><i class="fas fa-shield-alt"></i></div>
            <div>
                <div style="font-size: 0.8rem; color: var(--text-gray); font-weight: 700;">System Role</div>
                <div style="font-size: 1.4rem; font-weight: 800;">Administrator</div>
            </div>
        </div>
    </div>

    <div class="controls-row">
        <div class="search-wrapper">
            <i class="fas fa-search"></i>
            <input type="text" id="teacherSearch" class="search-input" placeholder="Search by name, grade, or section...">
        </div>
    </div>

    <div class="grid-container" id="teacherGrid">
        <?php foreach ($teachers as $t): ?>
            <div class="teacher-card" data-name="<?= strtolower($t['fullname']) ?>" data-meta="<?= $t['grade_handle'] ?> <?= strtolower($t['section']) ?>">
                <div class="action-btns">
                    <button class="btn-icon" onclick="confirmDelete(<?= $t['id'] ?>)"><i class="fas fa-trash"></i></button>
                </div>
                <div class="profile-stack">
                    <div class="avatar"><?= strtoupper(substr($t['fullname'], 0, 1)) ?></div>
                    <div>
                        <h3 style="margin: 0; font-size: 1.1rem; font-weight: 800; color: var(--text-dark);"><?= htmlspecialchars($t['fullname']) ?></h3>
                        <span class="badge badge-grade">Grade <?= $t['grade_handle'] ?> - Section <?= htmlspecialchars($t['section']) ?></span>
                    </div>
                </div>
                <div style="background: #F8F9FF; padding: 12px; border-radius: 12px; margin-top: 15px; border: 1px solid #EEF0FF;">
                    <div style="font-size: 11px; font-weight: 800; color: var(--text-gray); text-transform: uppercase;">Login Username</div>
                    <div style="font-weight: 700; color: var(--accent);"><?= htmlspecialchars($t['username']) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<div class="modal" id="addModal">
    <div class="modal-content">
        <h2 style="font-weight: 800; margin-bottom: 10px;">Register Faculty</h2>
        <p style="color: var(--text-gray); font-weight: 600; margin-bottom: 25px; font-size: 0.9rem;">Assign a new teacher to a specific grade and section.</p>

        <form method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="fullname" class="form-control" placeholder="e.g. Jane Doe" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" placeholder="jdoe_2024" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 15px;">
                <div class="form-group">
                    <label>Grade Level</label>
                    <select name="grade_handle" class="form-control">
                        <?php for($i=1;$i<=6;$i++): ?>
                            <option value="<?= $i ?>">Grade <?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Section Name</label>
                    <input type="text" name="section" class="form-control" placeholder="e.g. Diamond" required>
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 20px;">
                <button type="submit" name="add_teacher" class="btn-primary">Create Account</button>
                <button type="button" onclick="toggleModal(false)" style="flex: 0.5; background: #F4F7FE; border: none; border-radius: 14px; font-weight: 800; cursor: pointer; color: var(--text-dark);">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleModal(show) {
        const modal = document.getElementById('addModal');
        modal.style.display = show ? 'flex' : 'none';
    }

    // Modern Real-time Search Logic
    document.getElementById('teacherSearch').addEventListener('input', function(e) {
        const term = e.target.value.toLowerCase();
        const cards = document.querySelectorAll('.teacher-card');

        cards.forEach(card => {
            const content = card.getAttribute('data-name') + ' ' + card.getAttribute('data-meta');
            card.style.display = content.includes(term) ? 'block' : 'none';
        });
    });

    function confirmDelete(id) {
        Swal.fire({
            title: 'Remove Faculty?',
            text: "This action cannot be undone. Data associated with this account will be lost.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#4318FF',
            cancelButtonColor: '#EDF2F7',
            confirmButtonText: 'Yes, delete account',
            cancelButtonText: '<span style="color: #1B2559">Cancel</span>',
            background: '#ffffff',
            borderRadius: '25px'
        }).then((result) => {
            if (result.isConfirmed) window.location.href = 'manage_teachers.php?delete_id=' + id;
        })
    }

    // Handle Alerts from PHP
    <?php if($status === 'success'): ?>
    Swal.fire({ icon: 'success', title: 'Teacher Added', showConfirmButton: false, timer: 1500 });
    <?php elseif($status === 'exists'): ?>
    Swal.fire({ icon: 'error', title: 'Username Taken', text: 'Please try a different username.' });
    <?php elseif($status === 'deleted'): ?>
    Swal.fire({ icon: 'success', title: 'Account Removed', showConfirmButton: false, timer: 1500 });
    <?php endif; ?>
</script>

</body>
</html>