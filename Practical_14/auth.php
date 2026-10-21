<?php
// Practical 14 : Auth guards (login + role) and audit-log helper
require_once __DIR__ . "/config.php";

/** Write one row to dashboard_db.audit_log. Never breaks the page on failure. */
function audit($action, $details = "") {
    global $conn;
    try {
        $username = $_SESSION["username"] ?? "guest";
        $role     = $_SESSION["role"] ?? "";
        $ip       = $_SERVER["REMOTE_ADDR"] ?? "";
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO audit_log (username, role, action, details, ip) VALUES (?, ?, ?, ?, ?)"
        );
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sssss", $username, $role, $action, $details, $ip);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    } catch (Throwable $e) {
        // Audit must never crash the app
    }
}

function current_role() {
    return $_SESSION["role"] ?? null;
}

/** Block non-logged-in users; enforces SESSION_TIMEOUT. */
function require_login() {
    if (empty($_SESSION["username"])) {
        header("Location: login.php?msg=" . urlencode("Please log in first.") . "&type=error");
        exit;
    }
    if (isset($_SESSION["last_activity"]) && (time() - $_SESSION["last_activity"] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        header("Location: login.php?msg=" . urlencode("Session timed out. Please log in again.") . "&type=error");
        exit;
    }
    $_SESSION["last_activity"] = time();
}

/** Block everyone except role=admin (403 for staff). */
function require_admin() {
    require_login();
    if (current_role() !== "admin") {
        http_response_code(403);
        die("<h2>403 Forbidden</h2><p>Admin role required.</p><a href='user_dashboard.php'>Back to Dashboard</a>");
    }
}

/** Send each role to its landing page. */
function redirect_by_role() {
    if (current_role() === "admin") { header("Location: admin_dashboard.php"); exit; }
    header("Location: user_dashboard.php"); exit;
}
?>
