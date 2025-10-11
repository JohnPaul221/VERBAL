<?php
// Start session
session_start();

// 🔥 Path adjustment assumption: leaderboard.php is in the root VERBAL/ directory.
require_once 'config/database.php';

// Check if the PDO object is available (safety check)
if (!isset($pdo) || !($pdo instanceof PDO)) {
    die('Critical Error: Database connection object ($pdo) is missing.');
}

$leaderboardData = []; // Initialize empty array
$db_query_error = null; // Initialize error variable

try {
    // SQL Query to Calculate Total Points per Student
    $sql = "
        SELECT
            s.username,
            s.grade,
            s.section,
            COALESCE(SUM(sr.score), 0) AS points
        FROM
            students s
        LEFT JOIN
            student_ratings sr ON s.username = sr.username
        GROUP BY
            s.username, s.grade, s.section
        ORDER BY
            points DESC, s.username ASC
    ";

    // Execute the query using the PDO object
    $stmt = $pdo->query($sql);

    // Fetch all results into the leaderboardData array
    $leaderboardData = $stmt->fetchAll();

} catch (\PDOException $e) {
    // Handle database query errors gracefully
    error_log('LEADERBOARD QUERY FAILED: ' . $e->getMessage());
    $db_query_error = "An error occurred while retrieving the leaderboard data.";
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
        /* BASE CHUNKY SKY THEME */
        body {
            margin: 0; padding: 0; height: 100vh;
            font-family: 'Nunito', sans-serif; /* Changed Font */
            background: linear-gradient(135deg, #a3b3fa 0%, #ccf2ff 100%); /* Blue/Purple Gradient */
            overflow: hidden;
            position: relative; display: flex; justify-content: center; align-items: center;
        }

        /* CLOUD ANIMATION (Kept existing styles) */
        .cloud { position: absolute; background: #fff; border-radius: 50%; opacity: 0.9; }
        .cloud1 { width: 120px; height: 60px; top: 10%; animation: float 70s linear infinite; left: -200px; animation-delay: 0s; }
        .cloud2 { width: 150px; height: 70px; top: 20%; animation: float 90s linear infinite; left: -200px; animation-delay: 45s; }
        .cloud3 { width: 180px; height: 80px; bottom: 20%; left: -200px; animation: float 80s linear infinite; }
        @keyframes float {
            from { transform: translateX(0); }
            to   { transform: translateX(120vw); }
        }

        .balloon {
            position: absolute; animation: floatY 10s ease-in-out infinite alternate;
            opacity: 0.9; font-size: 80px; top: 15%; right: 10%; z-index: 1;
        }
        @keyframes floatY {
            from { transform: translateY(0px); }
            to   { transform: translateY(-25px); }
        }

        /* --- LEADERBOARD CHUNKY STYLES --- */
        .leaderboard-container {
            background: #ffffff;
            padding: 40px; /* Increased padding */
            border-radius: 40px; /* CHUNKY RADIUS */
            /* CHUNKY SHADOW */
            box-shadow: 0 15px 35px rgba(0,0,0,0.3), 0 5px 0 #8b99df;
            width: 90%; max-width: 700px;
            text-align: center; z-index: 10;
            border: none; /* Removed hard border */
            animation: pop 0.8s ease;
            position: relative;
        }
        @keyframes pop {
            0% { transform: scale(0.8); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        h1 {
            color: #ff6f61; /* Vibrant color */
            font-size: 3em;
            margin-bottom: 25px;
            text-shadow: 3px 3px 0 #ffdab9; /* Chunky shadow */
            font-weight: 900;
        }

        #leaderboard {
            width: 100%; border-collapse: separate; border-spacing: 0 12px; /* Increased spacing */
        }

        #leaderboard thead th {
            background-color: #4a54ff; /* Deep Blue Header */
            color: #fff;
            padding: 18px 15px; /* Increased padding */
            font-size: 1.2em;
            text-transform: uppercase;
            border-radius: 20px; /* CHUNKY RADIUS */
            box-shadow: 0 6px 0 #0288d1; /* Stronger button-like shadow */
            font-weight: 800;
        }

        #leaderboard td {
            padding: 18px 15px;
            font-size: 1.2em;
            font-weight: 700;
            color: #444;
            background-color: #e3f2fd;
            border-radius: 18px; /* CHUNKY RADIUS */
            box-shadow: 0 3px 8px rgba(0,0,0,0.1); /* Soft lifted shadow */
            transition: transform 0.2s;
        }
        #leaderboard tbody tr:hover td {
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0,0,0,0.15);
        }

        /* Alternating row colors (Subtle effect) */
        #leaderboard tbody tr:nth-child(odd) td { background-color: #ffffff; }

        /* Rank Column (First Cell) */
        #leaderboard tbody tr td:first-child {
            width: 10%;
            text-align: center;
            font-size: 1.8em;
            background: #60a5fa; /* Default rank color */
            color: white;
            border-radius: 18px 0 0 18px;
            font-weight: 900;
        }
        /* Name/Grade Column */
        #leaderboard tbody tr td:nth-child(2) { text-align: left; padding-left: 30px; }
        /* Points Column */
        #leaderboard tbody tr td:last-child {
            width: 15%;
            text-align: center;
            font-size: 1.5em;
            color: #d84315; /* Score color */
            background-color: #ffccbc; /* Light background for points */
            border-radius: 0 18px 18px 0;
        }
        .grade-section {
            font-size: 0.8em;
            color: #777;
            font-weight: 600;
            display: block;
        }


        /* --- TOP 3 MEDALS/COLORS (Vibrant Rewards) --- */
        /* GOLD - 1st Place */
        #leaderboard tbody tr:nth-child(1) td { background: linear-gradient(135deg, #ffeb3b, #fdd835); color: #01579b; }
        #leaderboard tbody tr:nth-child(1) td:first-child { background-color: #ffb300; color: #fff; }
        #leaderboard tbody tr:nth-child(1) td:last-child { background: #ffe082; color: #d84315; }

        /* SILVER - 2nd Place */
        #leaderboard tbody tr:nth-child(2) td { background: linear-gradient(135deg, #e0e0e0, #bdbdbd); color: #333; }
        #leaderboard tbody tr:nth-child(2) td:first-child { background-color: #9e9e9e; color: #fff; }
        #leaderboard tbody tr:nth-child(2) td:last-child { background: #cfd8dc; color: #d84315; }

        /* BRONZE - 3rd Place */
        #leaderboard tbody tr:nth-child(3) td { background: linear-gradient(135deg, #ffcc80, #ffb74d); color: #333; }
        #leaderboard tbody tr:nth-child(3) td:first-child { background-color: #ff9800; color: #fff; }
        #leaderboard tbody tr:nth-child(3) td:last-child { background: #ffccbc; color: #d84315; }

        /* --- BACK/LOGIN BUTTON STYLES (MATCHING CHUNKY DESIGN) --- */
        .action-button {
            position: absolute;
            padding: 12px 20px;
            background-color: #0288d1;
            color: #fff;
            text-decoration: none;
            border-radius: 20px; /* CHUNKY RADIUS */
            font-weight: bold;
            font-size: 1.1em;
            box-shadow: 0 4px 0 #01579b;
            transition: all 0.1s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            z-index: 11;
        }
        .action-button:hover { background-color: #03a9f4; }
        .action-button:active { transform: translateY(4px); box-shadow: none; }

        .back-button { top: 25px; left: 25px; } /* Positioning for Back button */
        .teacher-login-button {
            top: 25px; right: 25px;
            background-color: #4CAF50; /* Green color for teacher link */
            box-shadow: 0 4px 0 #388E3C;
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
        ⬅️ Student Dashboard
    </a>

    <a href="teacher/teacher_dashboard.php" class="action-button teacher-login-button">
        🧑‍🏫 Teacher Login
    </a>

    <h1>V.E.R.B.A.L. Leaderboard</h1>

    <table id="leaderboard">
        <thead>
        <tr>
            <th>Rank</th>
            <th>Player Name</th>
            <th>Points</th>
        </tr>
        </thead>
        <tbody>
        <?php
        // Display database error if present
        if (isset($db_query_error)): ?>
            <tr><td colspan="3" style="color: red; background-color: #ffe0b2;"><?= htmlspecialchars($db_query_error) ?></td></tr>
        <?php endif; ?>

        <?php
        // Loop through the fetched data and generate the table rows
        foreach ($leaderboardData as $index => $student):
            $rank = $index + 1;
            $medal = '';

            // Determine the medal emoji for the top 3
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
                <td><?= htmlspecialchars($student['points']) ?></td>
            </tr>
        <?php endforeach;

        // Display a message if no data was found
        if (!isset($db_query_error) && empty($leaderboardData)): ?>
            <tr><td colspan="3">No ratings recorded yet! Start playing!</td></tr>
        <?php endif; ?>

        </tbody>
    </table>
</div>

</body>
</html>