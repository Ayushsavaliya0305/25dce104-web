<?php
// Practical 8: StudentHub — PDO database connection (db.php)
// Include with: require_once __DIR__ . '/db.php';  then use $pdo.

declare(strict_types=1);

// ---- Edit these for your MySQL setup (XAMPP defaults shown) ----
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'studenthub');
define('DB_USER', 'root');
define('DB_PASS', '');          // XAMPP default is empty; use your password on lab PC
define('DB_CHARSET', 'utf8mb4');

try {
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_NAME,
        DB_CHARSET
    );

    $options = [
        // Throw exceptions on SQL errors (needed for try/catch + viva Q&A)
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        // Return rows as associative arrays by default
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Use real prepared statements (not emulated) — safer + faster
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Never print $e->getMessage() on a live site (leaks credentials);
    // it is shown here only for lab debugging.
    http_response_code(500);
    exit('Database connection failed. Check db.php credentials: ' .
        htmlspecialchars($e->getMessage()));
}
