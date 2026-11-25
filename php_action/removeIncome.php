<?php
require_once 'core.php';
require_once 'security.php';
header('Content-Type: application/json');

verifyCsrfToken();

$income_id = (int)($_POST['income_id'] ?? 0);
$userId    = (int)$_SESSION['userId'];

if ($income_id <= 0) {
    echo json_encode(['success' => false, 'messages' => 'ID inválido.']);
    exit;
}

if (!verifyOwnedEntity($connect, 'incomes', 'income_id', $income_id, $userId)) {
    echo json_encode(['success'=>false,'messages'=>'Registro no encontrado o ya fue eliminado.']);
    exit;
}

$stmt = $connect->prepare("UPDATE incomes SET is_deleted = 1, deleted_at = NOW() WHERE income_id=? AND created_by=?");
if (!$stmt) { echo json_encode(['success'=>false,'messages'=>'Error preparando consulta.']); exit; }
$stmt->bind_param("ii", $income_id, $userId);

// Balance reversal is handled automatically by trg_incomes_after_update
if ($stmt->execute()) {
    echo json_encode(['success'=>true,'messages'=>'Ingreso eliminado exitosamente (borrado lógico).']);
} else {
    echo json_encode(['success'=>false,'messages'=>'Error al eliminar.']);
}
$stmt->close();
