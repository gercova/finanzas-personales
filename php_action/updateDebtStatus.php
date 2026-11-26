<?php
require_once __DIR__ . '/core.php';
require_once __DIR__ . '/security.php';
header('Content-Type: application/json; charset=utf-8');

if (!verifyCsrfToken()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'messages' => 'Error de validación de seguridad (CSRF inválido).']);
    exit;
}

$userId    = (int)$_SESSION['userId'];
$debtId    = (int)($_POST['debt_id'] ?? 0);
$newStatus = trim((string)($_POST['status'] ?? ''));

$sets = callProcedureMulti($connect, "CALL sp_update_debt_status(?, ?, ?)", "iis", [$userId, $debtId, $newStatus]);
if (!empty($sets[0][0])) {
    $res = $sets[0][0];
    echo json_encode([
        'success'  => (int)$res['success'] === 1,
        'messages' => $res['messages'],
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['success' => false, 'messages' => 'Error al actualizar el estado.']);
}
