<?php
session_start();
require_once '../config/database.php';
global $pdo;

if (!isset($_SESSION['teacher_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];
$students = [];
$leaderboard = [];
$stats = ['total_students' => 0, 'avg_progress' => 0];

try {
    // 1. Fetch Teacher Info
    $stmt = $pdo->prepare("SELECT fullname, grade, section, profile_img FROM teachers WHERE id = ?");
    $stmt->execute([$teacher_id]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($teacher) {
        $t_grade = $teacher['grade'];
        $t_section = $teacher['section'];

        // 2. Fetch Students matching Teacher's Grade and Section
        $sql_students = "SELECT * FROM students WHERE grade = ? AND section = ? ORDER BY fullname ASC";
        $stmt_students = $pdo->prepare($sql_students);
        $stmt_students->execute([$t_grade, $t_section]);
        $students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

        // 3. Fetch Leaderboard from student_ratings
        $sql_leaderboard = "
            SELECT s.fullname, SUM(sr.score) AS total_score 
            FROM students s
            JOIN student_ratings sr ON s.id = sr.student_id
            WHERE s.grade = ? AND s.section = ?
            GROUP BY s.id 
            ORDER BY total_score DESC LIMIT 5";
        $stmt_lb = $pdo->prepare($sql_leaderboard);
        $stmt_lb->execute([$t_grade, $t_section]);
        $leaderboard = $stmt_lb->fetchAll(PDO::FETCH_ASSOC);

        // 4. Calculate Progress Stats
        $sql_stats = "SELECT AVG(words_correct) as avg_score FROM student_progress sp 
                      JOIN students s ON sp.student_id = s.id 
                      WHERE s.grade = ? AND s.section = ?";
        $stmt_stats = $pdo->prepare($sql_stats);
        $stmt_stats->execute([$t_grade, $t_section]);
        $stats['avg_progress'] = round($stmt_stats->fetchColumn(), 1);
    }
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Dashboard | Pitch.edu</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 260px;
            --primary: #7c5cfc;
            --bg: #f4f7fe;
        }
        body { margin: 0; font-family: 'Inter', sans-serif; background: var(--bg); display: flex; height: 100vh; }

        /* Sidebar */
        .sidebar { width: var(--sidebar-width); background: white; padding: 30px; border-right: 1px solid #e0e0e0; }
        .logo { font-size: 1.5rem; font-weight: 800; color: var(--primary); margin-bottom: 50px; }
        .nav-link { display: block; padding: 12px; color: #666; text-decoration: none; border-radius: 8px; margin-bottom: 5px; transition: 0.3s; }
        .nav-link.active { background: #f0edff; color: var(--primary); font-weight: 600; }

        /* Content */
        .main { flex: 1; padding: 40px; overflow-y: auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }

        .welcome-card {
            background: linear-gradient(135deg, #7c5cfc 0%, #a389ff 100%);
            border-radius: 20px; padding: 30px; color: white; display: flex; justify-content: space-between; align-items: center;
        }

        .stats-container { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 30px 0; }
        .stat-box { background: white; padding: 20px; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        .stat-val { font-size: 1.8rem; font-weight: 700; color: #2d3436; }

        /* Table */
        .student-list { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; color: #888; font-weight: 500; padding: 15px; border-bottom: 1px solid #eee; }
        td { padding: 15px; border-bottom: 1px solid #eee; }
        .avatar-circle { width: 35px; height: 35px; background: #eee; border-radius: 50%; display: inline-block; vertical-align: middle; margin-right: 10px; }
    </style>
</head>
<body>

<nav class="sidebar">
    <div class="logo">🟣 Pitch.edu</div>
    <a href="#" class="nav-link active">Dashboard</a>
    <a href="#" class="nav-link">Students</a>
    <a href="#" class="nav-link">Class Reports</a>
    <a href="../logout.php" class="nav-link" style="margin-top:20px; color:#ff7675;">Logout</a>
</nav>

<main class="main">
    <div class="header">
        <h1>Dashboard</h1>
        <div style="display:flex; align-items:center; gap:10px;">
            <strong><?php echo htmlspecialchars($teacher['fullname']); ?></strong>
            <img src="../uploads/<?php echo $teacher['profile_img']; ?>" style="width:40px; height:40px; border-radius:50%; object-fit:cover;">
        </div>
    </div>

    <div class="welcome-card">
        <div>
            <h2 style="margin:0">Class <?php echo htmlspecialchars($teacher['section']); ?></h2>
            <p style="opacity:0.9">Grade <?php echo htmlspecialchars($teacher['grade']); ?> • You have <?php echo count($students); ?> students active.</p>
        </div>
        <div style="font-size: 3rem;">📖</div>
    </div>

    <div class="stats-container">
        <div class="stat-box">
            <small style="color:#888">Total Students</small>
            <div class="stat-val"><?php echo count($students); ?></div>
        </div>
        <div class="stat-box">
            <small style="color:#888">Avg. Words Correct</small>
            <div class="stat-val"><?php echo $stats['avg_progress']; ?></div>
        </div>
        <div class="stat-box">
            <small style="color:#888">Top Performer</small>
            <div class="stat-val" style="font-size:1.2rem; color:var(--primary)">
                <?php echo $leaderboard[0]['fullname'] ?? 'N/A'; ?>
            </div>
        </div>
    </div>

    <div class="student-list">
        <h3>Student Roster</h3>
        <table>
            <thead>
            <tr>
                <th>Name</th>
                <th>Username</th>
                <th>Joined Date</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($students as $row): ?>
                <tr>
                    <td>
                        <div class="avatar-circle"></div>
                        <strong><?php echo htmlspecialchars($row['fullname']); ?></strong>
                    </td>
                    <td>@<?php echo htmlspecialchars($row['username']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                    <td>
                        <a href="edit_student.php?id=<?php echo $row['id']; ?>" style="text-decoration:none;">✏️</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

</body>
</html>