<?php
// Practical 12 : Admin Logout
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$_SESSION = [];
session_destroy();
header("Location: login.php?msg=" . urlencode("Success: Logged out.") . "&type=success");
exit;
?>
