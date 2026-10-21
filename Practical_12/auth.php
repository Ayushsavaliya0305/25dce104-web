<?php
// Practical 12 : Admin session guard
// Demo credentials -> admin / admin123 (see login.php)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_admin() {
    if (empty($_SESSION["admin_user"])) {
        header("Location: login.php?msg=" . urlencode("Please log in as admin first.") . "&type=error");
        exit;
    }
}
?>
