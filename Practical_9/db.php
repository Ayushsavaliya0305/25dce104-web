<?php
// db.php — Central MySQLi database connection for Practical 9
// Change credentials as per your XAMPP setup (default: root / no password)

$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "practical9_db";

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

// Set charset to support all characters safely
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Database connection failed: " . htmlspecialchars($conn->connect_error));
}
?>
