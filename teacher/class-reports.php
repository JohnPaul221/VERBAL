<?php
session_start();
require_once '../config/database.php';
global $pdo;

// 1. SECURITY CHECK
if (!isset($_SESSION['teacher_id']) || $_SESSION['role'] !== 'teacher') {
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

    $t_grade = $teacher['grade_handle'] ?? 'N/A';
    $t_section = $teacher['section'] ?? 'N/A';

    $range = isset($_GET['range']) ? $_GET['range'] : 'all';
    $rate_condition = "";

    switch ($range) {
        case 'daily': $rate_condition = " AND DATE(sr.created_at) = CURDATE()"; break;
        case 'weekly': $rate_condition = " AND YEARWEEK(sr.created_at, 1) = YEARWEEK(CURDATE(), 1)"; break;
        case 'monthly': $rate_condition = " AND MONTH(sr.created_at) = MONTH(CURDATE()) AND YEAR(sr.created_at) = YEAR(CURDATE())"; break;
    }

    // Ginamit ang mas mabilis na JOIN query pero same result columns
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
            --accent: #4318FF;
            --bg-body: #F4F7FE;
            --text-dark: #1B2559;
            --text-gray: #A3AED0;
            --sidebar-dark: #111C44;
            --white: #ffffff;
            --success: #05CD99;
            --danger: #EE5D50;
            --shadow: 14px 17px 40px 4px rgba(112, 144, 176, 0.08);
        }

        /* FIT TO SCREEN LOGIC - NO VERTICAL SCROLL ON BODY */
        html, body { height: 100vh; width: 100vw; margin: 0; padding: 0; overflow: hidden; background: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; }
        * { box-sizing: border-box; -ms-overflow-style: none; scrollbar-width: none; }
        *::-webkit-scrollbar { display: none; }

        .app-container { display: flex; height: 100vh; width: 100vw; overflow: hidden; }

        /* SIDEBAR WITH ORIGINAL HOVER & SLIDE EFFECTS */
        .sidebar { width: 280px; background: var(--sidebar-dark); padding: 40px 25px; display: flex; flex-direction: column; color: white; flex-shrink: 0; transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .logo { font-size: 1.6rem; font-weight: 800; margin-bottom: 50px; animation: fadeInDown 0.8s; }
        .nav-link { display: flex; align-items: center; padding: 16px 20px; color: var(--text-gray); text-decoration: none; border-radius: 20px; font-weight: 700; transition: 0.3s; margin-bottom: 8px; }
        .nav-link:hover { background: rgba(255, 255, 255, 0.05); color: white; transform: translateX(8px); }
        .nav-link.active { background: var(--accent); color: white; box-shadow: 0px 10px 20px rgba(67, 24, 255, 0.3); }

        .logout-btn { margin-top: auto; color: #ff5f5f; border: 1px solid rgba(255, 95, 95, 0.2); text-align: center; cursor: pointer; padding: 12px; border-radius: 12px; transition: 0.3s; font-weight: 700; }
        .logout-btn:hover { background: #ff5f5f; color: white; transform: scale(1.02); }

        /* MAIN CONTENT AREA */
        .main-wrapper { flex: 1; padding: 25px 35px; display: flex; flex-direction: column; height: 100vh; overflow: hidden; }

        .header-area { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-shrink: 0; animation: fadeInDown 0.8s; }
        .header-area h1 { font-size: 1.8rem; font-weight: 800; letter-spacing: -0.5px; }

        /* EXPORT PDF BUTTON STYLE */
        .btn-print {
            background: var(--accent);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 15px;
            font-family: inherit;
            font-weight: 700;
            font-size: 0.85rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0px 10px 20px rgba(67, 24, 255, 0.2);
        }

        .btn-print:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0px 15px 25px rgba(67, 24, 255, 0.3);
            background: #3310DB;
        }

        .analytics-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 25px; flex-shrink: 0; }
        .glass-card { background: white; padding: 25px; border-radius: 30px; box-shadow: var(--shadow); transition: 0.4s; }
        .glass-card:hover { transform: translateY(-5px); }

        /* TOOLBAR & SEARCH */
        .toolbar { display: flex; gap: 15px; margin-bottom: 20px; align-items: center; flex-shrink: 0; }
        .range-picker { display: flex; background: #E9EDF7; padding: 5px; border-radius: 15px; gap: 5px; }
        .range-btn { text-decoration: none; padding: 8px 18px; border-radius: 12px; font-size: 0.8rem; font-weight: 800; color: var(--accent); transition: 0.3s; }
        .range-btn.active { background: var(--accent); color: white; box-shadow: 0 5px 10px rgba(67, 24, 255, 0.2); }

        .search-box { flex: 1; background: white; border: none; padding: 12px 25px; border-radius: 50px; box-shadow: var(--shadow); font-weight: 600; outline: none; transition: 0.3s; }
        .search-box:focus { box-shadow: 0 10px 25px rgba(67, 24, 255, 0.1); }

        /* TABLE AREA */
        .table-wrap { background: white; border-radius: 30px; box-shadow: var(--shadow); flex: 1; overflow-y: auto; position: relative; animation: fadeInUp 1s; }
        table { width: 100%; border-collapse: collapse; }
        thead { position: sticky; top: 0; background: white; z-index: 10; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        th { text-align: left; padding: 18px 25px; color: var(--text-gray); font-size: 0.7rem; text-transform: uppercase; font-weight: 800; border-bottom: 1px solid #F1F4F9; }
        td { padding: 18px 25px; border-bottom: 1px solid #F1F4F9; font-weight: 700; font-size: 0.9rem; transition: 0.2s; }
        tr:hover td { background: #f8faff; }

        /* STATUS BADGES */
        .badge { padding: 6px 12px; border-radius: 8px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; display: inline-block; }
        .status-advanced { background: rgba(5, 205, 153, 0.1); color: var(--success); }
        .status-proficient { background: rgba(67, 24, 255, 0.1); color: var(--accent); }
        .status-struggling { background: rgba(238, 93, 80, 0.1); color: var(--danger); }

        .progress-mini { width: 80px; height: 6px; background: #F4F7FE; border-radius: 10px; overflow: hidden; display: inline-block; vertical-align: middle; margin-right: 12px; }
        .fill-mini { height: 100%; background: var(--accent); border-radius: 10px; transition: 1s ease-out; }

        /* PRINT MEDIA QUERY - FIXED FOR PDF EXPORT */
        @media print {
            @page { size: landscape; margin: 10mm; }
            .sidebar, .toolbar, .btn-print, .logout-btn, .range-picker { display: none !important; }
            .app-container { display: block !important; }
            .main-wrapper { padding: 0 !important; width: 100% !important; overflow: visible !important; height: auto !important; }
            .table-wrap { overflow: visible !important; box-shadow: none !important; border-radius: 0 !important; border: 1px solid #eee; }
            body { background: white !important; overflow: visible !important; }
            thead { position: static !important; }
            th, td { border: 1px solid #eee !important; color: #000 !important; }
            .glass-card { box-shadow: none !important; border: 1px solid #eee; margin-bottom: 20px; page-break-inside: avoid; }
        }
    </style>
</head>
<body>

<div class="app-container">
    <aside class="sidebar">
        <div class="logo">🟣 Verbal.</div>
        <nav style="display:flex; flex-direction:column; gap:5px;">
            <a href="teacher_dashboard.php" class="nav-link"><i class="fas fa-th-large"></i> &nbsp; Dashboard</a>
            <a href="student-page.php" class="nav-link"><i class="fas fa-user-graduate"></i> &nbsp; Students</a>
            <a href="class-reports.php" class="nav-link active"><i class="fas fa-chart-line"></i> &nbsp; Reports</a>
        </nav>
        <div class="logout-btn" onclick="handleLogout()">Logout Account</div>
    </aside>

    <main class="main-wrapper">
        <header class="header-area">
            <div>
                <h1 class="animate__animated animate__fadeIn">Class Reports</h1>
                <p style="color: var(--text-gray); font-weight: 600;">Grade <?= htmlspecialchars($t_grade) ?> - Section <?= htmlspecialchars($t_section) ?></p>
            </div>
            <button class="btn-print" onclick="window.print()">
                <i class="fas fa-file-pdf"></i> Export PDF
            </button>
        </header>

        <div class="analytics-grid">
            <div class="glass-card">
                <h3 style="margin-bottom: 15px; font-size: 0.9rem; font-weight: 800; color: var(--text-gray);">STUDENT ACCURACY</h3>
                <div style="height: 180px;"><canvas id="reportChart"></canvas></div>
            </div>
            <div class="glass-card" style="display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center;">
                <p style="font-size: 0.7rem; font-weight: 800; color: var(--text-gray); text-transform: uppercase;"><?= $range ?> Average</p>
                <?php
                $avg_acc = 0; $count = 0;
                foreach($reports as $r) {
                    if($r['words_attempted'] > 0) {
                        $avg_acc += ($r['words_correct'] / $r['words_attempted']) * 100;
                        $count++;
                    }
                }
                $final_avg = ($count > 0) ? round($avg_acc / $count) : 0;
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
            <input type="text" id="studentSearch" class="search-box" placeholder="Search by student name..." onkeyup="filterReports()">
            <select id="levelFilter" class="filter-select" onchange="filterReports()" style="background: white; border: none; padding: 12px 20px; border-radius: 15px; font-weight: 700; cursor: pointer; box-shadow: var(--shadow); outline: none;">
                <option value="all">All Levels</option>
                <option value="advanced">Advanced</option>
                <option value="proficient">Proficient</option>
                <option value="struggling">Struggling</option>
            </select>
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
                        <td><?= htmlspecialchars($r['fullname']) ?></td>
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
        data: {
            labels: [],
            datasets: [{
                label: 'Accuracy %',
                data: [],
                backgroundColor: '#4318FF',
                borderRadius: 8,
                barThickness: 20
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, max: 100, grid: { display: false }, ticks: { font: { weight: 'bold' } } },
                x: { grid: { display: false }, ticks: { font: { weight: 'bold' } } }
            }
        }
    });

    function filterReports() {
        const query = document.getElementById('studentSearch').value.toLowerCase();
        const level = document.getElementById('levelFilter').value;
        const rows = document.querySelectorAll('#reportTbody tr');

        let chartLabels = [];
        let chartData = [];
        let count = 0;

        rows.forEach(row => {
            const name = row.cells[0].innerText;
            const status = row.getAttribute('data-status');
            const accuracy = parseInt(row.cells[3].innerText);

            const matchesSearch = name.toLowerCase().includes(query);
            const matchesLevel = (level === 'all' || status === level);

            if (matchesSearch && matchesLevel) {
                row.style.display = '';
                if (count < 10) {
                    chartLabels.push(name.split(' ')[0]);
                    chartData.push(accuracy);
                    count++;
                }
            } else {
                row.style.display = 'none';
            }
        });

        chart.data.labels = chartLabels;
        chart.data.datasets[0].data = chartData;
        chart.update();
    }

    function handleLogout() {
        Swal.fire({
            title: 'Logout Account?',
            text: "Are you sure you want to end your session?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#4318FF',
            confirmButtonText: 'Yes, Logout'
        }).then((result) => { if (result.isConfirmed) window.location.href = "../logout.php"; });
    }

    window.onload = filterReports;
</script>
</body>
</html>