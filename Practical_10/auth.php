<?php
// auth.php - Session helpers: login guard, role guard, timeout, remember-me
require_once __DIR__ . '/config.php';
secure_session_start();

// ---- Token storage helpers (file-based, replace with DB in production) ----
function load_tokens() {
    if (!file_exists(TOKEN_FILE)) return [];
    $data = json_decode(file_get_contents(TOKEN_FILE), true);
    return is_array($data) ? $data : [];
}
function save_tokens($tokens) {
    file_put_contents(TOKEN_FILE, json_encode($tokens, JSON_PRETTY_PRINT), LOCK_EX);
}

// ---- Remember-Me: create secure selector:validator token ----
function create_remember_token($username) {
    $selector  = bin2hex(random_bytes(16));   // public lookup key
    $validator = bin2hex(random_bytes(32));   // secret, only hash stored
    $tokens = load_tokens();
    // Remove old tokens of this user (one active token per user)
    foreach ($tokens as $sel => $t) {
        if ($t['username'] === $username) unset($tokens[$sel]);
    }
    $tokens[$selector] = [
        'username'       => $username,
        'validator_hash' => hash('sha256', $validator),
        'expiry'         => time() + REMEMBER_EXPIRY
    ];
    save_tokens($tokens);
    // HttpOnly cookie, SameSite=Lax. Secure flag auto-added on HTTPS.
    setcookie('remember_me', $selector . ':' . $validator, [
        'expires'  => time() + REMEMBER_EXPIRY,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

function clear_remember_token() {
    if (!empty($_COOKIE['remember_me'])) {
        [$selector] = explode(':', $_COOKIE['remember_me'] . ':');
        $tokens = load_tokens();
        if (isset($tokens[$selector])) {
            unset($tokens[$selector]);
            save_tokens($tokens);
        }
        setcookie('remember_me', '', time() - 3600, '/');
    }
}

// Auto-login from valid remember-me cookie. Returns true if restored.
function try_remember_login() {
    global $users;
    if (isset($_SESSION['username'])) return true;      // already logged in
    if (empty($_COOKIE['remember_me'])) return false;
    $parts = explode(':', $_COOKIE['remember_me']);
    if (count($parts) !== 2) return false;
    [$selector, $validator] = $parts;

    $tokens = load_tokens();
    if (!isset($tokens[$selector])) return false;
    $rec = $tokens[$selector];
    // Expiry check
    if ($rec['expiry'] < time()) {
        unset($tokens[$selector]); save_tokens($tokens);
        setcookie('remember_me', '', time() - 3600, '/');
        return false;
    }
    // Constant-time compare of validator hash
    if (!hash_equals($rec['validator_hash'], hash('sha256', $validator))) return false;
    $username = $rec['username'];
    if (!isset($users[$username])) return false;

    // Restore session securely
    session_regenerate_id(true);
    $_SESSION['username']      = $username;
    $_SESSION['role']          = $users[$username]['role'];
    $_SESSION['login_time']    = time();
    $_SESSION['last_activity'] = time();
    return true;
}

// ---- Access guards ----
function require_login() {
    // 1. Try remember-me auto-login first
    try_remember_login();
    // 2. Not logged in -> go to login
    if (!isset($_SESSION['username'])) {
        header('Location: login.php');
        exit;
    }
    // 3. Session timeout check
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        // Timeout: wipe session + remember token, redirect with message
        clear_remember_token();
        session_unset();
        session_destroy();
        header('Location: login.php?timeout=1');
        exit;
    }
    // 4. Refresh activity timestamp
    $_SESSION['last_activity'] = time();
}

function require_role($role) {
    require_login(); // ensures logged in + not timed out
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        http_response_code(403);
        die('<h2>403 Forbidden</h2><p>You do not have permission to view this page.</p><a href="dashboard.php">Back to Dashboard</a>');
    }
}

// Role-based landing page
function redirect_by_role() {
    if (!isset($_SESSION['role'])) { header('Location: login.php'); exit; }
    if ($_SESSION['role'] === 'admin') { header('Location: admin.php'); exit; }
    header('Location: dashboard.php'); exit;
}
