<?php
session_start();
require_once '../config/database.php';
global $pdo;

// 1. SECURITY CHECK - Principal only
if (!isset($_SESSION['principal_id']) || $_SESSION['role'] !== 'principal') {
    header("Location: ../login.php");
    exit();
}

$principal_name = $_SESSION['fullname'];

try {
    // Stat 1: Performance by Grade Level (Based on student_progress_main)
    // Ginamit ang 'grade' column base sa SQL dump mo
    $stmt_grade_xp = $pdo->query("SELECT grade, SUM(words_correct) as total_correct 
                                   FROM student_progress_main 
                                   GROUP BY grade ORDER BY grade ASC");
    $grade_data = $stmt_grade_xp->fetchAll(PDO::FETCH_ASSOC);

    // Stat 2: Gender Distribution School-wide
    $stmt_gender = $pdo->query("SELECT sex, COUNT(*) as count FROM students GROUP BY sex");
    $gender_data = $stmt_gender->fetchAll(PDO::FETCH_ASSOC);

    // Stat 3: Top Performing Sections (Top 5 based on ratings score)
    $stmt_top = $pdo->query("SELECT t.section, t.grade_handle, SUM(sr.score) as total_xp 
                              FROM teachers t
                              JOIN students s ON t.section = s.section AND t.grade_handle = s.grade
                              JOIN student_ratings sr ON s.id = sr.student_id
                              GROUP BY t.id ORDER BY total_xp DESC LIMIT 5");
    $top_sections = $stmt_top->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Analytics | Verbal Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --accent: #4318FF;
            --bg-body: #F4F7FE;
            --text-dark: #1B2559;
            --text-gray: #A3AED0;
            --sidebar-dark: #111C44;
            --white: #ffffff;
            --shadow: 14px 17px 40px 4px rgba(112, 144, 176, 0.08);
        }

        body { margin: 0; display: flex; height: 100vh; background: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; overflow: hidden; }

        .sidebar { width: 280px; background: var(--sidebar-dark); padding: 40px 25px; display: flex; flex-direction: column; color: white; flex-shrink: 0; height: 100vh; }
        .logo { font-size: 1.6rem; font-weight: 800; margin-bottom: 50px; }
        .nav-link { display: flex; align-items: center; padding: 16px 20px; color: var(--text-gray); text-decoration: none; border-radius: 20px; font-weight: 700; transition: 0.3s; margin-bottom: 8px; }
        .nav-link.active { background: var(--accent); color: white; box-shadow: 0px 10px 20px rgba(67, 24, 255, 0.3); }

        .main-wrapper { flex: 1; padding: 25px 35px; display: flex; flex-direction: column; overflow-y: auto; height: 100vh; }

        .report-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 25px; margin-top: 20px; }
        .report-card { background: white; padding: 30px; border-radius: 24px; box-shadow: var(--shadow); border: 1px solid #F4F7FE; }

        .top-item { display: flex; justify-content: space-between; padding: 18px 0; border-bottom: 1px solid #F4F7FE; align-items: center; }
        .top-item:last-child { border: none; }
        .xp-badge { background: rgba(5, 205, 153, 0.1); color: #05CD99; padding: 6px 14px; border-radius: 10px; font-weight: 800; }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="logo">🟣 <span>Verbal.</span></div>
    <nav>
        <a href="principal_dashboard.php" class="nav-link"><i class="fas fa-folder-open"></i> &nbsp; Directory</a>
        <a href="manage_teachers.php" class="nav-link"><i class="fas fa-chalkboard-teacher"></i> &nbsp; Teachers</a>
        <a href="all_reports.php" class="nav-link active"><i class="fas fa-chart-pie"></i> &nbsp; School Reports</a>
    </nav>
    <a href="../logout.php" style="margin-top: auto; color: #ff5f5f; text-decoration: none; font-weight: 700; padding: 15px; text-align: center; border: 1px solid rgba(255,95,95,0.2); border-radius: 15px;">Logout</a>
</aside>

<main class="main-wrapper">
    <h1 style="color: var(--text-dark); font-weight: 800; margin: 0;">School-Wide Analytics</h1>
    <p style="color: var(--text-gray); font-weight: 600;">Data Overview for Grade 1 - 6</p>

    <div class="report-grid">
        <div class="report-card">
            <h3 style="color: var(--text-dark); font-weight: 800; margin-top: 0;">Performance by Grade Level</h3>
            <canvas id="gradeChart" height="150"></canvas>
        </div>

        <div class="report-card">
            <h3 style="color: var(--text-dark); font-weight: 800; margin-top: 0;">Gender Distribution</h3>
            <canvas id="genderChart"></canvas>
        </div>

        <div class="report-card" style="grid-column: 1 / -1;">
            <h3 style="color: var(--text-dark); font-weight: 800; margin-top: 0;">🏆 Top Performing Sections</h3>
            <?php foreach($top_sections as $index => $top): ?>
                <div class="top-item">
                    <div>
                        <span style="font-weight: 800; color: var(--accent);">#<?= $index+1 ?></span>
                        <span style="font-weight: 800; margin-left: 10px; color: var(--text-dark);">Section <?= htmlspecialchars($top['section']) ?></span>
                        <span style="margin-left: 10px; font-size: 0.75rem; color: var(--text-gray); font-weight: 700;">GRADE <?= $top['grade_handle'] ?></span>
                    </div>
                    <div class="xp-badge"><?= number_format($top['total_xp']) ?> TOTAL XP</div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<script>
    // Chart 1: Grade Level Performance
    const gradeLabels = <?= json_encode(array_column($grade_data, 'grade')) ?>;
    const gradeValues = <?= json_encode(array_column($grade_data, 'total_correct')) ?>;

    new Chart(document.getElementById('gradeChart'), {
        type: 'bar',
        data: {
            labels: gradeLabels.map(g => 'Grade ' + g),
            datasets: [{
                label: 'Words Correct',
                data: gradeValues,
                backgroundColor: '#4318FF',
                borderRadius: 10
            }]
        },
        options: { plugins: { legend: { display: false } } }
    });

    // Chart 2: Gender Distribution
    const genderLabels = <?= json_encode(array_column($gender_data, 'sex')) ?>;
    const genderValues = <?= json_encode(array_column($gender_data, 'count')) ?>;

    new Chart(document.getElementById('genderChart'), {
        type: 'doughnut',
        data: {
            labels: genderLabels,
            datasets: [{
                data: genderValues,
                backgroundColor: ['#4facfe', '#f093fb']
            }]
        },
        options: { cutout: '70%' }
    });
</script>

</body>
</html>