<?php
// admin.php - Admin-only page (role-based access)
require_once __DIR__ . '/auth.php';
require_role('admin'); // only role=admin allowed, others get 403
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Admin Panel - Practical 10</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="box">
  <h2>Admin Panel</h2>
  <p>Welcome, Admin <b><?= htmlspecialchars($_SESSION['username']) ?></b>!</p>
  <p>This page is restricted to <b>admin</b> role only.</p>
  <p>
    <a href="dashboard.php">User Dashboard</a> |
    <a href="logout.php">Logout</a>
  </p>
</div>
</body>
</html>
