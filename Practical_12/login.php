<?php
// Practical 12 : Admin Login
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!empty($_SESSION["admin_user"])) {
    header("Location: index.php");
    exit;
}

$error = isset($_GET["msg"]) ? $_GET["msg"] : "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    // Demo admin account for the practical (admin / admin123)
    if ($username === "admin" && $password === "admin123") {
        session_regenerate_id(true);
        $_SESSION["admin_user"] = "admin";
        header("Location: index.php?msg=" . urlencode("Success: Welcome, admin.") . "&type=success");
        exit;
    } else {
        $error = "Failure: Invalid admin credentials.";
    }
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Admin Login - Event Management</title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="card form-box">
    <h1>Admin Login</h1>
    <p class="muted">Event Management CRUD &mdash; admin area</p>
    <?php if ($error !== ""): ?><div class="msg-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <form method="POST" action="login.php">
        <label>Username</label>
        <input type="text" name="username" required autocomplete="username" placeholder="admin">
        <label>Password</label>
        <input type="password" name="password" required autocomplete="current-password" placeholder="admin123">
        <div class="form-row">
            <button type="submit" class="btn btn-save">Login</button>
        </div>
    </form>
    <p class="muted">Demo credentials: <b>admin</b> / <b>admin123</b></p>
</div>
</body>
</html>
