<?php
// config.php - Central configuration: users, timeouts, secure session start

define('SESSION_TIMEOUT', 900);          // 15 minutes = 15 * 60
define('REMEMBER_EXPIRY', 30*24*60*60);  // 30 days
define('TOKEN_FILE', __DIR__ . '/tokens.json');

// Demo users. In real app, fetch from database.
// password_hash() is called here so the hash always matches.
// Demo credentials:
//   admin / admin123  (role: admin)
//   user  / user123   (role: user)
$users = [
    'admin' => [
        'hash' => password_hash('admin123', PASSWORD_DEFAULT),
        'role' => 'admin'
    ],
    'user' => [
        'hash' => password_hash('user123', PASSWORD_DEFAULT),
        'role' => 'user'
    ]
];

/**
 * Start session with secure cookie params. Call once per page via auth.php.
 */
function secure_session_start() {
    if (session_status() === PHP_SESSION_NONE) {
        // Must be set BEFORE session_start()
        session_set_cookie_params([
            'lifetime' => 0,              // session cookie (dies on browser close)
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']), // true on HTTPS only
            'httponly' => true,           // JS cannot read session cookie (XSS protection)
            'samesite' => 'Lax'           // CSRF protection
        ]);
        session_start();
    }
}
