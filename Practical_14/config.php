<?php
// Practical 14 : Central config - DB (self-creating), session, demo-user seeding
// Demo credentials (seeded automatically on first load):
//   admin / admin123  (role: admin)
//   staff / staff123  (role: staff)

define("DB_HOST", "localhost");
define("DB_USER", "root");
define("DB_PASS", "");
define("DB_NAME", "dashboard_db");
define("SESSION_TIMEOUT", 900); // 15 minutes of inactivity

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        "lifetime" => 0,
        "path"     => "/",
        "secure"   => isset($_SERVER["HTTPS"]),
        "httponly" => true,
        "samesite" => "Lax"
    ]);
    session_start();
}

// --- Connect (no DB selected yet) and self-create dashboard_db ---
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS);
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
mysqli_select_db($conn, DB_NAME);
mysqli_set_charset($conn, "utf8mb4");

// --- Self-create tables (so the app works even if database.sql was skipped) ---
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','staff') NOT NULL DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT '',
    action VARCHAR(50) NOT NULL,
    details VARCHAR(255) NULL,
    ip VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_action (action),
    INDEX idx_created (created_at)
)");

/** Insert demo accounts on first run (hashes generated at runtime - always valid). */
function ensure_demo_users($conn) {
    $res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM users");
    $row = $res ? mysqli_fetch_assoc($res) : ["c" => 1];
    if ((int)$row["c"] > 0) return;
    $demos = [
        ["admin", "admin123", "admin"],
        ["staff", "staff123", "staff"],
    ];
    $stmt = mysqli_prepare($conn, "INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)");
    foreach ($demos as [$u, $p, $r]) {
        $h = password_hash($p, PASSWORD_DEFAULT);
        mysqli_stmt_bind_param($stmt, "sss", $u, $h, $r);
        mysqli_stmt_execute($stmt);
    }
    mysqli_stmt_close($stmt);
}
ensure_demo_users($conn);
?>
