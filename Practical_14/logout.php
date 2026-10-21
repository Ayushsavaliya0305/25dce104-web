<?php
// Practical 14 : Logout (audited)
require_once __DIR__ . "/auth.php";
if (!empty($_SESSION["username"])) {
    audit("logout", "User logged out");
}
$_SESSION = [];
session_destroy();
header("Location: login.php?msg=" . urlencode("Success: Logged out.") . "&type=success");
exit;
?>
