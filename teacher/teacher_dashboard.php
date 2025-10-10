<?php
// 1. START THE SESSION FIRST
// This MUST be the first line of executable code to avoid 'Undefined global variable $_SESSION' warnings.
global $conn;
session_start();

// 2. INCLUDE THE DATABASE CONNECTION FILE
// Corrected Path: The '../' moves up one directory level (to 'verbal/')
require_once '../config/database.php';

// 3. AUTHENTICATION AND REDIRECTION
// Ensure only logged-in teachers can access this page
// This check handles redirecting users who have logged out or are unauthorized.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}

// 4. DATABASE QUERY
// MODIFIED: 'password' has been added to the SELECT statement.
$sql = "SELECT fullname, username, grade, section, password FROM students ORDER BY grade, section, fullname";
$result = $conn->query($sql);

$students = [];
// Check if the query was successful and returned rows
if ($result && $result->num_rows > 0) {
    // Fetch all rows into an array
    while($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}

// 5. Close the database connection (Best practice)
if (isset($conn)) {
    $conn->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
    <style>
        /* BASE SKY THEME STYLES */
        body {
            margin: 0; padding: 0; min-height: 100vh; font-family: 'Comic Sans MS', cursive, sans-serif;
            background: linear-gradient(to bottom, #87ceeb, #ccf2ff); overflow-x: hidden;
            position: relative; display: flex; flex-direction: column; align-items: center; padding-top: 50px;
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

        /* --- DASHBOARD SPECIFIC STYLES --- */
        .dashboard-container {
            background: rgba(255, 255, 255, 0.95); padding: 30px; border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3); width: 90%; max-width: 900px;
            text-align: center; z-index: 10; border: 5px solid #4fc3f7; animation: pop 0.8s ease;
            margin-bottom: 50px; /* Add space at the bottom */
        }
        @keyframes pop {
            0% { transform: scale(0.8); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        h1 {
            color: #01579b; font-size: 2.5em; margin-bottom: 10px;
            text-shadow: 2px 2px 0 #ccf2ff;
        }

        h2 {
            color: #0288d1; font-size: 1.8em; margin-top: 20px;
        }

        p {
            color: #333; font-size: 1.1em; margin-bottom: 20px;
        }

        .logout-link {
            display: inline-block; padding: 10px 20px; margin-top: 30px; /* Added margin to separate from table */
            background-color: #ff8a65; color: white; border-radius: 15px;
            text-decoration: none; font-weight: bold; font-size: 1.1em;
            transition: background-color 0.3s; box-shadow: 0 4px 0 #e65100;
        }
        .logout-link:hover {
            background-color: #ffab91;
        }

        /* TABLE STYLES */
        .student-table {
            width: 100%; border-collapse: separate; border-spacing: 0 8px; margin-top: 20px;
        }

        .student-table thead th {
            background-color: #0288d1; color: #fff; padding: 15px 10px; font-size: 1.1em;
            text-transform: uppercase; border-radius: 15px; box-shadow: 0 5px 0 #01579b;
        }

        .student-table td {
            padding: 15px 10px; font-size: 1em; font-weight: bold; color: #333;
            background-color: #e3f2fd; border-radius: 12px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: left;
        }

        /* Alternating row colors */
        .student-table tbody tr:nth-child(odd) td { background-color: #ffffff; }

    </style>
</head>
<body>

<div class="cloud cloud1"></div>
<div class="cloud cloud2"></div>
<div class="cloud cloud3"></div>

<div class="dashboard-container">
    <h1>Welcome, <?= htmlspecialchars($_SESSION['username']); ?> (Teacher) 👋</h1>
    <p>This is your dashboard. Here is a full list of all students and their credentials:</p>

    <?php if (!empty($students)): ?>
        <h2>Student List 📝</h2>
        <table class="student-table">
            <thead>
            <tr>
                <th>Full Name</th>
                <th>Username</th>
                <th>Grade</th>
                <th>Section</th>
                <th>Password</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($students as $student): ?>
                <tr>
                    <td><?= htmlspecialchars($student['fullname']); ?></td>
                    <td><?= htmlspecialchars($student['username']); ?></td>
                    <td><?= htmlspecialchars($student['grade']); ?></td>
                    <td><?= htmlspecialchars($student['section']); ?></td>
                    <td><?= htmlspecialchars($student['password']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>🚫 No students found or an error occurred while fetching data. Check your database connection!</p>
    <?php endif; ?>

    <a href="../login.php" class="logout-link">Logout</a>

</div>

</body>
</html>