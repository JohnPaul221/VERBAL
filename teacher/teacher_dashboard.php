<?php
// ... (PHP code remains the same, fetching $students and $leaderboard) ...
session_start();
require_once '../config/database.php';
global $pdo;
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}

$students = [];
$leaderboard = [];

try {
    // 4a. STUDENT LIST QUERY - Fetching the Student ID (id) is CRUCIAL for Edit/Archive
    $sql_students = "SELECT id, fullname, username, grade, section, password FROM students ORDER BY grade, section, fullname";
    $stmt_students = $pdo->prepare($sql_students);
    $stmt_students->execute();
    $students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

    // 4b. REAL LEADERBOARD QUERY (unchanged)
    $sql_leaderboard = "
        SELECT 
            s.fullname, 
            s.grade, 
            s.section, 
            COALESCE(SUM(sr.score), 0) AS total_score 
        FROM students s
        LEFT JOIN student_ratings sr ON s.id = sr.student_id
        GROUP BY s.id, s.fullname, s.grade, s.section
        ORDER BY total_score DESC, s.fullname ASC
        LIMIT 10
    ";

    $stmt_leaderboard = $pdo->prepare($sql_leaderboard);
    $stmt_leaderboard->execute();
    $leaderboard = $stmt_leaderboard->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Data Fetch Error: " . $e->getMessage());
    // Handle error gracefully
}

