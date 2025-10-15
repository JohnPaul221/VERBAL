<?php
session_start();

require_once 'config/database.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    // Note: In a real-world scenario, you might redirect or show a friendlier message.
    die('Critical Error: Database connection object ($pdo) is missing.');
}

$leaderboardData = [];
$db_query_error = null;
$current_grade = null;

// =========================================================================
// 1. DYNAMIC GRADE FETCHING LOGIC (The Fix)
// =========================================================================

// Check if the user is logged in (using 'username' as an example session key)
if (isset($_SESSION['username'])) {
    $current_username = $_SESSION['username'];

    try {
        // Query the students table to get the grade for the logged-in user
        $sql_grade = "SELECT grade FROM students WHERE username = :username";
        $stmt_grade = $pdo->prepare($sql_grade);
        $stmt_grade->bindParam(':username', $current_username);
        $stmt_grade->execute();
        $student_info = $stmt_grade->fetch(PDO::FETCH_ASSOC);

        if ($student_info) {
            $current_grade = $student_info['grade'];
        }

    } catch (\PDOException $e) {
        error_log('GRADE FETCH FAILED: ' . $e->getMessage());
        $db_query_error = "An error occurred while fetching user information.";
    }
} else {
    // If no user is logged in, restrict access or show a general error
    // For this example, we'll set an error. In a real app, you might redirect to login.
    $db_query_error = "Error: User not logged in. Cannot determine grade.";
}

// =========================================================================
// 2. LEADERBOARD QUERY
// =========================================================================

// Check if a grade was successfully found before attempting the query
if ($current_grade === null && $db_query_error === null) {
    // Only set this error if we didn't already hit a DB error but still missed the grade
    $db_query_error = "Error: Could not determine your current grade.";
}

