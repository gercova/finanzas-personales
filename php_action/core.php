<?php
/**
 * php_action/core.php
 * Authentication & session guard for protected REST JSON endpoints.
 */
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/security.php';

initSecureSession();

if (!isset($_SESSION['userId']) || empty($_SESSION['userId'])) {
    $reason = $GLOBALS['__sessionInvalidReason'] ?? 'unauthenticated';
    $msg = $reason === 'timeout'
        ? 'Su sesión ha expirado por inactividad. Inicie sesión nuevamente.'
        : ($reason === 'hijack'
            ? 'Su sesión se cerró por motivos de seguridad.'
            : 'Sesión no iniciada o expirada. Inicie sesión nuevamente.');

    if (!headers_sent()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode([
        'success'       => false,
        'authenticated' => false,
        'code'          => $reason,
        'messages'      => $msg,
    ], JSON_UNESCAPED_UNICODE);
    exit();
}
