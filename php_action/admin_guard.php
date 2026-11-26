<?php
/**
 * php_action/admin_guard.php
 * Strict authentication + administrator authorization guard for system configuration endpoints.
 */
require_once __DIR__ . '/core.php';

function isAdminSession(): bool
{
    $role     = strtoupper((string)($_SESSION['role'] ?? 'USER'));
    $username = strtolower((string)($_SESSION['username'] ?? ''));
    return $role === 'ADMIN' || $username === 'admin';
}

if (!isAdminSession()) {
    if (!headers_sent()) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode([
        'success'  => false,
        'messages' => 'Acceso denegado. Este módulo es exclusivo para el usuario administrador.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}