if ($current_grade !== null) {
    try {
        // The original SQL query, now using the fetched $current_grade
        $sql = "
            SELECT
                s.username,
                s.grade,
                s.section,
                COALESCE(SUM(sr.score), 0) AS points,
                COALESCE(sp.words_attempted, 0) AS attempts
            FROM
                students s
            LEFT JOIN
                student_ratings sr ON s.username = sr.username
            LEFT JOIN
                student_progress sp ON s.id = sp.student_id
            WHERE
                s.grade = :current_grade -- Filter by the fetched grade
            GROUP BY
                s.username, s.grade, s.section, attempts
            ORDER BY
                points DESC,
                attempts ASC,
                s.username ASC
        ";

        // Use prepared statements for safe execution
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':current_grade', $current_grade);
        $stmt->execute();

        $leaderboardData = $stmt->fetchAll();

    } catch (\PDOException $e) {
        error_log('LEADERBOARD QUERY FAILED: ' . $e->getMessage());
        $db_query_error = "An error occurred while retrieving the leaderboard data.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>V.E.R.B.A.L. Leaderboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        /* ... (Your original CSS styles remain here) ... */
        body {
            margin: 0; padding: 0; height: 100vh;
            font-family: 'Nunito', sans-serif;
            background: linear-gradient(135deg, #a3b3fa 0%, #ccf2ff 100%);
            overflow: hidden;
            position: relative; display: flex; justify-content: center; align-items: center;
        }

        /* Reduced cloud sizes and delays for slight change */
        .cloud { position: absolute; background: #fff; border-radius: 50%; opacity: 0.9; }
        .cloud1 { width: 100px; height: 50px; top: 10%; animation: float 60s linear infinite; left: -150px; animation-delay: 0s; }
        .cloud2 { width: 120px; height: 60px; top: 20%; animation: float 80s linear infinite; left: -150px; animation-delay: 40s; }
        .cloud3 { width: 140px; height: 70px; bottom: 20%; left: -150px; animation: float 70s linear infinite; }
        @keyframes float {
            from { transform: translateX(0); }
            to   { transform: translateX(120vw); }
        }

        .balloon {
            position: absolute; animation: floatY 10s ease-in-out infinite alternate;
            opacity: 0.9; font-size: 60px; top: 10%; right: 5%; z-index: 1; /* Smaller balloon and moved slightly */
        }
        @keyframes floatY {
            from { transform: translateY(0px); }
            to   { transform: translateY(-20px); }
        }

        .leaderboard-container {
            background: #ffffff;
            padding: 25px; /* Reduced padding */
            border-radius: 20px; /* Reduced border-radius */
            box-shadow: 0 10px 25px rgba(0,0,0,0.3), 0 4px 0 #8b99df; /* Smaller shadow */
            width: 90%;
            max-width: 950px; /* NEW ADJUSTMENT: Increased max-width for an even bigger container */
            text-align: center; z-index: 10;
            border: none;
            animation: pop 0.8s ease;
            position: relative;
        }
        @keyframes pop {
            0% { transform: scale(0.8); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        h1 {
            color: #ff6f61;
            font-size: 2.2em; /* Reduced font size */
            margin-bottom: 15px; /* Reduced margin */
            text-shadow: 2px 2px 0 #ffdab9; /* Reduced text shadow */
            font-weight: 900;
        }

        #leaderboard {
            width: 100%; border-collapse: separate; border-spacing: 0 8px; /* Reduced spacing */
        }

        #leaderboard thead th {
            background-color: #4a54ff;
            color: #fff;
            padding: 10px 15px; /* Reduced padding */
            font-size: 1em; /* Reduced font size */
            text-transform: uppercase;
            border-radius: 10px; /* Reduced border-radius */
            box-shadow: 0 4px 0 #0288d1; /* Reduced shadow */
            font-weight: 800;
        }

        /* Style for the Attempts column header */
        #leaderboard thead th:nth-child(3) {
            width: 15%;
        }
        /* Adjusted width for Points column header */
        #leaderboard thead th:last-child {
            width: 15%;
        }


        #leaderboard td {
            padding: 12px 10px; /* Reduced padding */
            font-size: 1em; /* Reduced font size */
            font-weight: 700;
            color: #444;
            background-color: #e3f2fd;
            border-radius: 10px; /* Reduced border-radius */
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); /* Reduced shadow */
            transition: transform 0.2s;
        }
        #leaderboard tbody tr:hover td {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        #leaderboard tbody tr:nth-child(odd) td { background-color: #ffffff; }

        #leaderboard tbody tr td:first-child {
            width: 10%;
            text-align: center;
            font-size: 1.4em; /* Reduced font size */
            background: #60a5fa;
            color: white;
            border-radius: 10px 0 0 10px; /* Reduced border-radius */
            font-weight: 900;
        }
        #leaderboard tbody tr td:nth-child(2) { text-align: left; padding-left: 15px; /* Reduced padding */ }

        /* Style for Attempts column data */
        #leaderboard tbody tr td:nth-child(3) {
            width: 15%;
            text-align: center;
            color: #0288d1; /* Blue for attempts */
            background-color: #b3e5fc;
        }

        #leaderboard tbody tr td:last-child {
            width: 15%;
            text-align: center;
            font-size: 1.2em;
            color: #d84315;
            background-color: #ffccbc;
            border-radius: 0 10px 10px 0;
        }

        .grade-section {
            font-size: 0.7em; /* Reduced font size */
            color: #777;
            font-weight: 600;
            display: block;
        }

        /* Top 3 rank styling for the new column */
        /* Gold (Rank 1) */
        #leaderboard tbody tr:nth-child(1) td { background: linear-gradient(135deg, #ffeb3b, #fdd835); color: #01579b; }
        #leaderboard tbody tr:nth-child(1) td:first-child { background-color: #ffb300; color: #fff; }
        #leaderboard tbody tr:nth-child(1) td:nth-child(3) { background-color: #ffecb3; color: #01579b; } /* Attempts */
        #leaderboard tbody tr:nth-child(1) td:last-child { background: #ffe082; color: #d84315; }

        /* Silver (Rank 2) */
        #leaderboard tbody tr:nth-child(2) td { background: linear-gradient(135deg, #e0e0e0, #bdbdbd); color: #333; }
        #leaderboard tbody tr:nth-child(2) td:first-child { background-color: #9e9e9e; color: #fff; }
        #leaderboard tbody tr:nth-child(2) td:nth-child(3) { background-color: #f5f5f5; color: #333; } /* Attempts */
        #leaderboard tbody tr:nth-child(2) td:last-child { background: #cfd8dc; color: #d84315; }

        /* Bronze (Rank 3) */
        #leaderboard tbody tr:nth-child(3) td { background: linear-gradient(135deg, #ffcc80, #ffb74d); color: #333; }
        #leaderboard tbody tr:nth-child(3) td:first-child { background-color: #ff9800; color: #fff; }
        #leaderboard tbody tr:nth-child(3) td:nth-child(3) { background-color: #ffe0b2; color: #333; } /* Attempts */
        #leaderboard tbody tr:nth-child(3) td:last-child { background: #ffccbc; color: #d84315; }

        .action-button {
            position: absolute;
            padding: 8px 15px; /* Reduced padding */
            background-color: #0288d1;
            color: #fff;
            text-decoration: none;
            border-radius: 15px; /* Reduced border-radius */
            font-weight: bold;
            font-size: 0.9em; /* Reduced font size */
            box-shadow: 0 3px 0 #01579b; /* Reduced shadow */
            transition: all 0.1s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px; /* Reduced gap */
            z-index: 11;
        }
        .action-button:hover { background-color: #03a9f4; }
        .action-button:active { transform: translateY(3px); box-shadow: none; }

        .back-button { top: 15px; left: 15px; } /* Moved closer to the corner */
        .teacher-login-button {
            top: 15px; right: 15px; /* Moved closer to the corner */
            background-color: #4CAF50;
            box-shadow: 0 3px 0 #388E3C;
        }
        .teacher-login-button:hover { background-color: #66BB6A; }

    </style>
</head>
<body>

<div class="cloud cloud1"></div>
<div class="cloud cloud2"></div>
<div class="cloud cloud3"></div>

<div class="balloon">🎈</div>

<div class="leaderboard-container">
    <a href="student/student_dashboard.php" class="action-button back-button">
        ⬅️ Dashboard
    </a>

    <a href="teacher/teacher_dashboard.php" class="action-button teacher-login-button">
        🧑‍🏫 Teacher
    </a>

    <h1>V.E.R.B.A.L. Leaderboard (Grade <?= htmlspecialchars($current_grade ?? 'N/A') ?>)</h1>

    <table id="leaderboard">
        <thead>
        <tr>
            <th>Rank</th>
            <th>Player Name</th>
            <th>Attempts</th>
            <th>Points</th>
        </tr>
        </thead>
        <tbody>
        <?php
        if (isset($db_query_error)): ?>
            <tr><td colspan="4" style="color: red; background-color: #ffe0b2;"><?= htmlspecialchars($db_query_error) ?></td></tr>
        <?php endif; ?>

        <?php
        if (!isset($db_query_error)):
            foreach ($leaderboardData as $index => $student):
                $rank = $index + 1;
                $medal = '';

                if ($rank === 1) {
                    $medal = '🥇 ';
                } elseif ($rank === 2) {
                    $medal = '🥈 ';
                } elseif ($rank === 3) {
                    $medal = '🥉 ';
                }
                ?>
                <tr>
                    <td><?= $medal . $rank ?></td>
                    <td>
                        <?= htmlspecialchars($student['username']) ?>
                        <span class="grade-section">(G<?= htmlspecialchars($student['grade']) ?> S<?= htmlspecialchars($student['section']) ?>)</span>
                    </td>
                    <td><?= htmlspecialchars($student['attempts']) ?></td>
                    <td><?= htmlspecialchars($student['points']) ?></td>
                </tr>
            <?php endforeach;
        endif;

        if (!isset($db_query_error) && empty($leaderboardData) && $current_grade !== null): ?>
            <tr><td colspan="4">No ratings recorded yet for Grade <?= htmlspecialchars($current_grade) ?>! Start playing!</td></tr>
        <?php elseif (!isset($db_query_error) && empty($leaderboardData)): ?>
            <tr><td colspan="4">No data available. Please ensure you are logged in.</td></tr>
        <?php endif; ?>

        </tbody>
    </table>
</div>

</body>
</html>