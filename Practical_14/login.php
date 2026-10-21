<?php
// Practical 14 : Login (DB-backed users + audit of success/failure)
require_once __DIR__ . "/auth.php";

if (!empty($_SESSION["username"])) { redirect_by_role(); }

$error = isset($_GET["msg"]) ? $_GET["msg"] : "";
$type  = isset($_GET["type"]) ? $_GET["type"] : "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {
        $error = "Please enter username and password.";
        $type = "error";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, username, password_hash, role FROM users WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $user = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        if ($user && password_verify($password, $user["password_hash"])) {
            session_regenerate_id(true); // prevent session fixation
            $_SESSION["username"] = $user["username"];
            $_SESSION["role"] = $user["role"];
            $_SESSION["login_time"] = time();
            $_SESSION["last_activity"] = time();
            audit("login_success", "User logged in");
            redirect_by_role();
        } else {
            // Generic message (do not reveal whether username or password failed)
            $error = "Failure: Invalid username or password.";
            $type = "error";
            // Audit the failed attempt under the attempted name
            $_SESSION["username"] = null;
            $u = $username; $r = "";
            try {
                $s = mysqli_prepare($conn, "INSERT INTO audit_log (username, role, action, details, ip) VALUES (?, ?, 'login_failed', 'Invalid credentials', ?)");
                $ip = $_SERVER["REMOTE_ADDR"] ?? "";
                mysqli_stmt_bind_param($s, "sss", $u, $r, $ip);
                mysqli_stmt_execute($s);
                mysqli_stmt_close($s);
            } catch (Throwable $e) {}
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Login - Admin Dashboard</title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="card form-box">
    <h1>Dashboard Login</h1>
    <p class="muted">Role-based access: admin sees full dashboard, staff sees limited view.</p>
    <?php if ($error !== ""): ?>
        <div class="<?php echo ($type === "error") ? "msg-error" : "msg-success"; ?>"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="POST" action="login.php">
        <label>Username</label>
        <input type="text" name="username" required autocomplete="username" placeholder="admin">
        <label>Password</label>
        <input type="password" name="password" required autocomplete="current-password" placeholder="admin123">
        <div class="form-row">
            <button type="submit" class="btn btn-save">Login</button>
        </div>
    </form>
    <p class="muted">Demo: <b>admin / admin123</b> (admin) &nbsp;|&nbsp; <b>staff / staff123</b> (staff)</p>
</div>
</body>
</html>
