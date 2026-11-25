<?php
require_once 'core.php';
require_once 'security.php';
header('Content-Type: application/json');

verifyCsrfToken();

$expense_id = (int)($_POST['expense_id'] ?? 0);
$userId     = (int)$_SESSION['userId'];

if ($expense_id <= 0) {
    echo json_encode(['success' => false, 'messages' => 'ID inválido.']);
    exit;
}

if (!verifyOwnedEntity($connect, 'expenses', 'expense_id', $expense_id, $userId)) {
    echo json_encode(['success'=>false,'messages'=>'Registro no encontrado o ya fue eliminado.']);
    exit;
}

$stmt = $connect->prepare("UPDATE expenses SET is_deleted = 1, deleted_at = NOW() WHERE expense_id=? AND created_by=?");
if (!$stmt) { echo json_encode(['success'=>false,'messages'=>'Error preparando consulta.']); exit; }
$stmt->bind_param("ii", $expense_id, $userId);

// Balance reversal is handled automatically by trg_expenses_after_update
if ($stmt->execute()) {
    echo json_encode(['success'=>true,'messages'=>'Gasto eliminado exitosamente (borrado lógico).']);
} else {
    echo json_encode(['success'=>false,'messages'=>'Error al eliminar.']);
}
$stmt->close();
