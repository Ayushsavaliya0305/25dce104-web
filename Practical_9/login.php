<?php
// login.php — Bonus: proves password_hash() works via password_verify()
require_once "db.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Enter a valid email address.";
    } elseif (empty($password)) {
        $error = "Enter your password.";
    } else {
        $stmt = $conn->prepare("SELECT fullname, password FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            if (password_verify($password, $row["password"])) {
                $success = "Welcome back, " . htmlspecialchars($row["fullname"]) . "! Password verified ✔";
            } else {
                $error = "Incorrect password.";
            }
        } else {
            $error = "No account found with this email.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | Practical 9</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="card">
    <div class="card-header">
        <div class="logo">🔑</div>
        <h1>Welcome Back</h1>
        <p>Login verifies the hashed password</p>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success">✅ <?= $success ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" placeholder="you@example.com" required>
            <label for="password">Password</label>
            <div class="pass-wrap">
                <input type="password" id="password" name="password" placeholder="Your password" required>
                <button type="button" class="toggle" onclick="togglePass('password', this)">Show</button>
            </div>
            <button type="submit" class="btn">Login 🔓</button>
        </form>
        <div class="footer">New user? <a href="register.php">Register here</a></div>
    </div>
</div>
<script src="script.js"></script>
</body>
</html>
