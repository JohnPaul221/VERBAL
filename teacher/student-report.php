<?php
session_start();
require_once '../config/database.php';
global $pdo;

// 1. SECURITY CHECK
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
$level_filter = $_GET['level'] ?? 'all';

if (!$s_id) {
    header("Location: student-page.php");
    exit();
}

$back_link = ($origin === 'principal' || $is_principal)
    ? "../principal/section_view.php?grade=$grade&section=" . urlencode($section)
    : "student-page.php";

try {
    $range = $_GET['range'] ?? 'all';
    $condition = " AND student_id = ?";

    if ($level_filter !== 'all') {
        $condition .= " AND difficulty = " . $pdo->quote($level_filter);
    }

    $filters = [
        'daily'   => ["DATE(created_at) = CURDATE()", "%h:%i %p"],
        'weekly'  => ["YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)", "%a"],
        'monthly' => ["MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())", "%d %b"],
        'all'     => ["1=1", "%b %d, %y"]
    ];

    $active_filter = $filters[$range] ?? $filters['all'];
    $sql_condition = $condition . " AND " . $active_filter[0];

    // --- LOGS QUERY ---
    $stmt_log = $pdo->prepare("
        SELECT word, score, duration, created_at, difficulty,
        (SELECT COUNT(*) FROM student_ratings r2 WHERE r2.student_id = r1.student_id AND r2.word = r1.word AND r2.created_at <= r1.created_at) as attempt_no
        FROM student_ratings r1
        WHERE 1=1 $sql_condition 
        ORDER BY created_at DESC LIMIT 50
    ");
    $stmt_log->execute([$s_id]);
    $logs = $stmt_log->fetchAll(PDO::FETCH_ASSOC);

    // --- MASTERY COUNTS ---
    $stmt_mastery = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT CASE WHEN (difficulty = 'beginner' OR difficulty IS NULL) AND score = 5 THEN word END) as beg,
            COUNT(DISTINCT CASE WHEN difficulty = 'intermediate' AND score = 5 THEN word END) as `int`,
            COUNT(DISTINCT CASE WHEN difficulty = 'advanced' AND score = 5 THEN word END) as adv
        FROM student_ratings WHERE student_id = ?
    ");
    $stmt_mastery->execute([$s_id]);
    $m_counts = $stmt_mastery->fetch(PDO::FETCH_ASSOC);

    // --- CHART DATA ---
    $stmt_chart = $pdo->prepare("SELECT word as label, score as val FROM student_ratings WHERE 1=1 $sql_condition ORDER BY created_at ASC LIMIT 50");
    $stmt_chart->execute([$s_id]);
    $chart_raw = $stmt_chart->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) { die("Database Error"); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pro Analytics | <?= htmlspecialchars($s_name) ?></title>
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

        * { box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg-body); color: var(--text-main); margin: 0; padding: 20px; height: 100vh; display: flex; justify-content: center; overflow: hidden; }
        .layout { width: 100%; max-width: 1600px; height: 100%; display: flex; flex-direction: column; gap: 15px; }

        header { display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; }
        .header-left { display: flex; align-items: center; gap: 15px; }
        .exit-link { background: white; padding: 10px 18px; border-radius: 15px; text-decoration: none; color: var(--brand); font-weight: 800; font-size: 13px; box-shadow: var(--shadow); transition: 0.3s; display: flex; align-items: center; gap: 8px; }
        .exit-link:hover { transform: translateX(-5px); background: var(--brand); color: white; }

        .glass-nav { background: white; padding: 4px; border-radius: 14px; display: flex; gap: 4px; box-shadow: var(--shadow); }
        .nav-link { padding: 6px 12px; border-radius: 10px; text-decoration: none; font-size: 11px; font-weight: 800; color: var(--text-muted); transition: 0.3s; }
        .nav-link.active { background: var(--brand); color: white; }

        .btn-print { background: var(--brand) !important; color: white !important; cursor: pointer; border: none; }
        .btn-print:hover { opacity: 0.9; transform: translateY(-1px); }

        .mastery-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; flex-shrink: 0; }
        .card-link { text-decoration: none; color: inherit; }
        .card { background: var(--card); border-radius: 20px; padding: 15px 20px; box-shadow: var(--shadow); border: 2px solid transparent; transition: 0.2s ease; }
        .card-link.active .card { border-color: var(--brand); background: linear-gradient(to bottom right, #ffffff, #f0f4ff); transform: translateY(-3px); }
        .val { font-size: 26px; font-weight: 800; margin: 0; }
        .m-bar { width: 100%; height: 6px; background: #E9EDF7; border-radius: 10px; margin-top: 10px; overflow: hidden; }
        .m-fill { height: 100%; border-radius: 10px; transition: 1s ease; }

        .dashboard-content { display: grid; grid-template-columns: 1.6fr 1fr; gap: 15px; flex-grow: 1; min-height: 0; }
        .panel { background: var(--card); border-radius: 25px; padding: 20px; box-shadow: var(--shadow); display: flex; flex-direction: column; min-height: 0; }

        .chart-box { flex-grow: 1; position: relative; width: 100%; min-height: 0; }
        .table-wrap { flex-grow: 1; overflow-y: auto; padding-right: 5px; }

        table { width: 100%; border-collapse: separate; border-spacing: 0 6px; }
        td { padding: 12px 15px; background: #F7F9FF; font-weight: 700; font-size: 13px; }
        tr td:first-child { border-radius: 12px 0 0 12px; color: var(--brand); }
        tr td:last-child { border-radius: 0 12px 12px 0; text-align: right; }
        .attempt-tag { background: #EBF0FF; color: var(--brand); padding: 2px 8px; border-radius: 6px; font-size: 10px; margin-left: 8px; }

        @media print {
            body { background: white; height: auto; overflow: visible; padding: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .layout { height: auto; padding: 20px; width: 100% !important; max-width: 100% !important; }
            header, .glass-nav, .exit-link, .card-link:not(.active) { display: none !important; }
            .page-info { width: 100%; background: linear-gradient(135deg, #4318FF 0%, #001280 100%); color: white; padding: 30px; border-radius: 20px; text-align: center; display: block !important; margin-bottom: 30px; }
            .page-info h1 { font-size: 32px; font-weight: 800; margin: 0; }
            .card-link.active { width: 100% !important; margin-bottom: 30px; display: block !important; }
            .card { box-shadow: 0 10px 30px rgba(67, 24, 255, 0.1) !important; border: 1px solid #ddd; background: #fbfdff !important; padding: 25px !important; }
            .m-fill { background: #4318FF !important; }
            .dashboard-content { display: block; width: 100%; }
            .panel { box-shadow: 0 10px 30px rgba(67, 24, 255, 0.1) !important; border: 1px solid #ddd; margin-bottom: 30px; page-break-inside: avoid; background: white !important; }
            .panel h2 { color: #4318FF !important; }
            .table-wrap { overflow: visible; }
            td { background: #fdfdff !important; }
        }

        @media (max-width: 1024px) {
            body { overflow: auto; height: auto; }
            .layout { height: auto; }
            .mastery-grid { grid-template-columns: 1fr; }
            .dashboard-content { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="layout">
    <header>
        <div class="header-left">
            <a href="<?= $back_link ?>" class="exit-link"><i class="fas fa-arrow-left"></i> Exit</a>
            <div class="page-info"><h1><?= htmlspecialchars($s_name) ?></h1></div>
        </div>
        <div class="header-right" style="display:flex; gap:10px;">
            <div class="glass-nav">
                <a href="?student_id=<?= $s_id ?>&name=<?= urlencode($s_name) ?>&level=all&range=all" class="nav-link <?= $level_filter == 'all' ? 'active' : '' ?>"><i class="fas fa-layer-group"></i> All Levels</a>
                <button onclick="window.print()" class="nav-link btn-print"><i class="fas fa-print"></i> Print Report</button>
            </div>
            <div class="glass-nav">
                <?php foreach(['all', 'daily', 'weekly', 'monthly'] as $r): ?>
                    <a href="?student_id=<?= $s_id ?>&name=<?= urlencode($s_name) ?>&level=<?= $level_filter ?>&range=<?= $r ?>"
                       class="nav-link <?= $range == $r ? 'active' : '' ?>"><?= ucfirst($r) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </header>

    <div class="mastery-grid">
        <a href="?student_id=<?= $s_id ?>&name=<?= urlencode($s_name) ?>&level=beginner&range=<?= $range ?>" class="card-link <?= $level_filter == 'beginner' ? 'active' : '' ?>">
            <div class="card">
                <div class="card-header"><span>Beginner</span><i class="fas fa-seedling" style="color: var(--brand);"></i></div>
                <p class="val"><?= (int)$m_counts['beg'] ?> <small style="font-size: 12px; color: var(--text-muted);">/ 40</small></p>
                <div class="m-bar"><div class="m-fill" style="width: <?= min(($m_counts['beg']/40)*100, 100) ?>%; background: var(--brand);"></div></div>
            </div>
        </a>

        <a href="?student_id=<?= $s_id ?>&name=<?= urlencode($s_name) ?>&level=intermediate&range=<?= $range ?>" class="card-link <?= $level_filter == 'intermediate' ? 'active' : '' ?>">
            <div class="card">
                <div class="card-header"><span>Intermediate</span><i class="fas fa-fire" style="color: var(--green);"></i></div>
                <p class="val"><?= (int)$m_counts['int'] ?> <small style="font-size: 12px; color: var(--text-muted);">/ 40</small></p>
                <div class="m-bar"><div class="m-fill" style="width: <?= min(($m_counts['int']/40)*100, 100) ?>%; background: var(--green);"></div></div>
            </div>
        </a>

        <a href="?student_id=<?= $s_id ?>&name=<?= urlencode($s_name) ?>&level=advanced&range=<?= $range ?>" class="card-link <?= $level_filter == 'advanced' ? 'active' : '' ?>">
            <div class="card">
                <div class="card-header"><span>Advanced</span><i class="fas fa-crown" style="color: var(--orange);"></i></div>
                <p class="val"><?= (int)$m_counts['adv'] ?> <small style="font-size: 12px; color: var(--text-muted);">/ 40</small></p>
                <div class="m-bar"><div class="m-fill" style="width: <?= min(($m_counts['adv']/40)*100, 100) ?>%; background: var(--orange);"></div></div>
            </div>
        </a>
    </div>

    <div class="dashboard-content">
        <div class="panel">
            <h2 style="font-size:16px; font-weight:800; margin:0 0 15px 0;">Performance: <?= ucfirst($level_filter) ?></h2>
            <div class="chart-box"><canvas id="proChart"></canvas></div>
        </div>

        <div class="panel">
            <h2 style="font-size:16px; font-weight:800; margin:0 0 15px 0;">Reading History</h2>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Word & Try</th><th>Rating</th><th>Sec</th></tr></thead>
                    <tbody>
                    <?php if(empty($logs)) echo "<tr><td colspan='3' style='text-align:center;'>No records found.</td></tr>"; ?>
                    <?php foreach($logs as $l): ?>
                        <tr>
                            <td><?= htmlspecialchars($l['word']) ?> <span class="attempt-tag">#<?= $l['attempt_no'] ?></span></td>
                            <td style="color: var(--yellow); letter-spacing: 2px;">
                                <?php
                                $s = (int)$l['score'];
                                echo str_repeat('★', $s) . str_repeat('☆', 5 - $s);
                                ?>
                            </td>
                            <td><?= number_format($l['duration'], 1) ?>s</td>
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
    grad.addColorStop(0, 'rgba(67, 24, 255, 0.2)');
    grad.addColorStop(1, 'rgba(67, 24, 255, 0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($chart_raw, 'label')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($chart_raw, 'val')) ?>,
                borderColor: '#4318FF',
                borderWidth: 4,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#4318FF',
                pointBorderWidth: 2,
                pointRadius: 4,
                fill: true,
                backgroundColor: grad,
                tension: 0.4
            }]
        },
        options: {
            maintainAspectRatio: false,
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { min: 0, max: 5, grid: { color: 'rgba(224, 230, 255, 0.5)', borderDash: [5, 5] }, ticks: { stepSize: 1, color: '#A3AED0' } },
                x: { grid: { display: false }, ticks: { color: '#A3AED0', font: { size: 10 }, maxRotation: 45 } }
            }
        }
    });
</script>
</body>
</html>