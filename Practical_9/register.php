<?php
// register.php — Practical 9: Secure User Registration (MySQLi + password_hash + duplicate check)
require_once "db.php";

$errors  = [];
$success = "";
// Preserve entered values on error
$fullname = $username = $email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullname = trim($_POST["fullname"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $email    = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm  = $_POST["confirm_password"] ?? "";

    /* ---------- Backend validation (never trust frontend alone) ---------- */
    if (strlen($fullname) < 3) {
        $errors[] = "Full name must be at least 3 characters.";
    }
    if (!preg_match("/^[A-Za-z0-9_]{4,20}$/", $username)) {
        $errors[] = "Username: 4–20 characters, letters/numbers/underscore only.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address.";
    }
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters.";
    }
    if ($password !== $confirm) {
        $errors[] = "Password and confirm password do not match.";
    }

    /* ---------- Duplicate email / username check ---------- */
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1");
        $stmt->bind_param("ss", $email, $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            // Find out WHICH one is duplicate for a precise message
            $stmt->close();
            $s2 = $conn->prepare("SELECT email, username FROM users WHERE email = ? OR username = ? LIMIT 1");
            $s2->bind_param("ss", $email, $username);
            $s2->execute();
            $row = $s2->get_result()->fetch_assoc();
            $s2->close();
            if ($row && strcasecmp($row["email"], $email) === 0) {
                $errors[] = "This email is already registered. Try logging in.";
            } else {
                $errors[] = "This username is already taken. Please choose another.";
            }
        } else {
            $stmt->close();
        }
    }

    /* ---------- Hash password & insert with prepared statement ---------- */
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT); // bcrypt, salted automatically
        $ins = $conn->prepare("INSERT INTO users (fullname, username, email, password) VALUES (?, ?, ?, ?)");
        $ins->bind_param("ssss", $fullname, $username, $email, $hash);
        if ($ins->execute()) {
            $success = "Registration successful! Welcome, " . htmlspecialchars($fullname) . ". Your password is securely hashed.";
            $fullname = $username = $email = ""; // clear form
        } else {
            // Handle race-condition duplicate (unique key) gracefully
            if ($conn->errno === 1062) {
                $errors[] = "Email or username already exists (duplicate entry).";
            } else {
                $errors[] = "Database error: " . htmlspecialchars($conn->error);
            }
        }
        $ins->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Secure Registration | Practical 9</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="card">
    <div class="card-header">
        <div class="logo">🔐</div>
        <h1>Create Account</h1>
        <p>Secure registration with hashing &amp; duplicate check</p>
    </div>

    <div class="card-body">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <strong>❌ Registration failed:</strong>
                <ul>
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">✅ <?= $success ?></div>
        <?php endif; ?>

        <form id="regForm" method="POST" action="" novalidate>
            <label for="fullname">Full Name</label>
            <input type="text" id="fullname" name="fullname" data-rule="fullname" data-err="e-fullname"
                   placeholder="e.g. Aarav Sharma" value="<?= htmlspecialchars($fullname) ?>" required>
            <span class="err" id="e-fullname"></span>

            <label for="username">Username</label>
            <input type="text" id="username" name="username" data-rule="username" data-err="e-username"
                   placeholder="4–20 chars, letters/numbers/_" value="<?= htmlspecialchars($username) ?>" required>
            <span class="err" id="e-username"></span>

            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" data-rule="email" data-err="e-email"
                   placeholder="you@example.com" value="<?= htmlspecialchars($email) ?>" required>
            <span class="err" id="e-email"></span>

            <label for="password">Password</label>
            <div class="pass-wrap">
                <input type="password" id="password" name="password" data-rule="password" data-err="e-password"
                       placeholder="Minimum 8 characters" required>
                <button type="button" class="toggle" onclick="togglePass('password', this)">Show</button>
            </div>
            <div class="meter"><div id="meterBar"></div></div>
            <span class="err" id="e-password"></span>

            <label for="confirm_password">Confirm Password</label>
            <div class="pass-wrap">
                <input type="password" id="confirm_password" name="confirm_password" data-rule="confirm" data-err="e-confirm"
                       placeholder="Re-enter password" required>
                <button type="button" class="toggle" onclick="togglePass('confirm_password', this)">Show</button>
            </div>
            <span class="err" id="e-confirm"></span>

            <button type="submit" class="btn">Register Securely 🚀</button>
        </form>

        <div class="footer">
            Already registered? <a href="login.php">Login here</a>
        </div>
    </div>
</div>

<script src="script.js"></script>
</body>
</html>
