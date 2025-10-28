<?php
session_start();
require_once '../config/database.php';
global $pdo;

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}
$teacher_id = $_SESSION['user_id'];
$teacher_grade = '';
$teacher_section = '';
$students = [];
$leaderboard = [];
$data_fetch_error = false;


$status_message = '';
$status_type = '';

if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success_edit') {
        $_SESSION['status_message'] = "✅ Student record updated successfully!";
    } elseif ($_GET['status'] == 'success_archive') {
        $_SESSION['status_message'] = "🗑️ Student successfully archived!";
    } elseif ($_GET['status'] == 'not_found') {
        $_SESSION['status_message'] = "❌ Error: Student record not found.";
        $_SESSION['status_type'] = 'error';
    }

    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit();
}
if (isset($_SESSION['status_message'])) {
    $status_message = $_SESSION['status_message'];
    $status_type = $_SESSION['status_type'] ?? '';


    unset($_SESSION['status_message']);
    unset($_SESSION['status_type']);
}

try {
    $sql_teacher_info = "SELECT grade, section FROM teachers WHERE id = ?";
    $stmt_teacher_info = $pdo->prepare($sql_teacher_info);
    $stmt_teacher_info->execute([$teacher_id]);
    $teacher_info = $stmt_teacher_info->fetch(PDO::FETCH_ASSOC);

    if ($teacher_info) {
        $teacher_grade = $teacher_info['grade'];
        $teacher_section = $teacher_info['section'];
    }

    if ($teacher_grade && $teacher_section) {
        $sql_students = "
            SELECT 
                id, fullname, username, grade, section, password 
            FROM 
                students 
            WHERE 
                grade = ? AND section = ?
            ORDER BY 
                fullname
        ";
        $stmt_students = $pdo->prepare($sql_students);
        $stmt_students->execute([$teacher_grade, $teacher_section]);
        $students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);
        $sql_leaderboard = "
            SELECT 
                s.fullname, 
                s.grade, 
                s.section, 
                COALESCE(SUM(sr.score), 0) AS total_score 
            FROM students s
            LEFT JOIN student_ratings sr ON s.id = sr.student_id
            WHERE 
                s.grade = ? AND s.section = ?
            GROUP BY 
                s.id, s.fullname, s.grade, s.section
            ORDER BY 
                total_score DESC, s.fullname ASC
            LIMIT 10
        ";

        $stmt_leaderboard = $pdo->prepare($sql_leaderboard);
        $stmt_leaderboard->execute([$teacher_grade, $teacher_section]);
        $leaderboard = $stmt_leaderboard->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    error_log("Data Fetch Error: " . $e->getMessage());
    $data_fetch_error = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
    <style>

        body {
            margin: 0;
            padding: 10px;
            height: 100vh;
            overflow: hidden;

            font-family: 'Nunito', 'Arial Rounded MT Bold', sans-serif;
            background: linear-gradient(135deg, #a3b3fa 0%, #ccf2ff 100%);
            overflow-x: hidden;
            position: relative;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .page-header-wrapper {
            position: relative;
            width: 100%;
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            z-index: 10;
        }
        .add-student-link {
            position: absolute;
            top: 10px;
            left: 0;
            background: linear-gradient(145deg, #4CAF50, #388E3C);
            color: white;
            padding: 10px 18px;
            border-radius: 0 20px 20px 0;
            text-decoration: none;
            font-weight: 900;
            font-size: 0.9em;
            box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.2), 0 2px 0 #1B5E20;
            transition: transform 0.2s, box-shadow 0.2s;
            z-index: 20;
        }
        .add-student-link:active {
            box-shadow: 0 1px 0 #1B5E20;
            transform: translateY(1px);
        }
        .logout-link {
            position: absolute;
            top: 10px;
            right: 0;
            display: inline-block;
            padding: 10px 18px;
            background: linear-gradient(145deg, #ffa07a, #ff6347);
            color: white;
            border-radius: 20px 0 0 20px;
            box-shadow: -2px 2px 5px rgba(0, 0, 0, 0.2), 0 2px 0 #d84315;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9em;
            transition: transform 0.2s, box-shadow 0.2s;
            z-index: 20;
        }
        .logout-link:active {
            box-shadow: 0 1px 0 #d84315;
            transform: translateY(1px);
        }
        h1, .class-info, .status-message {
            margin-left: auto;
            margin-right: auto;
            text-align: center;
        }
        @media (min-width: 992px) {
            .page-header-wrapper {
                align-items: flex-start;
                padding-top: 30px;
            }
            .add-student-link {
                top: 20px;
                left: 0;
                padding: 12px 25px;
                font-size: 1.1em;
                border-radius: 0 30px 30px 0;
            }
            .logout-link {
                top: 20px;
                right: 0;
                padding: 12px 25px;
                font-size: 1.1em;
                border-radius: 30px 0 0 30px;
            }
        }
        .content-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
            width: 100%;
            max-width: none;
            margin: 0 auto;
            flex-grow: 1;
            min-height: 0;
        }
        @media (min-width: 992px) {
            .content-grid {
                grid-template-columns: 3fr 1fr;
                grid-auto-rows: 1fr;
                gap: 30px;
            }
        }

        .list-section, .leaderboard-section {
            background: linear-gradient(135deg, #ffffff, #f0f8ff);
            padding: 20px;
            border-radius: 25px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1), 0 3px 0 #b3e5fc;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }
        .scrollable-content {
            flex-grow: 1;
            overflow-y: auto;
            padding-right: 10px;
            margin-right: -10px;
            min-height: 0;
        }
        h1 {
            color: #ff6f61;
            font-size: 2.2em;
            text-shadow: 3px 3px 0 #ffdab9;
            margin-top: 20px;
            margin-bottom: 5px;
            padding: 10px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1), 0 3px 0 #8b99df;
            max-width: 900px;
        }
        @media (min-width: 992px) {
            h1 {
                font-size: 3em;
                padding: 15px;
                margin-bottom: 10px;
            }
        }
        .class-info {
            color: #4CAF50;
            font-size: 1.2em;
            font-weight: 700;
            margin-top: -10px;
            margin-bottom: 10px;
            padding: 5px 10px;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 10px;
            display: block;
            width: fit-content;
        }
        .status-message {
            padding: 15px;
            margin: 20px auto;
            border-radius: 15px;
            background-color: #e8f5e9;
            color: #1b5e20;
            font-weight: 700;
            max-width: 600px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);

            opacity: 1;
            transition: opacity 0.5s ease-out;
        }
        .status-message.error {
            background-color: #ffcdd2;
            color: #b71c1c;
        }

        .student-list-controls {
            display: flex;

            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            gap: 10px;
            flex-shrink: 0;
        }
        @media (min-width: 600px) {
            .student-list-controls {
                flex-direction: row;
                gap: 15px;
                margin-bottom: 20px;
            }
        }
        .search-input {
            flex-grow: 1;
            padding: 10px 20px;
            border-radius: 20px;
            border: 2px solid #ccc;
            font-size: 1em;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: border-color 0.3s;
            width: 100%;
        }
        .search-input:focus {
            outline: none;
            border-color: #4fc3f7;
        }
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

        p { color: #555; font-size: 1.2em; margin-bottom: 20px; }

        .student-table { display: block; width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 20px; }
        .student-table thead { display: none; }
        .student-table tbody tr {
            display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 15px; padding: 15px; border-radius: 25px;
            background: linear-gradient(135deg, #fce4ec, #fff8e1); box-shadow: 0 8px 15px rgba(0,0,0,0.1), 0 3px 0 #d81b60; transition: transform 0.2s, box-shadow 0.2s;
        }
        @media (min-width: 768px) {

            .student-table tbody tr { grid-template-columns: 1.5fr 1fr 0.5fr 0.5fr 1.5fr 2fr; align-items: center; }
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


        .password-cell {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .password-data {
            word-break: break-all;
            display: inline-block;

            font-family: 'Arial', sans-serif;
            margin-right: 10px;
        }

        .password-data.visible {
            font-family: 'Nunito', 'Arial Rounded MT Bold', sans-serif;
        }

        .toggle-btn {
            background: #e0e0e0;
            color: #555;
            border: none;
            border-radius: 10px;
            padding: 5px 8px;
            cursor: pointer;
            font-size: 0.8em;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: background 0.2s;
            flex-shrink: 0;
            min-width: 40px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .toggle-btn:hover {
            background: #ccc;
        }

        .leaderboard-list { list-style: none; padding: 0; margin: 10px 0 0 0; }
        .leaderboard-list li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 18px;
            margin-bottom: 12px;
            background: linear-gradient(145deg, #ffecb3, #ffe082);
            border-radius: 20px;
            box-shadow: 0 4px 10px rgba(255, 179, 0, 0.3), 0 2px 0 #ffb300;
            font-weight: bold;
            color: #5d4037;

            flex-wrap: wrap;
            gap: 8px;
        }
        .leaderboard-list li .name-and-class {
            flex-grow: 1;
        }
        .rank { font-size: 1.8em; font-weight: 900; }
        .total-score-display { font-size: 1.4em; color: white; background-color: #4CAF50; padding: 5px 12px; border-radius: 15px; box-shadow: 0 3px 0 #388E3C; }
        .leaderboard-list li:nth-child(1) .rank { color: #d4ac00; font-size: 2.2em; }

    </style>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <script>

        function filterStudents() {
            let input = document.getElementById('studentSearch');
            let filter = input.value.toUpperCase();

            let table = document.querySelector('.list-section .scrollable-content .student-table tbody');
            if (!table) return;
            let trs = table.getElementsByTagName('tr');

            for (let i = 0; i < trs.length; i++) {
                let found = false;

                let fullname = trs[i].querySelector('td[data-label="Full Name"]').textContent || trs[i].querySelector('td[data-label="Full Name"]').innerText;
                let username = trs[i].querySelector('td[data-label="Username"]').textContent || trs[i].querySelector('td[data-label="Username"]').innerText;

                if (fullname.toUpperCase().indexOf(filter) > -1 || username.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                }
                trs[i].style.display = found ? "grid" : "none";
            }
        }


        function togglePasswordVisibility(button) {
            const passwordSpan = button.previousElementSibling;
            const rawPassword = passwordSpan.getAttribute('data-raw-password');

            if (passwordSpan.textContent === '********') {

                passwordSpan.textContent = rawPassword;
                passwordSpan.classList.add('visible');
                button.textContent = '🙈 Hide';
            } else {

                passwordSpan.textContent = '********';
                passwordSpan.classList.remove('visible');
                button.textContent = '👀 Show';
            }
        }


        document.addEventListener('DOMContentLoaded', () => {

            document.querySelectorAll('.password-data').forEach(span => {
                if (span.textContent.trim() !== '') {
                    span.setAttribute('data-raw-password', span.textContent);
                    span.textContent = '********';
                }
            });


            const statusMessage = document.getElementById('statusMessage');

            if (statusMessage) {

                setTimeout(() => {

                    statusMessage.style.opacity = '0';


                    setTimeout(() => {
                        statusMessage.style.display = 'none';
                    }, 500);
                }, 3000);
            }
        });
    </script>
</head>
<body>
<div class="page-header-wrapper">
    <a href="../signup.php" class="add-student-link">
        + Add New Student
    </a>

    <a href="../login.php" class="logout-link">Log Me Out! 🚪</a>

    <h1>Teacher Panel! 🧑‍🏫</h1>
    <?php if ($status_message):  ?>
        <p class="status-message <?= ($status_type == 'error') ? 'error' : ''; ?>" id="statusMessage">
            <?= $status_message; ?>
        </p>
    <?php endif; ?>

    <?php if ($data_fetch_error): ?>
        <p class="status-message error">
            ⚠️ Database Error: Failed to load class data. Please check the logs or contact the administrator.
        </p>
    <?php endif; ?>

    <?php if ($teacher_grade && $teacher_section): ?>
        <span class="class-info">Grade: <?= htmlspecialchars($teacher_grade); ?> | Section: <?= htmlspecialchars($teacher_section); ?></span>
    <?php endif; ?>
</div>
<div class="content-grid">

    <div class="list-section">
        <h2>Student List (<?= count($students); ?> Class Students) 📝</h2>

        <div class="student-list-controls">
            <input type="text" id="studentSearch" onkeyup="filterStudents()" placeholder="Search students by name or username..." class="search-input">
        </div>

        <div class="scrollable-content">
            <?php

            if (!empty($teacher_grade) && !empty($teacher_section) && !$data_fetch_error):
                ?>
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

                            <td data-label="Password" class="password-cell">
                                    <span class="password-data">
                                        <?= htmlspecialchars($student['password']); ?>
                                    </span>
                                <button class="toggle-btn" onclick="togglePasswordVisibility(this)">
                                    👀 Show
                                </button>
                            </td>

                            <td data-label="Actions">
                                <a href="../edit.php?id=<?= htmlspecialchars($student['id']); ?>" class="action-btn edit-btn">
                                    ✏️ Edit
                                </a>
                                <a href="../archive_student.php?id=<?= htmlspecialchars($student['id']); ?>&status_redirect=teacher_dashboard" class="action-btn archive-btn"
                                   onclick="return confirm('Are you sure you want to archive <?= htmlspecialchars($student['fullname']); ?>?');">
                                    🗑️ Archive
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data-alert">🚫 No students found in your assigned class (Grade <?= htmlspecialchars($teacher_grade) ?> - Section <?= htmlspecialchars($teacher_section) ?>).</p>
            <?php endif; ?>
            <?php else: ?>
                <p class="no-data-alert">⚠️ Error: Could not determine your assigned class or a critical data error occurred. Please contact the administrator.</p>
            <?php endif; ?>
        </div> </div>

    <div class="leaderboard-section">
        <h2>Top 10 Class Achievers 🏆</h2>
        <div class="scrollable-content">
            <?php if (!empty($leaderboard)): ?>
                <ul class="leaderboard-list">
                    <?php foreach ($leaderboard as $index => $student): ?>
                        <li>
                            <span class="rank"><?= $index + 1; ?><?= $index < 3 ? '✨' : ''; ?></span>
                            <span class="name-and-class">
                                <?= htmlspecialchars($student['fullname']); ?><br>
                                <small style="font-weight: normal; color: #777;">(<?= htmlspecialchars($student['section']) ?>)</small>
                            </span>
                            <span class="total-score-display">
                                <?= htmlspecialchars($student['total_score']); ?> pts
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="no-data-alert">⚠️ No scores yet for your class! Encourage students to play and learn!</p>
            <?php endif; ?>
        </div> </div>

</div>
</body>
</html>