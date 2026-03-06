<?php
session_start();
require_once '../config/database.php';
global $pdo;

// 1. SECURITY CHECK - Principal only
if (!isset($_SESSION['principal_id']) || $_SESSION['role'] !== 'principal') {
    header("Location: ../login.php");
    exit();
}

// 2. GET DATA FROM URL
$grade = $_GET['grade'] ?? '';
$section = $_GET['section'] ?? '';

if (empty($grade) || empty($section)) {
    header("Location: principal_dashboard.php");
    exit();
}

try {
    // Kunin ang mga estudyante na may online status logic
    $stmt = $pdo->prepare("SELECT *, 
                          (CASE WHEN last_login > NOW() - INTERVAL 5 MINUTE THEN 1 ELSE 0 END) as is_online 
                          FROM students 
                          WHERE grade = ? AND section = ? 
                          ORDER BY fullname ASC");
    $stmt->execute([$grade, $section]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Kunin ang info ng Adviser
    $stmt_t = $pdo->prepare("SELECT fullname FROM teachers WHERE grade_handle = ? AND section = ?");
    $stmt_t->execute([$grade, $section]);
    $teacher = $stmt_t->fetch(PDO::FETCH_ASSOC);

    // Quick Stats para sa Principal
    $total_students = count($students);
    $online_count = array_sum(array_column($students, 'is_online'));
    $male_count = count(array_filter($students, fn($s) => strtolower($s['sex'] ?? '') == 'male'));
    $female_count = count(array_filter($students, fn($s) => strtolower($s['sex'] ?? '') == 'female'));

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade <?= $grade ?> - <?= htmlspecialchars($section) ?> | Directory</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4318FF;
            --bg-body: #F4F7FE;
            --sidebar: #111C44;
            --text-main: #1B2559;
            --text-secondary: #A3AED0;
            --success: #05CD99;
            --card-bg: #FFFFFF;
            --shadow: 14px 17px 40px 4px rgba(112, 144, 176, 0.08);
            --border: #E9EDF7;
        }

        body { margin: 0; display: flex; height: 100vh; background: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; color: var(--text-main); overflow: hidden; }

        /* Sidebar Navigation */
        .sidebar { width: 290px; background: var(--sidebar); padding: 40px 25px; display: flex; flex-direction: column; color: white; flex-shrink: 0; }
        .logo { font-size: 1.8rem; font-weight: 800; margin-bottom: 50px; color: white; text-decoration: none; display: flex; align-items: center; gap: 10px; }
        .nav-link { display: flex; align-items: center; padding: 16px 20px; color: var(--text-secondary); text-decoration: none; border-radius: 20px; font-weight: 700; transition: 0.3s; margin-bottom: 10px; }
        .nav-link:hover { color: white; background: rgba(255,255,255,0.05); }
        .nav-link.active { background: var(--primary); color: white; box-shadow: 0px 10px 20px rgba(67, 24, 255, 0.3); }

        /* Main Area */
        .main-wrapper { flex: 1; padding: 30px 40px; overflow-y: auto; }
        .header-section { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 35px; }
        .back-btn { display: inline-flex; align-items: center; color: var(--primary); text-decoration: none; font-weight: 800; font-size: 0.9rem; margin-bottom: 15px; gap: 8px; }

        /* Stats Cards */
        .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 35px; }
        .stat-card { background: var(--card-bg); padding: 20px; border-radius: 20px; box-shadow: var(--shadow); display: flex; align-items: center; gap: 15px; }
        .stat-icon { width: 50px; height: 50px; border-radius: 15px; background: var(--bg-body); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: var(--primary); }
        .stat-info span { font-size: 0.85rem; color: var(--text-secondary); font-weight: 600; }
        .stat-info h3 { margin: 0; font-size: 1.4rem; font-weight: 800; }

        /* Search */
        .table-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .search-box { position: relative; width: 320px; }
        .search-box input { width: 100%; padding: 12px 20px 12px 45px; border-radius: 30px; border: 1px solid var(--border); outline: none; font-family: inherit; font-weight: 600; }
        .search-box i { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: var(--text-secondary); }

        /* Table Design */
        .table-card { background: var(--card-bg); border-radius: 25px; padding: 15px; box-shadow: var(--shadow); }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { text-align: left; padding: 15px; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase; border-bottom: 1px solid var(--border); }
        .data-table td { padding: 18px 15px; border-bottom: 1px solid var(--border); vertical-align: middle; }

        /* Clickable Row Effect */
        .student-row { cursor: pointer; transition: 0.2s; }
        .student-row:hover { background: #F9FAFF; transform: translateY(-2px); box-shadow: 0px 4px 12px rgba(0,0,0,0.03); }

        .user-cell { display: flex; align-items: center; gap: 15px; }
        .avatar { width: 45px; height: 45px; border-radius: 14px; background: #E9EDF7; display: flex; align-items: center; justify-content: center; font-weight: 800; color: var(--primary); position: relative; font-size: 1.1rem; }
        .online-dot { position: absolute; bottom: -3px; right: -3px; width: 12px; height: 12px; border-radius: 50%; border: 3px solid white; }
        .online { background: var(--success); }
        .offline { background: #CBD5E0; }

        .badge { padding: 6px 12px; border-radius: 10px; font-size: 0.75rem; font-weight: 800; display: inline-flex; align-items: center; gap: 5px; }
        .badge-success { background: rgba(5, 205, 153, 0.1); color: var(--success); }
        .badge-gray { background: #F4F7FE; color: var(--text-secondary); }
        .view-btn { background: #F4F7FE; color: var(--primary); padding: 8px 15px; border-radius: 10px; font-size: 0.8rem; font-weight: 800; text-decoration: none; transition: 0.3s; }
        .view-btn:hover { background: var(--primary); color: white; }
    </style>
</head>
<body>

<aside class="sidebar">
    <a href="#" class="logo">🟣 <span>Verbal.</span></a>
    <nav>
        <a href="principal_dashboard.php" class="nav-link active"><i class="fas fa-grid-2"></i> &nbsp; Section Directory</a>
        <a href="manage_teachers.php" class="nav-link"><i class="fas fa-user-tie"></i> &nbsp; Teacher Faculty</a>
    </nav>
</aside>

<main class="main-wrapper">
    <div class="header-section">
        <div>
            <a href="principal_dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> BACK TO SECTIONS</a>
            <h1 style="font-size: 2.2rem; font-weight: 800; margin: 0; letter-spacing: -1px;">Grade <?= $grade ?> — <?= htmlspecialchars($section) ?></h1>
            <p style="color: var(--text-secondary); font-weight: 600; margin-top: 5px;">
                Adviser: <span style="color: var(--text-main);"><?= $teacher ? htmlspecialchars($teacher['fullname']) : 'Unassigned' ?></span>
            </p>
        </div>
        <button onclick="window.print()" class="nav-link active" style="border:none; cursor:pointer; font-size: 0.85rem;">
            <i class="fas fa-print"></i> &nbsp; EXPORT LIST
        </button>
    </div>

    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-info"><span>Total Students</span><h3><?= $total_students ?></h3></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="color: #05CD99;"><i class="fas fa-circle-check"></i></div>
            <div class="stat-info"><span>Currently Online</span><h3><?= $online_count ?></h3></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="color: #39B1FF;"><i class="fas fa-mars"></i></div>
            <div class="stat-info"><span>Male</span><h3><?= $male_count ?></h3></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="color: #FF5B99;"><i class="fas fa-venus"></i></div>
            <div class="stat-info"><span>Female</span><h3><?= $female_count ?></h3></div>
        </div>
    </div>

    <div class="table-actions">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="studentSearch" placeholder="Find student by name...">
        </div>
    </div>

    <div class="table-card">
        <table class="data-table">
            <thead>
            <tr>
                <th>Full Name</th>
                <th>Sex</th>
                <th>Last Active</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody id="studentTableBody">
            <?php foreach ($students as $s): ?>
                <tr class="student-row" onclick="window.location='../teacher/student-report.php?student_id=<?= $s['id'] ?>&name=<?= urlencode($s['fullname']) ?>'">
                    <td>
                        <div class="user-cell">
                            <div class="avatar">
                                <?= strtoupper(substr($s['fullname'] ?? 'S', 0, 1)) ?>
                                <div class="online-dot <?= $s['is_online'] ? 'online' : 'offline' ?>"></div>
                            </div>
                            <div>
                                <div style="font-weight: 800; font-size: 0.95rem;"><?= htmlspecialchars($s['fullname']) ?></div>
                                <div style="font-size: 0.7rem; color: var(--text-secondary);">Click for Analytics</div>
                            </div>
                        </div>
                    </td>
                    <td style="font-weight: 700;"><?= htmlspecialchars($s['sex']) ?></td>
                    <td style="color: var(--text-secondary); font-weight: 600; font-size: 0.85rem;">
                        <?= $s['last_login'] ? date('M d, h:i A', strtotime($s['last_login'])) : '---' ?>
                    </td>
                    <td>
                        <?php if($s['is_online']): ?>
                            <span class="badge badge-success">ONLINE</span>
                        <?php else: ?>
                            <span class="badge badge-gray">OFFLINE</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="../teacher/student-report.php?student_id=<?= $s['id'] ?>&name=<?= urlencode($s['fullname']) ?>" class="view-btn">
                            <i class="fas fa-chart-line"></i> Report
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<script>
    // Search logic
    document.getElementById('studentSearch').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('#studentTableBody tr');
        rows.forEach(row => {
            let name = row.querySelector('.user-cell div div').textContent.toLowerCase();
            row.style.display = name.includes(filter) ? '' : 'none';
        });
    });
</script>

</body>
</html>