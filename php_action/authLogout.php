<?php
/**
 * php_action/authLogout.php
 * Pure JSON logout endpoint: destroys current session and returns a fresh guest CSRF token.
 */
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/security.php';

initSecureSession();
header('Content-Type: application/json; charset=utf-8');

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}
@session_destroy();

session_start();
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

echo json_encode([
    'success'       => true,
    'authenticated' => false,
    'csrf_token'    => $_SESSION['csrf_token'],
], JSON_UNESCAPED_UNICODE);
