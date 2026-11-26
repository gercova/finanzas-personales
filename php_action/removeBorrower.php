<?php
/**
 * php_action/removeBorrower.php
 * Soft deletes a borrower record.
 */
require_once 'core.php';
require_once 'security.php';
header('Content-Type: application/json; charset=utf-8');

verifyCsrfToken();

if (!isset($_SESSION['userId'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'messages' => 'No autorizado.']);
    exit;
}

$userId = (int)$_SESSION['userId'];
$id     = (int)($_POST['id'] ?? $_POST['borrower_id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'messages' => 'ID inválido.']);
    exit;
}

if (!verifyOwnedEntity($connect, 'borrowers', 'borrower_id', $id, $userId)) {
    echo json_encode(['success' => false, 'messages' => 'Registro no encontrado o sin permiso.']);
    exit;
}

// Check if has pending loans
$chk = $connect->prepare("SELECT COUNT(*) AS total FROM loans WHERE borrower_id = ? AND created_by = ? AND is_deleted = 0 AND status != 'PAGADO'");
if ($chk) {
    $chk->bind_param("ii", $id, $userId);
    $chk->execute();
    $row = $chk->get_result()->fetch_assoc();
    $chk->close();
    if (!empty($row['total']) && (int)$row['total'] > 0) {
        echo json_encode(['success' => false, 'messages' => 'No se puede eliminar el prestatario porque tiene préstamos pendientes o activos.']);
        exit;
    }
}

$stmt = $connect->prepare("UPDATE borrowers SET is_deleted = 1, deleted_at = NOW() WHERE borrower_id = ? AND created_by = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'messages' => 'Error interno al eliminar.']);
    exit;
}

$stmt->bind_param("ii", $id, $userId);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'messages' => 'Prestatario eliminado exitosamente.'], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['success' => false, 'messages' => 'Error al eliminar: ' . $connect->error]);
}
$stmt->close();