// Check for status messages from edit.php or archive_student.php
$status_message = '';
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success_edit') {
        $status_message = "✅ Student record updated successfully!";
    } elseif ($_GET['status'] == 'success_archive') {
        $status_message = "🗑️ Student successfully archived!";
    } elseif ($_GET['status'] == 'not_found') {
        $status_message = "❌ Error: Student record not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
    <style>
        /* BASE STYLES */
        body {
            margin: 0; padding: 0; min-height: 100vh;
            font-family: 'Nunito', 'Arial Rounded MT Bold', sans-serif;
            background: linear-gradient(135deg, #a3b3fa 0%, #ccf2ff 100%);
            overflow-x: hidden;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 50px 20px;
        }

        /* CLOUD ANIMATION (Omitted for brevity) */
        .cloud { position: absolute; background: #fff; border-radius: 50%; opacity: 0.9; }
        .cloud1 { width: 120px; height: 60px; top: 10%; animation: float 70s linear infinite; left: -200px; animation-delay: 0s; }
        .cloud2 { width: 150px; height: 70px; top: 20%; animation: float 90s linear infinite; left: -200px; animation-delay: 45s; }
        .cloud3 { width: 180px; height: 80px; bottom: 20%; left: -200px; animation: float 80s linear infinite; }
        @keyframes float { from { transform: translateX(0); } to { transform: translateX(120vw); } }

        /* HEADER AND LAYOUT WRAPPER */
        .page-header-wrapper {
            width: 100%;
            max-width: 1200px;
            text-align: center;
            margin-bottom: 30px;
        }

        h1 {
            color: #ff6f61;
            font-size: 3em;
            text-shadow: 3px 3px 0 #ffdab9;
            margin-bottom: 10px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1), 0 3px 0 #8b99df;
        }

        /* --- NEW: STATUS MESSAGE STYLE --- */
        .status-message {
            padding: 15px;
            margin: 20px auto;
            border-radius: 15px;
            background-color: #e8f5e9; /* Light green background */
            color: #1b5e20;
            font-weight: 700;
            max-width: 600px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .status-message.error {
            background-color: #ffcdd2;
            color: #b71c1c;
        }

        /* --- CONTENT GRID --- */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
            width: 100%;
            max-width: 1200px;
        }
        @media (min-width: 992px) {
            .content-grid {
                grid-template-columns: 3fr 1fr;
            }
        }

        .list-section, .leaderboard-section {
            background: linear-gradient(135deg, #ffffff, #f0f8ff);
            padding: 20px;
            border-radius: 25px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1), 0 3px 0 #b3e5fc;
        }

        /* --- NEW: SECTION HEADER STYLE --- */
        .list-section h2, .leaderboard-section h2 {
            display: inline-block;
            color: #4a54ff;
            font-size: 2em;
            padding: 5px 15px;
            margin-top: 5px;
            margin-bottom: 15px;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 3px 5px rgba(0,0,0,0.1);
        }

        /* --- NEW: SEARCH BAR & ADD BUTTON AREA --- */
        .student-list-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 15px;
        }
        .search-input {
            flex-grow: 1;
            padding: 10px 20px;
            border-radius: 20px;
            border: 2px solid #ccc;
            font-size: 1em;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: border-color 0.3s;
        }
        .search-input:focus {
            outline: none;
            border-color: #4fc3f7;
        }

        /* --- NEW: NO DATA ALERT STYLE --- */
        .no-data-alert {
            padding: 20px;
            border-radius: 15px;
            background: linear-gradient(145deg, #ffdddd, #ffeaea);
            color: #b71c1c;
            font-weight: 700;
            text-shadow: 1px 1px 0 #fff;
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }

        /* --- FLOATING ACTION BUTTON (FAB) --- */
        .add-student-fab {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: linear-gradient(145deg, #4CAF50, #388E3C);
            color: white;
            padding: 15px 25px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 900;
            font-size: 1.1em;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2), 0 4px 0 #1B5E20;
            transition: transform 0.2s, box-shadow 0.2s;
            z-index: 100;
        }
        .add-student-fab:active {
            box-shadow: 0 1px 0 #1B5E20;
            transform: translateY(3px);
        }

        p { color: #555; font-size: 1.2em; margin-bottom: 20px; }

        /* LOGOUT LINK (Moved outside the FAB area) */
        .logout-link {
            display: inline-block; padding: 12px 30px; margin-top: 30px;
            background: linear-gradient(145deg, #ffa07a, #ff6347); color: white;
            border-radius: 30px; box-shadow: 0 6px 0 #d84315;
            z-index: 10;
        }


        /* --- CARD-BOX STUDENT TABLE STYLES (Rest of existing styles for table, actions, leaderboard) --- */
        .student-table { display: block; width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 20px; }
        .student-table thead { display: none; }
        .student-table tbody tr {
            display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 15px; padding: 15px; border-radius: 25px;
            background: linear-gradient(135deg, #fce4ec, #fff8e1); box-shadow: 0 8px 15px rgba(0,0,0,0.1), 0 3px 0 #d81b60; transition: transform 0.2s, box-shadow 0.2s;
        }
        @media (min-width: 768px) {
            .student-table tbody tr { grid-template-columns: 1.5fr 1fr 0.5fr 0.5fr 1fr 2fr; align-items: center; }
        }
        .student-table tbody tr:nth-child(3n+1) { background: linear-gradient(135deg, #ffab91, #ff8a65); box-shadow: 0 8px 15px rgba(255,138,101,0.3), 0 3px 0 #e65100; }
        .student-table tbody tr:nth-child(3n+2) { background: linear-gradient(135deg, #81d4fa, #4fc3f7); box-shadow: 0 8px 15px rgba(79,195,247,0.3), 0 3px 0 #0288d1; }
        .student-table tbody tr:nth-child(3n) { background: linear-gradient(135deg, #b39ddb, #9575cd); box-shadow: 0 8px 15px rgba(149,117,205,0.3), 0 3px 0 #4527a0; }
        .student-table td { display: block; padding: 5px 0; font-size: 1em; font-weight: 600; color: #333; background-color: transparent; box-shadow: none; position: relative; }
        .student-table td::before { content: attr(data-label); font-weight: 900; color: rgba(0, 0, 0, 0.4); display: block; margin-bottom: 2px; font-size: 0.7em; text-transform: uppercase; }
        .student-table td[data-label="Actions"] { grid-column: 1 / -1; text-align: center; padding-top: 10px; border-top: 1px dashed rgba(0, 0, 0, 0.1); margin-top: 5px; display: flex; justify-content: space-evenly; align-items: center; }
        @media (min-width: 768px) {
            .student-table td[data-label="Actions"] { grid-column: auto; border-top: none; flex-direction: row; justify-content: center; align-items: center; margin: 0; padding: 0; }
        }
        .action-btn { display: block; padding: 8px 15px; margin: 5px 0; border-radius: 12px; text-decoration: none; font-weight: bold; font-size: 0.9em; transition: all 0.2s; min-width: 80px; }
        @media (max-width: 767px) { .action-btn { display: inline-block; margin: 0 5px; flex-grow: 1; } }
        @media (min-width: 768px) { .action-btn { display: inline-block; padding: 8px 12px; margin: 0 4px; min-width: 60px; font-size: 0.8em; } }
        .edit-btn { background: linear-gradient(145deg, #ffc107, #ff9800); color: #444; box-shadow: 0 4px 0 #e65100; }
        .archive-btn { background: linear-gradient(145deg, #ef5350, #d32f2f); color: white; box-shadow: 0 4px 0 #b71c1c; }
        .leaderboard-list { list-style: none; padding: 0; margin: 10px 0 0 0; }
        .leaderboard-list li { display: flex; justify-content: space-between; align-items: center; padding: 12px 18px; margin-bottom: 12px; background: linear-gradient(145deg, #ffecb3, #ffe082); border-radius: 20px; box-shadow: 0 4px 10px rgba(255, 179, 0, 0.3), 0 2px 0 #ffb300; font-weight: bold; color: #5d4037; }
        .rank { font-size: 1.8em; font-weight: 900; }
        .total-score-display { font-size: 1.4em; color: white; background-color: #4CAF50; padding: 5px 12px; border-radius: 15px; box-shadow: 0 3px 0 #388E3C; }
        .leaderboard-list li:nth-child(1) .rank { color: #d4ac00; font-size: 2.2em; }

    </style>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <script>
        function filterStudents() {
            let input = document.getElementById('studentSearch');
            let filter = input.value.toUpperCase();
            let table = document.querySelector('.student-table tbody');
            let trs = table.getElementsByTagName('tr');

            for (let i = 0; i < trs.length; i++) {
                let found = false;
                let tds = trs[i].getElementsByTagName('td');
                for (let j = 0; j < tds.length; j++) {
                    let txtValue = tds[j].textContent || tds[j].innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
                trs[i].style.display = found ? "grid" : "none";
            }
        }
    </script>
</head>
<body>

<div class="cloud cloud1"></div>
<div class="cloud cloud2"></div>
<div class="cloud cloud3"></div>

<div class="page-header-wrapper">
    <h1>Teacher Control Panel! 🧑‍🏫</h1>

    <?php if ($status_message): ?>
        <p class="status-message <?= (strpos($status_message, 'Error') !== false) ? 'error' : ''; ?>">
            <?= $status_message; ?>
        </p>
    <?php endif; ?>
</div>

<div class="content-grid">

    <div class="list-section">
        <h2>Student List (<?= count($students); ?> Total) 📝</h2>

        <div class="student-list-controls">
            <input type="text" id="studentSearch" onkeyup="filterStudents()" placeholder="Search students by name, grade, or section..." class="search-input">
        </div>

        <?php if (!empty($students)): ?>
            <table class="student-table">
                <thead>
                <tr>
                    <th>Full Name</th><th>Username</th><th>Grade</th><th>Section</th><th>Password</th><th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td data-label="Full Name"><?= htmlspecialchars($student['fullname']); ?></td>
                        <td data-label="Username"><?= htmlspecialchars($student['username']); ?></td>
                        <td data-label="Grade"><?= htmlspecialchars($student['grade']); ?></td>
                        <td data-label="Section"><?= htmlspecialchars($student['section']); ?></td>
                        <td data-label="Password"><span class="password-data"><?= htmlspecialchars($student['password']); ?></span></td>

                        <td data-label="Actions">
                            <a href="../edit.php?id=<?= htmlspecialchars($student['id']); ?>" class="action-btn edit-btn">
                                ✏️ Edit
                            </a>
                            <a href="../archive_student.php?id=<?= htmlspecialchars($student['id']); ?>" class="action-btn archive-btn"
                               onclick="return confirm('Are you sure you want to archive <?= htmlspecialchars($student['fullname']); ?>?');">
                                🗑️ Archive
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-data-alert">🚫 No students found. Click "Add New Student" to get started!</p>
        <?php endif; ?>
    </div>

    <div class="leaderboard-section">
        <h2>Top 10 Points Achievers 🏆</h2>
        <?php if (!empty($leaderboard)): ?>
            <ul class="leaderboard-list">
                <?php foreach ($leaderboard as $index => $student): ?>
                    <li>
                        <span class="rank"><?= $index + 1; ?><?= $index < 3 ? '✨' : ''; ?></span>
                        <span class="name-and-class">
                            <?= htmlspecialchars($student['fullname']); ?><br>
                            <small style="font-weight: normal; color: #777;">(G<?= htmlspecialchars($student['grade']) ?> - S<?= htmlspecialchars($student['section']) ?>)</small>
                        </span>
                        <span class="total-score-display">
                            <?= htmlspecialchars($student['total_score']); ?> pts
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="no-data-alert">⚠️ No scores yet! Encourage students to play and learn!</p>
        <?php endif; ?>
    </div>

</div>

<a href="../signup.php" class="add-student-fab">
    + Add New Student
</a>

<a href="../login.php" class="logout-link">Log Me Out! 🚪</a>

</body>
</html>