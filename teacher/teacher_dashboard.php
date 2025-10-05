<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Teacher Dashboard</title>
</head>
<body>
<h1>Welcome, <?= htmlspecialchars($_SESSION['username']); ?> (Teacher)</h1>
<p>This is the teacher dashboard.</p>
<a href="../logout.php">Logout</a>
</body>
</html>
