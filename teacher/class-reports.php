<?php
session_start();
require_once '../config/database.php';
global $pdo;


if (!isset($_SESSION['teacher_logged_in']) || $_SESSION['teacher_logged_in'] !== true || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM teachers WHERE id = ?");
    $stmt->execute([$teacher_id]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$teacher) {
        session_destroy();
        header("Location: ../login.php?error=session_expired");
        exit();
    }

    $t_grade = $teacher['grade_handle'];
    $t_section = $teacher['section'];

    $range = isset($_GET['range']) ? $_GET['range'] : 'all';
    $rate_condition = "";

    switch ($range) {
        case 'daily':
            $rate_condition = " AND DATE(sr.created_at) = CURDATE()";
            break;
        case 'weekly':
            $rate_condition = " AND YEARWEEK(sr.created_at, 1) = YEARWEEK(CURDATE(), 1)";
            break;
        case 'monthly':
            $rate_condition = " AND MONTH(sr.created_at) = MONTH(CURDATE()) AND YEAR(sr.created_at) = YEAR(CURDATE())";
            break;
    }

    $sql_reports = "SELECT 
                        s.id, 
                        s.fullname, 
                        COUNT(sr.id) as words_attempted,
                        SUM(CASE WHEN sr.score >= 3 THEN 1 ELSE 0 END) as words_correct,
                        COALESCE(SUM(sr.score), 0) as total_pts
                    FROM students s
                    LEFT JOIN student_ratings sr ON s.id = sr.student_id $rate_condition
                    WHERE s.grade = ? 
                    AND s.section = ? 
                    GROUP BY s.id
                    ORDER BY s.fullname ASC";

    $stmt_reports = $pdo->prepare($sql_reports);
    $stmt_reports->execute([$t_grade, $t_section]);
    $reports = $stmt_reports->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports | Verbal Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --accent: #4318FF; --bg-body: #F4F7FE; --text-dark: #1B2559;
            --text-gray: #A3AED0; --sidebar-dark: #111C44; --white: #ffffff;
            --success: #05CD99; --danger: #EE5D50; --shadow: 14px 17px 40px 4px rgba(112, 144, 176, 0.08);
        }

        html, body { height: 100vh; width: 100vw; margin: 0; padding: 0; overflow: hidden; background: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; }
        * { box-sizing: border-box; -ms-overflow-style: none; scrollbar-width: none; }
        *::-webkit-scrollbar { display: none; }

        .app-container { display: flex; height: 100vh; width: 100vw; overflow: hidden; }

        .sidebar { width: 280px; background: var(--sidebar-dark); padding: 40px 25px; display: flex; flex-direction: column; color: white; flex-shrink: 0; }
        .logo { font-size: 1.6rem; font-weight: 800; margin-bottom: 50px; }
        .nav-link { display: flex; align-items: center; padding: 16px 20px; color: var(--text-gray); text-decoration: none; border-radius: 20px; font-weight: 700; transition: 0.3s; margin-bottom: 8px; }
        .nav-link.active { background: var(--accent); color: white; box-shadow: 0px 10px 20px rgba(67, 24, 255, 0.3); }

        .main-wrapper { flex: 1; padding: 25px 35px; display: flex; flex-direction: column; height: 100vh; overflow: hidden; }
        .header-area { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-shrink: 0; }

        .analytics-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 25px; flex-shrink: 0; }
        .glass-card { background: white; padding: 25px; border-radius: 30px; box-shadow: var(--shadow); }

        .toolbar { display: flex; gap: 15px; margin-bottom: 20px; align-items: center; flex-shrink: 0; }
        .range-picker { display: flex; background: #E9EDF7; padding: 5px; border-radius: 15px; gap: 5px; }
        .range-btn { text-decoration: none; padding: 8px 18px; border-radius: 12px; font-size: 0.8rem; font-weight: 800; color: var(--accent); }
        .range-btn.active { background: var(--accent); color: white; }

        .filter-select { border: none; padding: 12px 15px; border-radius: 15px; box-shadow: var(--shadow); font-weight: 700; color: var(--text-dark); outline: none; background: white; cursor: pointer; }
        .search-box { flex: 1; border: none; padding: 12px 25px; border-radius: 50px; box-shadow: var(--shadow); font-weight: 600; outline: none; }

        .table-wrap { background: white; border-radius: 30px; box-shadow: var(--shadow); flex: 1; overflow-y: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 18px 25px; color: var(--text-gray); font-size: 0.7rem; text-transform: uppercase; border-bottom: 1px solid #F1F4F9; position: sticky; top: 0; background: white; }
        td { padding: 18px 25px; border-bottom: 1px solid #F1F4F9; font-weight: 700; font-size: 0.9rem; }

        .badge { padding: 6px 12px; border-radius: 8px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; }
        .status-advanced { background: rgba(5, 205, 153, 0.1); color: var(--success); }
        .status-proficient { background: rgba(67, 24, 255, 0.1); color: var(--accent); }
        .status-struggling { background: rgba(238, 93, 80, 0.1); color: var(--danger); }

        .progress-mini { width: 80px; height: 6px; background: #F4F7FE; border-radius: 10px; overflow: hidden; display: inline-block; margin-right: 12px; }
        .fill-mini { height: 100%; background: var(--accent); }
    </style>
</head>
<body>

<div class="app-container">
    <aside class="sidebar">
        <div class="logo">🟣 Verbal.</div>
        <nav>
            <a href="teacher_dashboard.php" class="nav-link"><i class="fas fa-th-large"></i> &nbsp; Dashboard</a>
            <a href="student-page.php" class="nav-link"><i class="fas fa-user-graduate"></i> &nbsp; Students</a>
            <a href="class-reports.php" class="nav-link active"><i class="fas fa-chart-line"></i> &nbsp; Reports</a>
        </nav>
    </aside>

    <main class="main-wrapper">
        <header class="header-area">
            <div>
                <h1>Class Reports</h1>
                <p style="color: var(--text-gray); font-weight: 600;">Grade <?= htmlspecialchars($t_grade) ?> - Section <?= htmlspecialchars($t_section) ?></p>
            </div>
            <button class="btn-print" onclick="window.print()" style="background: var(--accent); color: white; border: none; padding: 12px 25px; border-radius: 15px; font-weight: 700; cursor: pointer;">
                <i class="fas fa-file-pdf"></i> Export PDF
            </button>
        </header>

        <div class="analytics-grid">
            <div class="glass-card">
                <h3 style="margin-bottom: 15px; font-size: 0.9rem; font-weight: 800; color: var(--text-gray);">STUDENT ACCURACY</h3>
                <div style="height: 180px;"><canvas id="reportChart"></canvas></div>
            </div>
            <div class="glass-card" style="display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center;">
                <p style="font-size: 0.7rem; font-weight: 800; color: var(--text-gray); text-transform: uppercase;"><?= ucfirst($range) ?> Average</p>
                <?php
                $avg_acc = 0; $count_active = 0;
                foreach($reports as $r) {
                    if($r['words_attempted'] > 0) {
                        $avg_acc += ($r['words_correct'] / $r['words_attempted']) * 100;
                        $count_active++;
                    }
                }
                $final_avg = ($count_active > 0) ? round($avg_acc / $count_active) : 0;
                ?>
                <h2 style="font-size: 3.5rem; font-weight: 900; color: var(--accent);"><?= $final_avg ?>%</h2>
                <p style="font-weight: 700; color: var(--text-dark);">Class Proficiency</p>
            </div>
        </div>

        <div class="toolbar">
            <div class="range-picker">
                <a href="?range=all" class="range-btn <?= $range == 'all' ? 'active' : '' ?>">All Time</a>
                <a href="?range=daily" class="range-btn <?= $range == 'daily' ? 'active' : '' ?>">Daily</a>
                <a href="?range=weekly" class="range-btn <?= $range == 'weekly' ? 'active' : '' ?>">Weekly</a>
                <a href="?range=monthly" class="range-btn <?= $range == 'monthly' ? 'active' : '' ?>">Monthly</a>
            </div>

            <select id="statusFilter" class="filter-select" onchange="filterReports()">
                <option value="all">All Status</option>
                <option value="advanced">Advanced</option>
                <option value="proficient">Proficient</option>
                <option value="struggling">Struggling</option>
            </select>

            <input type="text" id="studentSearch" class="search-box" placeholder="Search student name..." onkeyup="filterReports()">
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Correct</th>
                    <th>Attempts</th>
                    <th>Accuracy %</th>
                    <th>Total XP</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody id="reportTbody">
                <?php foreach ($reports as $r):
                    $acc = ($r['words_attempted'] > 0) ? round(($r['words_correct'] / $r['words_attempted']) * 100) : 0;
                    $status = ($acc >= 85) ? 'Advanced' : (($acc >= 70) ? 'Proficient' : 'Struggling');
                    ?>
                    <tr data-status="<?= strtolower($status) ?>">
                        <td class="st-name"><?= htmlspecialchars($r['fullname']) ?></td>
                        <td><?= $r['words_correct'] ?></td>
                        <td><?= $r['words_attempted'] ?></td>
                        <td>
                            <div class="progress-mini"><div class="fill-mini" style="width: <?= $acc ?>%;"></div></div>
                            <span style="color: var(--accent); font-weight: 800;"><?= $acc ?>%</span>
                        </td>
                        <td style="color: var(--text-dark); font-weight: 800;"><?= number_format($r['total_pts']) ?></td>
                        <td><span class="badge status-<?= strtolower($status) ?>"><?= $status ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<script>
    const ctx = document.getElementById('reportChart').getContext('2d');
    let chart = new Chart(ctx, {
        type: 'bar',
        data: { labels: [], datasets: [{ label: 'Accuracy %', data: [], backgroundColor: '#4318FF', borderRadius: 8, barPercentage: 0.4 }] },
        options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, max: 100 }, x: { grid: { display: false } } } }
    });

    function filterReports() {
        const searchQuery = document.getElementById('studentSearch').value.toLowerCase();
        const statusQuery = document.getElementById('statusFilter').value;
        const rows = document.querySelectorAll('#reportTbody tr');

        let chartLabels = [];
        let chartData = [];

        rows.forEach(row => {
            const name = row.querySelector('.st-name').innerText.toLowerCase();
            const status = row.getAttribute('data-status');
            const accuracy = parseInt(row.cells[3].innerText);

            const matchesSearch = name.includes(searchQuery);
            const matchesStatus = (statusQuery === 'all' || status === statusQuery);

            if (matchesSearch && matchesStatus) {
                row.style.display = '';
                chartLabels.push(name.split(' ')[0]);
                chartData.push(accuracy);
            } else {
                row.style.display = 'none';
            }
        });

        chart.data.labels = chartLabels.slice(0, 8);
        chart.data.datasets[0].data = chartData.slice(0, 8);
        chart.update();
    }

    window.onload = filterReports;
</script>
</body>
</html>