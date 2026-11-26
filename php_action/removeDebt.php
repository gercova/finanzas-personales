<?php
require_once __DIR__ . '/core.php';
require_once __DIR__ . '/security.php';
header('Content-Type: application/json; charset=utf-8');

if (!verifyCsrfToken()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'messages' => 'Error de validación CSRF.']);
    exit;
}

$debt_id = (int)($_POST['debt_id'] ?? 0);
$userId  = (int)$_SESSION['userId'];

if ($debt_id <= 0) {
    echo json_encode(['success' => false, 'messages' => 'ID inválido.']);
    exit;
}

if (!verifyOwnedEntity($connect, 'debts', 'debt_id', $debt_id, $userId)) {
    echo json_encode(['success' => false, 'messages' => 'Préstamo recibido no encontrado o sin permiso.']);
    exit;
}

// Account balance reversal is handled automatically by trg_debts_after_update
$stmt = $connect->prepare("UPDATE debts SET is_deleted=1, deleted_at=NOW() WHERE debt_id=? AND created_by=?");
if (!$stmt) {
    echo json_encode(['success' => false, 'messages' => 'Error interno.']);
    exit;
}
$stmt->bind_param("ii", $debt_id, $userId);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'messages' => 'Préstamo recibido eliminado.'], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['success' => false, 'messages' => 'Error al eliminar.']);
}
$stmt->close();
