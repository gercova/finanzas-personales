<?php
/**
 * php_action/adminIntegrateMigration.php
 * Integrates a new SQL migration (from editor or uploaded .sql file) into database/migrations/
 * and optionally executes it immediately (admin only).
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

$title       = trim((string)($input['title'] ?? ''));
$description = trim((string)($input['description'] ?? ''));
$sql         = (string)($input['sql'] ?? '');
$executeNow  = !isset($input['execute_now']) || ($input['execute_now'] !== '0' && $input['execute_now'] !== 'false' && $input['execute_now'] !== false);

// Optional uploaded .sql file overrides/supplies SQL text
if (!empty($_FILES['sql_file']['tmp_name']) && is_uploaded_file($_FILES['sql_file']['tmp_name'])) {
    $origName = (string)($_FILES['sql_file']['name'] ?? '');
    if (strtolower(pathinfo($origName, PATHINFO_EXTENSION)) !== 'sql') {
        echo json_encode(['success' => false, 'messages' => 'Solo se permiten archivos con extensión .sql.']);
        exit();
    }
    if ((int)($_FILES['sql_file']['size'] ?? 0) > 2 * 1024 * 1024) {
        echo json_encode(['success' => false, 'messages' => 'El archivo SQL supera el límite de 2 MB.']);
        exit();
    }
    $sql = (string)file_get_contents($_FILES['sql_file']['tmp_name']);
    if ($title === '') {
        $title = pathinfo($origName, PATHINFO_FILENAME);
    }
}

$userId = (int)$_SESSION['userId'];
$runner = new MigrationRunner($connect);
$res    = $runner->integrateMigration($title, $description, $sql, $userId, $executeNow);

echo json_encode($res, JSON_UNESCAPED_UNICODE);
