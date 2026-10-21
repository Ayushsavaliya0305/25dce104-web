<?php
// Practical 14 : Role-based landing (index.php)
require_once __DIR__ . "/auth.php";
if (empty($_SESSION["username"])) {
    header("Location: login.php");
    exit;
}
redirect_by_role();
?>
