<?php
session_start();
require_once '../config/database.php';
global $pdo;

// 1. DYNAMIC SECURITY CHECK
if (!isset($_SESSION['student_logged_in']) || $_SESSION['student_logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

$s_id = $_SESSION['student_id'];
$s_grade = $_SESSION['grade'];
$s_name = $_SESSION['fullname'] ?? 'Student';
$level_filter = $_GET['level'] ?? 'all';
$range = $_GET['range'] ?? 'all';

$back_link = "Grade-" . $s_grade . ".php";

try {
    $condition = " AND student_id = ?";
    if ($level_filter !== 'all') {
        $condition .= " AND difficulty = " . $pdo->quote($level_filter);
    }

    $filters = [
        'daily'   => ["DATE(created_at) = CURDATE()"],
        'weekly'  => ["YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)"],
        'monthly' => ["MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())"],
        'all'     => ["1=1"]
    ];

    $active_filter = $filters[$range] ?? $filters['all'];
    $sql_condition = $condition . " AND " . $active_filter[0];

    $stmt_log = $pdo->prepare("
        SELECT word, score, duration, created_at, difficulty,
        (SELECT COUNT(*) FROM student_ratings r2 WHERE r2.student_id = r1.student_id AND r2.word = r1.word AND r2.created_at <= r1.created_at) as attempt_no
        FROM student_ratings r1
        WHERE 1=1 $sql_condition 
        ORDER BY created_at DESC LIMIT 30
    ");
    $stmt_log->execute([$s_id]);
    $logs = $stmt_log->fetchAll(PDO::FETCH_ASSOC);

    $stmt_mastery = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT CASE WHEN (difficulty = 'beginner' OR difficulty IS NULL) AND score = 5 THEN word END) as beg,
            COUNT(DISTINCT CASE WHEN difficulty = 'intermediate' AND score = 5 THEN word END) as `int`,
            COUNT(DISTINCT CASE WHEN difficulty = 'advanced' AND score = 5 THEN word END) as adv
        FROM student_ratings WHERE student_id = ?
    ");
    $stmt_mastery->execute([$s_id]);
    $m_counts = $stmt_mastery->fetch(PDO::FETCH_ASSOC);

    $stmt_chart = $pdo->prepare("SELECT word as label, score as val FROM student_ratings WHERE 1=1 $sql_condition ORDER BY created_at ASC LIMIT 20");
    $stmt_chart->execute([$s_id]);
    $chart_raw = $stmt_chart->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) { die("Database Error"); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>My Progress | <?= htmlspecialchars($s_name) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --brand: #4318FF; --text-main: #1B2559; --text-muted: #A3AED0;
            --bg-body: #F4F7FE; --card: #FFFFFF; --green: #05CD99;
            --orange: #FF8A65; --yellow: #FFB547;
            --shadow: 14px 17px 40px 4px rgba(112, 144, 176, 0.08);
        }

        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-body);
            color: var(--text-main);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
        }

        .layout {
            width: 100%;
            max-width: 1200px;
            padding: 15px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .exit-link {
            background: white;
            padding: 10px 18px;
            border-radius: 12px;
            text-decoration: none;
            color: var(--brand);
            font-weight: 800;
            box-shadow: var(--shadow);
            font-size: 14px;
        }

        .glass-nav {
            background: white;
            padding: 5px;
            border-radius: 12px;
            display: flex;
            gap: 5px;
            box-shadow: var(--shadow);
            overflow-x: auto; /* For very small phones */
        }

        .nav-link {
            padding: 8px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 11px;
            font-weight: 800;
            color: var(--text-muted);
            white-space: nowrap;
        }
        .nav-link.active { background: var(--brand); color: white; }

        .mastery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 15px;
        }

        .card {
            background: var(--card);
            border-radius: 20px;
            padding: 20px;
            box-shadow: var(--shadow);
            border: 2px solid transparent;
        }
        .card.active { border-color: var(--brand); background: #f8faff; }

        .val { font-size: 24px; font-weight: 800; margin: 10px 0; }
        .m-bar { width: 100%; height: 8px; background: #E9EDF7; border-radius: 10px; overflow: hidden; }
        .m-fill { height: 100%; border-radius: 10px; transition: width 0.5s ease-in-out; }

        .dashboard-content {
            display: grid;
            grid-template-columns: 1.6fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .panel {
            background: var(--card);
            border-radius: 20px;
            padding: 20px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        .table-wrapper {
            max-height: 350px;
            overflow-y: auto;
            padding-right: 5px;
        }

        table { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
        td { padding: 12px; background: #F7F9FF; font-weight: 700; font-size: 13px; }
        tr td:first-child { border-radius: 10px 0 0 10px; }
        tr td:last-child { border-radius: 0 10px 10px 0; text-align: right; }

        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .dashboard-content { grid-template-columns: 1fr; }
            .chart-container { height: 250px; }
        }

        @media (max-width: 480px) {
            header { flex-direction: column; align-items: stretch; }
            .exit-link { text-align: center; }
            .glass-nav { justify-content: space-around; }
            .page-info h1 { font-size: 20px; }
        }
    </style>
</head>
<body>

<div class="layout">
    <header>
        <a href="<?= $back_link ?>" class="exit-link"><i class="fas fa-arrow-left"></i> Back to Lessons</a>
        <div class="glass-nav">
            <?php foreach(['all', 'daily', 'weekly'] as $r): ?>
                <a href="?level=<?= $level_filter ?>&range=<?= $r ?>" class="nav-link <?= $range == $r ? 'active' : '' ?>"><?= strtoupper($r) ?></a>
            <?php endforeach; ?>
        </div>
    </header>

    <div class="page-info">
        <h1 style="margin:0;">Hello, <?= htmlspecialchars($s_name) ?>! 👋</h1>
        <p style="color: var(--text-muted); margin: 5px 0 0 0; font-size: 14px;">Grade <?= htmlspecialchars($s_grade) ?> Progress Report</p>
    </div>

    <div class="mastery-grid">
        <div class="card <?= $level_filter == 'beginner' ? 'active' : '' ?>">
            <div style="display:flex; justify-content:space-between; font-weight: 700;"><span>Beginner</span><i class="fas fa-seedling" style="color: var(--brand);"></i></div>
            <p class="val"><?= (int)$m_counts['beg'] ?> <small style="font-size:12px; color:var(--text-muted); font-weight:400;">Words Mastered</small></p>
            <div class="m-bar"><div class="m-fill" style="width: <?= min(($m_counts['beg']/40)*100, 100) ?>%; background: var(--brand);"></div></div>
        </div>

        <div class="card <?= $level_filter == 'intermediate' ? 'active' : '' ?>">
            <div style="display:flex; justify-content:space-between; font-weight: 700;"><span>Intermediate</span><i class="fas fa-fire" style="color: var(--green);"></i></div>
            <p class="val"><?= (int)$m_counts['int'] ?> <small style="font-size:12px; color:var(--text-muted); font-weight:400;">Words Mastered</small></p>
            <div class="m-bar"><div class="m-fill" style="width: <?= min(($m_counts['int']/40)*100, 100) ?>%; background: var(--green);"></div></div>
        </div>

        <div class="card <?= $level_filter == 'advanced' ? 'active' : '' ?>">
            <div style="display:flex; justify-content:space-between; font-weight: 700;"><span>Advanced</span><i class="fas fa-crown" style="color: var(--orange);"></i></div>
            <p class="val"><?= (int)$m_counts['adv'] ?> <small style="font-size:12px; color:var(--text-muted); font-weight:400;">Words Mastered</small></p>
            <div class="m-bar"><div class="m-fill" style="width: <?= min(($m_counts['adv']/40)*100, 100) ?>%; background: var(--orange);"></div></div>
        </div>
    </div>

    <div class="dashboard-content">
        <div class="panel">
            <h2 style="font-size:16px; margin-top:0;">Performance Graph</h2>
            <div class="chart-container">
                <canvas id="studentChart"></canvas>
            </div>
        </div>

        <div class="panel">
            <h2 style="font-size:16px; margin-top:0;">Recent Activity</h2>
            <div class="table-wrapper">
                <table>
                    <?php if(empty($logs)): ?>
                        <tr><td colspan="2" style="text-align:center; color: var(--text-muted);">No activity yet!</td></tr>
                    <?php endif; ?>
                    <?php foreach($logs as $l): ?>
                        <tr>
                            <td><?= htmlspecialchars($l['word']) ?></td>
                            <td style="color: var(--yellow);">
                                <?php echo str_repeat('★', (int)$l['score']); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    const ctx = document.getElementById('studentChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($chart_raw, 'label')) ?>,
            datasets: [{
                label: 'Score',
                data: <?= json_encode(array_column($chart_raw, 'val')) ?>,
                borderColor: '#4318FF',
                backgroundColor: 'rgba(67, 24, 255, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                borderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    min: 0, max: 5,
                    ticks: { stepSize: 1, font: { family: 'Plus Jakarta Sans' } },
                    grid: { color: '#E9EDF7' }
                },
                x: {
                    ticks: { font: { family: 'Plus Jakarta Sans', size: 10 } },
                    grid: { display: false }
                }
            }
        }
    });
</script>
</body>
</html>