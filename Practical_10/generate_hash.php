<?php
// generate_hash.php - Utility to create password_hash() for new users
// Usage: php generate_hash.php  OR  open in browser: generate_hash.php?pwd=abc123
$pwd = $argv[1] ?? ($_GET['pwd'] ?? 'admin123');
echo "Password: " . htmlspecialchars($pwd) . "<br>\n";
echo "Hash: " . password_hash($pwd, PASSWORD_DEFAULT) . "<br>\n";
echo "Verify test: " . (password_verify($pwd, password_hash($pwd, PASSWORD_DEFAULT)) ? 'OK' : 'FAIL') . "<br>\n";
