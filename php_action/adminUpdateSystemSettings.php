<?php
/**
 * php_action/adminUpdateSystemSettings.php
 * Updates general system settings stored in system_settings (admin only).
 */
require_once __DIR__ . '/admin_guard.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'messages' => 'Método no permitido.']);
    exit();
}

$input = getRequestData();
$token = $input['csrf_token'] ?? null;
if (!verifyCsrfToken($token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'messages' => 'Error de validación CSRF.']);
    exit();
}

$resChk = $connect->query("SHOW TABLES LIKE 'system_settings'");
$hasTable = ($resChk && $resChk->num_rows > 0);
if ($resChk) $resChk->free();

if (!$hasTable) {
    echo json_encode([
        'success'  => false,
        'messages' => 'La tabla system_settings aún no existe. Ejecute primero la migración 001 desde el panel de migraciones.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$appName       = trim((string)($input['app_name'] ?? 'myFin'));
$currency      = trim((string)($input['currency_symbol'] ?? 'S/'));
$monthlyLimit  = max(1, (float)($input['default_monthly_limit'] ?? 4000));
$sessionMins   = max(5, min(1440, (int)($input['session_lifetime_minutes'] ?? 30)));
$allowReg      = !empty($input['allow_user_registration']) && $input['allow_user_registration'] !== '0' ? '1' : '0';
$maintMode     = !empty($input['maintenance_mode']) && $input['maintenance_mode'] !== '0' ? '1' : '0';

if ($appName === '') {
    echo json_encode(['success' => false, 'messages' => 'El nombre de la aplicación es obligatorio.']);
    exit();
}
if ($currency === '') {
    $currency = 'S/';
}

$userId = (int)$_SESSION['userId'];
$pairs = [
    ['app_name',                 $appName,                         'general',  'Nombre visible de la aplicación'],
    ['currency_symbol',          $currency,                        'finance',  'Símbolo de moneda predeterminado'],
    ['default_monthly_limit',    number_format($monthlyLimit, 2, '.', ''), 'finance', 'Cuota de gasto mensual predeterminada'],
    ['session_lifetime_minutes', (string)$sessionMins,             'security', 'Tiempo máximo de inactividad de sesión en minutos'],
    ['allow_user_registration',  $allowReg,                        'security', 'Permitir auto-registro de nuevos usuarios (1=Sí, 0=No)'],
    ['maintenance_mode',         $maintMode,                       'system',   'Modo mantenimiento restringido para usuarios estándar (1=Sí, 0=No)'],
];

$stmt = $connect->prepare("
    INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_group`, `description`, `updated_by`)
    VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        `setting_value` = VALUES(`setting_value`),
        `updated_by`    = VALUES(`updated_by`),
        `updated_at`    = NOW()
");
if (!$stmt) {
    echo json_encode(['success' => false, 'messages' => 'Error preparando actualización de parámetros.']);
    exit();
}

foreach ($pairs as [$k, $v, $grp, $desc]) {
    $stmt->bind_param("ssssi", $k, $v, $grp, $desc, $userId);
    $stmt->execute();
}
$stmt->close();

echo json_encode([
    'success'  => true,
    'messages' => 'Configuración general del sistema actualizada correctamente.',
    'app_name' => $appName,
], JSON_UNESCAPED_UNICODE);
