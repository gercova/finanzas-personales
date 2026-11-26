<?php
/**
 * php_action/adminRunMigrations.php
 * Executes one specific migration or all pending database migrations (admin only).
 */
require_once __DIR__ . '/admin_guard.php';
require_once __DIR__ . '/MigrationRunner.php';

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

$userId = (int)$_SESSION['userId'];
$action = trim((string)($input['action'] ?? 'run_all'));
$runner = new MigrationRunner($connect);

if ($action === 'run_one') {
    $key   = trim((string)($input['migration_key'] ?? ''));
    $force = !empty($input['force']) && $input['force'] !== '0' && $input['force'] !== 'false';
    if ($key === '') {
        echo json_encode(['success' => false, 'messages' => 'Debe especificar migration_key.']);
        exit();
    }
    $res = $runner->runMigrationByKey($key, $userId, $force);
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    exit();
}

$res = $runner->runAllPending($userId);
echo json_encode($res, JSON_UNESCAPED_UNICODE);
