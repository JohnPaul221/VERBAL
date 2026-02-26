<?php
session_start();
require_once '../config/database.php';
global $pdo;

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

    $sql_students = "SELECT s.*, 
                     COALESCE(sp.words_correct, 0) as words_correct, 
                     COALESCE(sp.words_attempted, 0) as words_attempted 
                     FROM students s
                     LEFT JOIN student_progress_main sp ON s.id = sp.student_id
                     WHERE s.grade = ? AND s.section = ?
                     ORDER BY s.fullname ASC";
    $stmt_students = $pdo->prepare($sql_students);
    $stmt_students->execute([$t_grade, $t_section]);
    $students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

    $total_c = array_sum(array_column($students, 'words_correct'));
    $total_a = array_sum(array_column($students, 'words_attempted'));
    $class_progress = ($total_a > 0) ? round(($total_c / $total_a) * 100) : 0;

    $mastered_count = 0;
    foreach ($students as $s) {
        if (isset($s['mastery_unlocked']) && $s['mastery_unlocked'] == 1) {
            $mastered_count++;
        }
    }

    $sql_top5 = "SELECT s.fullname, SUM(sr.score) AS total_score 
                 FROM students s 
                 JOIN student_ratings sr ON s.id = sr.student_id
                 WHERE s.grade = ? AND s.section = ?
                 GROUP BY s.id 
                 ORDER BY total_score DESC LIMIT 5";
    $stmt_top5 = $pdo->prepare($sql_top5);
    $stmt_top5->execute([$t_grade, $t_section]);
    $top5_data = $stmt_top5->fetchAll(PDO::FETCH_ASSOC);

    $chart_labels = json_encode(array_column($top5_data, 'fullname'));
    $chart_scores = json_encode(array_column($top5_data, 'total_score'));

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verbal | Fit-to-Screen Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --accent: #4318FF;
            --bg-body: #F4F7FE;
            --text-dark: #1B2559;
            --text-gray: #A3AED0;
            --sidebar-dark: #111C44;
            --shadow: 14px 17px 40px 4px rgba(112, 144, 176, 0.08);
        }

        /* FIT TO SCREEN LOGIC */
        html, body {
            height: 100vh;
            width: 100vw;
            margin: 0;
            padding: 0;
            overflow: hidden;
            background: var(--bg-body);
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        * {
            box-sizing: border-box;
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        *::-webkit-scrollbar { display: none; }

        .app-container { display: flex; height: 100vh; width: 100vw; overflow: hidden; }

        /* SIDEBAR (KATULAD NG STUDENT-PAGE.PHP) */
        .sidebar {
            width: 280px;
            background: var(--sidebar-dark);
            padding: 40px 25px;
            display: flex;
            flex-direction: column;
            color: white;
            flex-shrink: 0;
            transition: 0.4s;
            height: 100vh;
        }
        .logo { font-size: 1.6rem; font-weight: 800; margin-bottom: 40px; }
        .nav-link {
            display: flex;
            align-items: center;
            padding: 14px 20px;
            color: var(--text-gray);
            text-decoration: none;
            border-radius: 18px;
            font-weight: 700;
            transition: 0.3s;
            margin-bottom: 5px;
        }
        .nav-link:hover { background: rgba(255, 255, 255, 0.05); color: white; transform: translateX(5px); }
        .nav-link.active { background: var(--accent); color: white; box-shadow: 0px 10px 20px rgba(67, 24, 255, 0.3); }

        .logout-btn {
            margin-top: auto;
            color: #ff5f5f;
            border: 1px solid rgba(255, 95, 95, 0.2);
            text-align: center;
            cursor: pointer;
            padding: 12px;
            border-radius: 15px;
            font-weight: 700;
            transition: 0.3s;
        }
        .logout-btn:hover { background: #ff5f5f; color: white; }

        /* MAIN WRAPPER - FLEX 1 TO FIT SCREEN */
        .main-wrapper {
            flex: 1;
            padding: 25px 35px;
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
        }

        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-shrink: 0; }
        .search-wrapper input { width: 350px; padding: 10px 25px; border-radius: 50px; border: none; background: white; box-shadow: var(--shadow); outline: none; font-weight: 600; }

        .profile-info { display: flex; align-items: center; gap: 12px; background: white; padding: 6px 18px; border-radius: 50px; box-shadow: var(--shadow); }
        .profile-info img { width: 32px; height: 32px; border-radius: 50%; border: 2px solid var(--accent); }

        .hero-banner {
            background: linear-gradient(135deg, #4318FF 0%, #991BFF 100%);
            padding: 30px; border-radius: 25px; color: white; margin-bottom: 20px;
            box-shadow: 0px 15px 30px rgba(67, 24, 255, 0.2);
            flex-shrink: 0;
        }

        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px; flex-shrink: 0; }
        .stat-card { background: white; padding: 20px; border-radius: 20px; box-shadow: var(--shadow); }
        .stat-card small { color: var(--text-gray); font-size: 0.7rem; font-weight: 800; text-transform: uppercase; }
        .stat-card h2 { font-size: 1.5rem; font-weight: 800; margin-top: 5px; }

        /* GRID AREA MUST FILL REMAINING SPACE */
        .content-grid {
            display: grid;
            grid-template-columns: 1.8fr 1fr;
            gap: 20px;
            flex: 1;
            min-height: 0; /* Important for flex children scrolling */
        }
        .card {
            background: white;
            padding: 25px;
            border-radius: 25px;
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            overflow: hidden; /* Prevent card from growing */
        }

        .rank-list-scroll {
            flex: 1;
            overflow-y: auto;
            margin-top: 15px;
            padding-right: 5px;
        }

        .rank-item { display: flex; align-items: center; padding: 12px 18px; border-radius: 18px; background: #f8faff; margin-bottom: 10px; transition: 0.3s; }
        .rank-item:hover { transform: translateX(5px); background: #f0f3ff; }
        .rank-num { width: 35px; height: 35px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 800; color: white; margin-right: 15px; }

        .chart-wrap { flex: 1; position: relative; width: 100%; display: flex; align-items: center; justify-content: center; }
        .chart-center { position: absolute; text-align: center; }
    </style>
</head>
<body>

<div class="app-container">
    <aside class="sidebar">
        <div class="logo">🟣 Verbal.</div>
        <nav style="display:flex; flex-direction:column; gap:3px;">
            <a href="teacher_dashboard.php" class="nav-link active"><i class="fas fa-th-large"></i> &nbsp; Dashboard</a>
            <a href="student-page.php" class="nav-link"><i class="fas fa-user-graduate"></i> &nbsp; Students</a>
            <a href="class-reports.php" class="nav-link"><i class="fas fa-chart-pie"></i> &nbsp; Reports</a>
        </nav>
        <div class="logout-btn" onclick="confirmLogout()">Logout Account</div>
    </aside>

    <main class="main-wrapper">
        <header class="top-bar">
            <div class="search-wrapper">
                <input type="text" id="perfSearch" placeholder="🔍 Search performance..." onkeyup="filterTop()">
            </div>
            <div class="profile-info">
                <div style="text-align: right;">
                    <p style="font-weight:800; font-size:0.8rem;"><?= htmlspecialchars($teacher['fullname']) ?></p>
                    <p style="font-size:0.65rem; color:var(--text-gray); font-weight:700;"></p>
                </div>
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($teacher['fullname']) ?>&background=4318FF&color=fff&bold=true" alt="Profile">
            </div>
        </header>

        <section class="hero-banner">
            <h1 style="font-size:1.8rem; font-weight:800;">Section <?= htmlspecialchars($t_section) ?></h1>
            <p style="font-size:0.95rem; opacity:0.9;">Grade <?= htmlspecialchars($t_grade) ?> • Welcome back to your dashboard.</p>
        </section>

        <div class="stats-grid">
            <div class="stat-card">
                <small>Total Students</small>
                <h2><?= count($students) ?></h2>
            </div>
            <div class="stat-card">
                <small>Class Accuracy</small>
                <h2 style="color:var(--accent);"><?= $class_progress ?>%</h2>
            </div>
            <div class="stat-card">
                <small>Words Mastered</small>
                <h2 style="color:#05cd99;"><?= number_format($total_c) ?></h2>
            </div>
            <div class="stat-card">
                <small>Mastery Unlocked</small>
                <h2 style="color:#ffb547;"><?= $mastered_count ?> <span style="font-size:0.8rem; color:var(--text-gray);">Students</span></h2>
            </div>
        </div>

        <div class="content-grid">
            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <h3 style="font-weight:800; font-size:1.1rem;">🏆 Top Performers</h3>
                    <a href="student-page.php" style="color:var(--accent); text-decoration:none; font-weight:800; font-size:0.8rem;">View All</a>
                </div>
                <div class="rank-list-scroll" id="perfList">
                    <?php
                    $colors = ['#4318FF', '#05cd99', '#ffb547', '#ff5f5f', '#991BFF'];
                    if (empty($top5_data)): ?>
                        <p style="text-align:center; padding:20px; color:var(--text-gray);">No data found.</p>
                    <?php else:
                        foreach($top5_data as $idx => $s): ?>
                            <div class="rank-item" data-name="<?= strtolower($s['fullname']) ?>">
                                <div class="rank-num" style="background: <?= $colors[$idx] ?? '#4318FF' ?>;"><?= $idx + 1 ?></div>
                                <div style="flex:1;">
                                    <p style="font-weight:800; font-size:0.9rem;"><?= htmlspecialchars($s['fullname']) ?></p>
                                    <p style="font-size:0.7rem; color:var(--text-gray); font-weight:600;">Consistent Performer</p>
                                </div>
                                <div style="text-align:right;">
                                    <p style="font-weight:800; font-size:1.1rem; color:var(--accent);"><?= number_format($s['total_score']) ?></p>
                                    <p style="font-size:0.6rem; font-weight:800; color:var(--text-gray);">PTS</p>
                                </div>
                            </div>
                        <?php endforeach;
                    endif; ?>
                </div>
            </div>

            <div class="card">
                <h3 style="font-weight:800; font-size:1.1rem; margin-bottom:10px;">Data Distribution</h3>
                <div class="chart-wrap">
                    <canvas id="top5Chart"></canvas>
                    <div class="chart-center">
                        <p style="font-size:0.65rem; font-weight:800; color:var(--text-gray);">TOP 5</p>
                        <p style="font-size:1.2rem; font-weight:900;">STATS</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>



<script>
    const ctx = document.getElementById('top5Chart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?= $chart_labels ?>,
            datasets: [{
                data: <?= $chart_scores ?>,
                backgroundColor: ['#4318FF', '#05cd99', '#ffb547', '#ff5f5f', '#991BFF'],
                borderWidth: 0,
                hoverOffset: 12
            }]
        },
        options: {
            cutout: '80%',
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }
        }
    });

    function filterTop() {
        let val = document.getElementById('perfSearch').value.toLowerCase();
        let items = document.getElementsByClassName('rank-item');
        Array.from(items).forEach(item => {
            item.style.display = item.getAttribute('data-name').includes(val) ? "flex" : "none";
        });
    }

    function confirmLogout() {
        Swal.fire({
            title: 'Logout?',
            text: "Sigurado ka bang gusto mong lumabas?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#4318FF',
            cancelButtonColor: '#ff5f5f',
            confirmButtonText: 'Yes, Logout'
        }).then((result) => { if (result.isConfirmed) window.location.href = "../logout.php"; });
    }
</script>
</body>
</html>