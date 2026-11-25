<?php
require_once 'core.php';
header('Content-Type: application/json; charset=utf-8');

if (!verifyCsrfToken()) {
    echo json_encode(['success' => false, 'messages' => 'Error de validación CSRF. Recargue la página.']);
    exit;
}

$userId    = (int)$_SESSION['userId'];
$accountId = (int)($_POST['account_id'] ?? 0);

if ($accountId <= 0 || !verifyOwnedEntity($connect, 'accounts', 'account_id', $accountId, $userId)) {
    echo json_encode(['success' => false, 'messages' => 'Cuenta no encontrada o sin permiso.']);
    exit;
}

$stmt = $connect->prepare("
    UPDATE accounts SET is_deleted = 1, deleted_at = NOW(), status = 0
    WHERE account_id = ? AND created_by = ? AND is_deleted = 0
");
if (!$stmt) {
    echo json_encode(['success' => false, 'messages' => 'Error preparando consulta.']);
    exit;
}
$stmt->bind_param("ii", $accountId, $userId);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'messages' => 'Cuenta eliminada exitosamente (borrado lógico).']);
} else {
    echo json_encode(['success' => false, 'messages' => 'Error al eliminar la cuenta.']);
}
$stmt->close();
