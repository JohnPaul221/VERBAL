<?php
// Start session
session_start();

// Include the database connection setup
// NOTE: This file now provides the connection object as $pdo, not $conn.
require_once 'config/database.php';

// Check if $pdo exists and is a PDO object (it should be, or the script would have died)
if (!isset($pdo) || !($pdo instanceof PDO)) {
    // This is a safety fallback, but should not be reached if config/database.php is working.
    die('Critical Error: Database connection object ($pdo) is missing.');
}

$leaderboardData = []; // Initialize empty array

try {
    // 1. SQL Query to Calculate Total Points per Student
    $sql = "
        SELECT
            username,
            SUM(score) AS points
        FROM
            student_ratings
        GROUP BY
            username
        ORDER BY
            points DESC
    ";

    // --- FIX APPLIED HERE: Using $pdo->query() and $pdo->fetchAll() ---
    // Execute the query using the PDO object
    $stmt = $pdo->query($sql);

    // Fetch all results into the leaderboardData array
    $leaderboardData = $stmt->fetchAll();

} catch (\PDOException $e) {
    // Handle database query errors gracefully
    error_log('LEADERBOARD QUERY FAILED: ' . $e->getMessage());
    // Provide a user-friendly error message
    $db_query_error = "An error occurred while retrieving the leaderboard data.";
}

// NOTE: With PDO, the connection does not need to be explicitly closed like with MySQLi.
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>V.E.R.B.A.L. Leaderboard</title>
    <style>
        /* BASE SKY THEME STYLES */
        body {
            margin: 0; padding: 0; height: 100vh; font-family: 'Comic Sans MS', cursive, sans-serif;
            background: linear-gradient(to bottom, #87ceeb, #ccf2ff); overflow: hidden;
            position: relative; display: flex; justify-content: center; align-items: center;
        }
        .cloud { position: absolute; background: #fff; border-radius: 50%; opacity: 0.9; }
        .cloud:before, .cloud:after { content: ''; position: absolute; background: #fff; border-radius: 50%; }
        .cloud1 { width: 120px; height: 60px; top: 10%; animation: float 70s linear infinite; left: -200px; animation-delay: 0s; }
        .cloud1:before { width: 60px; height: 60px; top: -30px; left: 10px; }
        .cloud1:after { width: 80px; height: 80px; top: -40px; right: 15px; }
        .cloud2 { width: 150px; height: 70px; top: 20%; animation: float 90s linear infinite; left: -200px; animation-delay: 45s; }
        .cloud2:before { width: 70px; height: 70px; top: -35px; left: 20px; }
        .cloud2:after { width: 90px; height: 90px; top: -45px; right: 25px; }
        .cloud3 { width: 180px; height: 80px; bottom: 20%; left: -200px; animation: float 80s linear infinite; }
        .cloud3:before { width: 90px; height: 90px; top: -45px; left: 30px; }
        .cloud3:after { width: 110px; height: 110px; top: -55px; right: 20px; }

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

        /* --- LEADERBOARD SPECIFIC STYLES --- */
        .leaderboard-container {
            background: rgba(255, 255, 255, 0.95); padding: 30px; border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3); width: 90%; max-width: 650px;
            text-align: center; z-index: 10; border: 5px solid #4fc3f7; animation: pop 0.8s ease;
            position: relative;
        }
        @keyframes pop {
            0% { transform: scale(0.8); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        h1 {
            color: #01579b; font-size: 2.5em; margin-bottom: 20px;
            text-shadow: 2px 2px 0 #ccf2ff;
        }

        #leaderboard {
            width: 100%; border-collapse: separate; border-spacing: 0 8px; margin-top: 20px;
        }

        #leaderboard thead th {
            background-color: #0288d1; color: #fff; padding: 15px 10px; font-size: 1.3em;
            text-transform: uppercase; border-radius: 15px; box-shadow: 0 5px 0 #01579b;
        }

        #leaderboard td {
            padding: 15px 10px; font-size: 1.1em; font-weight: bold; color: #333;
            background-color: #e3f2fd; border-radius: 12px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        /* Alternating row colors for visual interest */
        #leaderboard tbody tr:nth-child(odd) td { background-color: #ffffff; }

        /* Rank Column (First Cell) */
        #leaderboard tbody tr td:first-child {
            width: 15%; text-align: center; font-size: 1.5em; background-color: #4fc3f7;
            color: white; border-radius: 12px 0 0 12px;
        }
        /* Name Column */
        #leaderboard tbody tr td:nth-child(2) { text-align: left; padding-left: 25px; }
        /* Points Column */
        #leaderboard tbody tr td:last-child { width: 20%; text-align: center; }

        /* Top 3 Medals/Colors */
        #leaderboard tbody tr:nth-child(1) td { background-color: #ffeb3b; color: #01579b; }
        #leaderboard tbody tr:nth-child(1) td:first-child { background-color: #fdd835; }
        #leaderboard tbody tr:nth-child(2) td { background-color: #b0bec5; }
        #leaderboard tbody tr:nth-child(2) td:first-child { background-color: #90a4ae; }
        #leaderboard tbody tr:nth-child(3) td { background-color: #ffab91; }
        #leaderboard tbody tr:nth-child(3) td:first-child { background-color: #ff8a65; }

        /* --- BACK BUTTON STYLES --- */
        .back-button {
            position: absolute; top: 15px; left: 15px;
            padding: 10px 15px;
            background-color: #0288d1;
            color: #fff;
            text-decoration: none;
            border-radius: 15px;
            font-weight: bold;
            font-size: 1em;
            box-shadow: 0 4px 0 #01579b;
            transition: all 0.1s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            z-index: 11;
        }
        .back-button:hover {
            background-color: #03a9f4;
            box-shadow: 0 2px 0 #01579b;
            transform: translateY(2px);
        }
        .back-button:active {
            transform: translateY(4px);
            box-shadow: none;
        }
    </style>
</head>
<body>

<div class="cloud cloud1"></div>
<div class="cloud cloud2"></div>
<div class="cloud cloud3"></div>

<div class="balloon">🎈</div>

<div class="leaderboard-container">
    <a href="student/student_dashboard.php" class="back-button">
        ⬅️ Dashboard
    </a>

    <h1>V.E.R.B.A.L. Leaderboard</h1>

    <table id="leaderboard">
        <thead>
        <tr>
            <th>Rank</th>
            <th>Name</th>
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
        // 2. Loop through the fetched data and generate the table rows
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
                <td><?= htmlspecialchars($student['username']) ?></td>
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