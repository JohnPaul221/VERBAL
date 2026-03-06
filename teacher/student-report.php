<?php
session_start();
require_once '../config/database.php';
global $pdo;

// 1. DUAL SECURITY CHECK
$is_teacher = isset($_SESSION['teacher_logged_in']) && $_SESSION['teacher_logged_in'] === true;
$is_principal = isset($_SESSION['principal_id']) && $_SESSION['role'] === 'principal';

if (!$is_teacher && !$is_principal) {
    header("Location: ../login.php");
    exit();
}

$s_id = $_GET['student_id'] ?? null;
$s_name = $_GET['name'] ?? 'Student';
$origin = $_GET['origin'] ?? 'teacher';
$grade = $_GET['grade'] ?? '';
$section = $_GET['section'] ?? '';

if (!$s_id) {
    header("Location: student-page.php");
    exit();
}

$back_link = ($origin === 'principal' || $is_principal)
    ? "../principal/section_view.php?grade=$grade&section=" . urlencode($section)
    : "student-page.php";
$back_text = ($origin === 'principal' || $is_principal) ? "Section View" : "Student List";

try {
    $range = $_GET['range'] ?? 'all';
    $condition = " AND student_id = ?";
    $filters = [
        'daily'   => ["DATE(created_at) = CURDATE()", "%h:%i %p"],
        'weekly'  => ["YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)", "%a"],
        'monthly' => ["MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())", "%d %b"],
        'all'     => ["1=1", "%b %d, %y"]
    ];
    $active_filter = $filters[$range] ?? $filters['all'];
    $sql_condition = $condition . " AND " . $active_filter[0];
    $date_format = $active_filter[1];

    $stmt_chart = $pdo->prepare("SELECT DATE_FORMAT(created_at, '$date_format') as label, AVG(score) as avg_score FROM student_ratings WHERE 1=1 $sql_condition GROUP BY label ORDER BY created_at ASC");
    $stmt_chart->execute([$s_id]);
    $chart_raw = $stmt_chart->fetchAll(PDO::FETCH_ASSOC);

    $stmt_log = $pdo->prepare("SELECT word, score, duration, created_at FROM student_ratings WHERE 1=1 $sql_condition ORDER BY created_at DESC LIMIT 30");
    $stmt_log->execute([$s_id]);
    $logs = $stmt_log->fetchAll(PDO::FETCH_ASSOC);

    $stmt_stats = $pdo->prepare("SELECT COUNT(*) as attempts, AVG(score) as avg_rate, SUM(score) as total_stars, AVG(duration) as avg_speed FROM student_ratings WHERE student_id = ?");
    $stmt_stats->execute([$s_id]);
    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) { die("Database Error"); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Pro | <?= htmlspecialchars($s_name) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #4318FF; --secondary: #707EAE; --bg-main: #F4F7FE;
            --card-bg: #FFFFFF; --text-dark: #1B2559; --success: #05CD99;
            --star-gold: #FFB547; --speed-purple: #991BFF;
            --shadow: 14px 17px 40px 4px rgba(112, 144, 176, 0.08);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-main);
            color: var(--text-dark);
            margin: 0;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .dashboard-wrapper { width: 96%; max-width: 1600px; height: 95vh; display: flex; flex-direction: column; gap: 20px; }

        /* --- HEADER PRO --- */
        header { display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; }
        .back-pill {
            background: white; padding: 10px 20px; border-radius: 50px; text-decoration: none;
            color: var(--secondary); font-weight: 700; box-shadow: var(--shadow); transition: 0.3s;
            display: flex; align-items: center; gap: 10px;
        }
        .back-pill:hover { transform: translateX(-5px); color: var(--primary); }

        .filter-glass { background: rgba(224, 230, 255, 0.5); padding: 5px; border-radius: 18px; display: flex; gap: 5px; backdrop-filter: blur(10px); }
        .filter-btn {
            padding: 10px 22px; border-radius: 14px; text-decoration: none; font-size: 14px;
            font-weight: 800; color: var(--secondary); transition: 0.3s;
        }
        .filter-btn.active { background: white; color: var(--primary); box-shadow: 0px 4px 12px rgba(0,0,0,0.05); }

        /* --- STATS PRO --- */
        .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; flex-shrink: 0; }
        .stat-card-pro {
            background: var(--card-bg); padding: 25px; border-radius: 28px; box-shadow: var(--shadow);
            display: flex; flex-direction: column; position: relative; overflow: hidden;
        }
        .stat-card-pro .icon-circle {
            width: 50px; height: 50px; border-radius: 15px; display: flex; align-items: center;
            justify-content: center; font-size: 20px; margin-bottom: 15px;
        }
        .stat-card-pro h3 { margin: 0; font-size: 32px; font-weight: 800; letter-spacing: -1px; }
        .stat-card-pro span { font-size: 13px; font-weight: 800; color: var(--secondary); text-transform: uppercase; }

        /* --- CONTENT GRID --- */
        .main-grid { display: grid; grid-template-columns: 1.7fr 1fr; gap: 25px; flex-grow: 1; min-height: 0; }
        .glass-card {
            background: var(--card-bg); padding: 30px; border-radius: 32px; box-shadow: var(--shadow);
            display: flex; flex-direction: column; min-height: 0; border: 1px solid rgba(255,255,255,0.6);
        }

        .chart-box { flex-grow: 1; position: relative; width: 100%; height: 100%; }

        .table-pro-wrap { flex-grow: 1; overflow-y: auto; padding-right: 10px; }
        table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        th { position: sticky; top: 0; background: white; text-align: left; padding: 15px; color: var(--secondary); font-size: 12px; font-weight: 800; z-index: 10; }
        td { padding: 18px 15px; background: #F8FAFF; font-weight: 700; transition: 0.2s; }
        tr td:first-child { border-radius: 15px 0 0 15px; color: var(--primary); }
        tr td:last-child { border-radius: 0 15px 15px 0; text-align: right; }
        tr:hover td { background: #F0F4FF; transform: scale(1.01); }

        .rating-badge { color: var(--star-gold); background: rgba(255,181,71,0.1); padding: 5px 12px; border-radius: 10px; }
        .speed-badge { color: var(--speed-purple); background: rgba(153,27,255,0.1); padding: 5px 12px; border-radius: 10px; }
    </style>
</head>
<body>

<div class="dashboard-wrapper">
    <header>
        <div>
            <a href="<?= $back_link ?>" class="back-pill"><i class="fas fa-chevron-left"></i> <?= $back_text ?></a>
            <h1 style="margin: 15px 0 0 0; font-size: 36px; font-weight: 800; color: var(--text-dark); letter-spacing: -1.5px;">
                <?= htmlspecialchars($s_name) ?> <span style="font-weight: 400; color: var(--secondary);">Analytics</span>
            </h1>
        </div>
        <div class="filter-glass">
            <?php foreach(['all', 'daily', 'weekly', 'monthly'] as $f): ?>
                <a href="?student_id=<?= $s_id ?>&name=<?= urlencode($s_name) ?>&range=<?= $f ?>&origin=<?= $origin ?>&grade=<?= $grade ?>&section=<?= urlencode($section) ?>"
                   class="filter-btn <?= $range == $f ? 'active' : '' ?>"><?= ucfirst($f) ?></a>
            <?php endforeach; ?>
        </div>
    </header>

    <div class="stats-row">
        <div class="stat-card-pro">
            <div class="icon-circle" style="background: rgba(255,181,71,0.1); color: var(--star-gold);"><i class="fas fa-star"></i></div>
            <span>Total Stars Collected</span>
            <h3><?= number_format($stats['total_stars'] ?? 0) ?> <small style="font-size: 16px; color: var(--secondary);">★</small></h3>
        </div>
        <div class="stat-card-pro">
            <div class="icon-circle" style="background: rgba(67, 24, 255, 0.1); color: var(--primary);"><i class="fas fa-chart-line"></i></div>
            <span>Accuracy Rating</span>
            <h3><?= round($stats['avg_rate'] ?? 0, 1) ?> <small style="font-size: 16px; color: var(--secondary);">/ 5.0</small></h3>
        </div>
        <div class="stat-card-pro">
            <div class="icon-circle" style="background: rgba(153,27,255,0.1); color: var(--speed-purple);"><i class="fas fa-bolt"></i></div>
            <span>Average Speed</span>
            <h3><?= number_format($stats['avg_speed'] ?? 0, 2) ?> <small style="font-size: 16px; color: var(--secondary);">sec</small></h3>
        </div>
    </div>

    <div class="main-grid">
        <div class="glass-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h2 style="margin:0; font-size:22px; font-weight:800;">Performance Trend</h2>
                <span style="font-size:12px; color:var(--success); font-weight:800;"><i class="fas fa-circle"></i> Live Sync</span>
            </div>
            <div class="chart-box">

                <canvas id="proChart"></canvas>
            </div>
        </div>

        <div class="glass-card">
            <h2 style="margin:0 0 20px 0; font-size:22px; font-weight:800;">Recent Reading Logs</h2>
            <div class="table-pro-wrap">
                <table>
                    <thead>
                    <tr><th>Word</th><th>Quality</th><th>Time</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach($logs as $l): ?>
                        <tr>
                            <td><?= htmlspecialchars($l['word']) ?></td>
                            <td><span class="rating-badge"><?= str_repeat('★', $l['score']) ?></span></td>
                            <td><span class="speed-badge"><?= number_format($l['duration'], 2) ?>s</span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    const ctx = document.getElementById('proChart').getContext('2d');
    const grad = ctx.createLinearGradient(0, 0, 0, 400);
    grad.addColorStop(0, 'rgba(67, 24, 255, 0.15)');
    grad.addColorStop(1, 'rgba(67, 24, 255, 0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($chart_raw, 'label')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($chart_raw, 'avg_score')) ?>,
                borderColor: '#4318FF',
                borderWidth: 4,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#4318FF',
                pointBorderWidth: 3,
                pointRadius: 5,
                fill: true,
                backgroundColor: grad,
                tension: 0.4
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { min: 0, max: 5, grid: { color: '#F4F7FE', borderDash: [5, 5] }, ticks: { color: '#A3AED0', stepSize: 1, font: { weight: 'bold' } } },
                x: { grid: { display: false }, ticks: { color: '#A3AED0', font: { weight: 'bold' } } }
            }
        }
    });
</script>
</body>
</html>