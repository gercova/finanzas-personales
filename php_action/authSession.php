<?php
/**
 * php_action/authSession.php
 * Returns current authentication status, CSRF token, user info, and header metrics as pure JSON.
 */
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/security.php';

initSecureSession();
header('Content-Type: application/json; charset=utf-8');

$authenticated = isset($_SESSION['userId']) && !empty($_SESSION['userId']);
$csrfToken     = getCsrfToken();
$reason        = $GLOBALS['__sessionInvalidReason'] ?? null;

$user          = null;
$headerMetrics = ['month_income' => 0.0, 'today_expenses' => 0.0];

if ($authenticated) {
    $userId = (int)$_SESSION['userId'];
    $user = [
        'id'        => $userId,
        'username'  => $_SESSION['username'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? ($_SESSION['username'] ?? ''),
        'role'      => $_SESSION['role'] ?? 'USER',
    ];
    $headerMetrics = fetchHeaderMetrics($connect, $userId);
}

echo json_encode([
    'success'        => true,
    'authenticated'  => $authenticated,
    'reason'         => $reason,
    'app_name'       => env('APP_NAME', 'myFin'),
    'csrf_token'     => $csrfToken,
    'user'           => $user,
    'header_metrics' => $headerMetrics,
], JSON_UNESCAPED_UNICODE);
