<?php
// login.php - Login form + verification with password_verify()
require_once __DIR__ . '/auth.php';

// If already logged in (session OR valid remember-me), go to role page
if (try_remember_login() && isset($_SESSION['username'])) {
    redirect_by_role();
}

$error = '';
if (isset($_GET['timeout'])) {
    $error = 'Session timed out due to inactivity. Please login again.';
}
if (isset($_GET['loggedout'])) {
    $error = 'You have been logged out securely.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if ($username === '' || $password === '') {
        $error = 'Please enter username and password.';
    } elseif (!isset($users[$username])) {
        $error = 'Invalid username or password.'; // generic msg: don't reveal which failed
    } elseif (!password_verify($password, $users[$username]['hash'])) {
        $error = 'Invalid username or password.';
    } else {
        // SUCCESS: prevent session fixation
        session_regenerate_id(true);
        $_SESSION['username']      = $username;
        $_SESSION['role']          = $users[$username]['role'];
        $_SESSION['login_time']    = time();
        $_SESSION['last_activity'] = time();

        if ($remember) {
            create_remember_token($username);
        }
        redirect_by_role(); // admin -> admin.php, user -> dashboard.php
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Login - Practical 10</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="box">
  <h2>Secure Login</h2>
  <?php if ($error): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>
  <form method="POST" action="login.php">
    <label>Username</label>
    <input type="text" name="username" required
           value="<?= isset($username) ? htmlspecialchars($username) : '' ?>">
    <label>Password</label>
    <input type="password" name="password" required>
    <label class="inline">
      <input type="checkbox" name="remember"> Remember me (30 days)
    </label>
    <button type="submit">Login</button>
  </form>
  <p class="hint">Demo: <b>admin / admin123</b> (admin) &nbsp;|&nbsp; <b>user / user123</b> (user)</p>
</div>
</body>
</html>
