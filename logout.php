<?php
session_start();

// Burahin ang welcome voice record
session_unset();
session_destroy();

// Dahil magkasama sila sa main folder, login.php lang ang ilagay
header("Location: login.php");
exit();
?>