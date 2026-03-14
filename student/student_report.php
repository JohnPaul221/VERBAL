<?php
session_start();
require_once '../config/database.php';
global $pdo;

// 1. STUDENT SECURITY CHECK
// Sinisiguro natin na student lang ang pwedeng makakita nito at sarili lang nilang data.
if (!isset($_SESSION['student_id'])) {
    header("Location: ../login.php");
    exit();
}

$s_id = $_SESSION['student_id'];
$s_name = $_SESSION['student_name'] ?? 'Student'; // Siguraduhin na may 'student_name' sa session mo
$level_filter = $_GET['level'] ?? 'all';
$range = $_GET['range'] ?? 'all';

// Balik sa student dashboard (Palitan kung iba ang filename ng dashboard mo)
$back_link = "Grade-1.php"; // O kung saan mo gusto bumalik ang bata

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

    // --- LOGS QUERY (History ng binasa ng bata) ---
    $stmt_log = $pdo->prepare("
        SELECT word, score, duration, created_at, difficulty,
        (SELECT COUNT(*) FROM student_ratings r2 WHERE r2.student_id = r1.student_id AND r2.word = r1.word AND r2.created_at <= r1.created_at) as attempt_no
        FROM student_ratings r1
        WHERE 1=1 $sql_condition 
        ORDER BY created_at DESC LIMIT 30
    ");
    $stmt_log->execute([$s_id]);
    $logs = $stmt_log->fetchAll(PDO::FETCH_ASSOC);

    // --- MASTERY COUNTS (Ilang salita na ang Perfect/Mastered) ---
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
    $stmt_chart = $pdo->prepare("SELECT word as label, score as val FROM student_ratings WHERE 1=1 $sql_condition ORDER BY created_at ASC LIMIT 20");
    $stmt_chart->execute([$s_id]);
    $chart_raw = $stmt_chart->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) { die("Database Error"); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

        * { box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg-body); color: var(--text-main); margin: 0; padding: 20px; min-height: 100vh; }
        .layout { width: 100%; max-width: 1200px; margin: 0 auto; display: flex; flex-direction: column; gap: 20px; }

        header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .exit-link { background: white; padding: 10px 18px; border-radius: 15px; text-decoration: none; color: var(--brand); font-weight: 800; box-shadow: var(--shadow); transition: 0.3s; }
        .exit-link:hover { background: var(--brand); color: white; }

        .glass-nav { background: white; padding: 5px; border-radius: 14px; display: flex; gap: 5px; box-shadow: var(--shadow); }
        .nav-link { padding: 8px 15px; border-radius: 10px; text-decoration: none; font-size: 12px; font-weight: 800; color: var(--text-muted); }
        .nav-link.active { background: var(--brand); color: white; }

        .mastery-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; }
        .card { background: var(--card); border-radius: 20px; padding: 20px; box-shadow: var(--shadow); border: 2px solid transparent; }
        .card.active { border-color: var(--brand); background: #f8faff; }
        .val { font-size: 28px; font-weight: 800; margin: 10px 0; }

        .m-bar { width: 100%; height: 8px; background: #E9EDF7; border-radius: 10px; overflow: hidden; }
        .m-fill { height: 100%; border-radius: 10px; }

        .dashboard-content { display: grid; grid-template-columns: 1.5fr 1fr; gap: 20px; }
        .panel { background: var(--card); border-radius: 25px; padding: 25px; box-shadow: var(--shadow); }

        table { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
        td { padding: 12px; background: #F7F9FF; font-weight: 700; font-size: 14px; }
        tr td:first-child { border-radius: 12px 0 0 12px; }
        tr td:last-child { border-radius: 0 12px 12px 0; text-align: right; }

        @media (max-width: 850px) { .dashboard-content { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div class="layout">
    <header>
        <a href="<?= $back_link ?>" class="exit-link"><i class="fas fa-arrow-left"></i> Back to Lessons</a>
        <div class="glass-nav">
            <?php foreach(['all', 'daily', 'weekly'] as $r): ?>
                <a href="?level=<?= $level_filter ?>&range=<?= $r ?>" class="nav-link <?= $range == $r ? 'active' : '' ?>"><?= ucfirst($r) ?></a>
            <?php endforeach; ?>
        </div>
    </header>

    <div class="page-info">
        <h1 style="margin:0; font-size: 24px;">Hello, <?= htmlspecialchars($s_name) ?>! 👋</h1>
        <p style="color: var(--text-muted); margin: 5px 0 0 0;">Here is how you're doing so far:</p>
    </div>

    <div class="mastery-grid">
        <div class="card <?= $level_filter == 'beginner' ? 'active' : '' ?>">
            <div style="display:flex; justify-content:space-between;"><span>Beginner</span><i class="fas fa-seedling" style="color: var(--brand);"></i></div>
            <p class="val"><?= (int)$m_counts['beg'] ?> <small style="font-size:12px; color:var(--text-muted);">Mastered</small></p>
            <div class="m-bar"><div class="m-fill" style="width: <?= min(($m_counts['beg']/40)*100, 100) ?>%; background: var(--brand);"></div></div>
        </div>

        <div class="card <?= $level_filter == 'intermediate' ? 'active' : '' ?>">
            <div style="display:flex; justify-content:space-between;"><span>Intermediate</span><i class="fas fa-fire" style="color: var(--green);"></i></div>
            <p class="val"><?= (int)$m_counts['int'] ?> <small style="font-size:12px; color:var(--text-muted);">Mastered</small></p>
            <div class="m-bar"><div class="m-fill" style="width: <?= min(($m_counts['int']/40)*100, 100) ?>%; background: var(--green);"></div></div>
        </div>

        <div class="card <?= $level_filter == 'advanced' ? 'active' : '' ?>">
            <div style="display:flex; justify-content:space-between;"><span>Advanced</span><i class="fas fa-crown" style="color: var(--orange);"></i></div>
            <p class="val"><?= (int)$m_counts['adv'] ?> <small style="font-size:12px; color:var(--text-muted);">Mastered</small></p>
            <div class="m-bar"><div class="m-fill" style="width: <?= min(($m_counts['adv']/40)*100, 100) ?>%; background: var(--orange);"></div></div>
        </div>
    </div>

    <div class="dashboard-content">
        <div class="panel">
            <h2 style="font-size:18px; margin-bottom:20px;">Performance Graph</h2>
            <div style="height: 300px;"><canvas id="studentChart"></canvas></div>
        </div>

        <div class="panel">
            <h2 style="font-size:18px; margin-bottom:20px;">My Recent Readings</h2>
            <div style="max-height: 400px; overflow-y: auto;">
                <table>
                    <?php if(empty($logs)): ?>
                        <tr><td colspan="2" style="text-align:center;">Start reading to see your progress!</td></tr>
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
                borderWidth: 3
            }]
        },
        options: {
            maintainAspectRatio: false,
            scales: { y: { min: 0, max: 5, ticks: { stepSize: 1 } } }
        }
    });
</script>
</body>
</html>