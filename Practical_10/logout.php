<?php
// logout.php - Secure logout: delete remember token, destroy session
require_once __DIR__ . '/auth.php';

clear_remember_token();   // remove selector from tokens.json + expire cookie
$_SESSION = [];           // clear session array
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'] ?? '',
        $params['secure'] ?? false, $params['httponly'] ?? true
    );
}
session_destroy();        // destroy server-side session data

header('Location: login.php?loggedout=1');
exit;
