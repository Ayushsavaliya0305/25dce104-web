<?php
// dashboard.php - Restricted page for any logged-in user
require_once __DIR__ . '/auth.php';
require_login(); // blocks non-logged-in + handles timeout
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Dashboard - Practical 10</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="box">
  <h2>User Dashboard</h2>
  <p>Welcome, <b><?= htmlspecialchars($_SESSION['username']) ?></b>!</p>
  <p>Role: <b><?= htmlspecialchars($_SESSION['role']) ?></b></p>
  <p>Login time: <?= date('d-m-Y H:i:s', $_SESSION['login_time']) ?></p>
  <p>Session timeout: <?= SESSION_TIMEOUT / 60 ?> min of inactivity.</p>
  <p>
    <?php if ($_SESSION['role'] === 'admin'): ?>
      <a href="admin.php">Go to Admin Panel</a> |
    <?php endif; ?>
    <a href="logout.php">Logout</a>
  </p>
</div>
</body>
</html>
