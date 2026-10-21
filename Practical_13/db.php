<?php
// Practical 13 : DB Connection (MySQLi) - shared by all JSON API endpoints
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "event_db";

$conn = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if (!$conn) {
    http_response_code(500);
    header("Content-Type: application/json");
    echo json_encode(["success" => false, "message" => "Database connection failed."]);
    exit;
}
mysqli_set_charset($conn, "utf8mb4");
?>
