<?php
/**
 * php_action/getBorrowerData.php
 * Returns single borrower details by ID.
 */
require_once 'core.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['userId'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'messages' => 'No autorizado.']);
    exit;
}

$userId = (int)$_SESSION['userId'];
$id     = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'messages' => 'ID de prestatario inválido.']);
    exit;
}

$stmt = $connect->prepare("
    SELECT borrower_id AS id, document_type, document_number, full_name,
           phone, email, address, notes, status
    FROM borrowers
    WHERE borrower_id = ? AND created_by = ? AND is_deleted = 0
");
if (!$stmt) {
    echo json_encode(['success' => false, 'messages' => 'Error de consulta.']);
    exit;
}

$stmt->bind_param("ii", $id, $userId);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($data) {
    echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['success' => false, 'messages' => 'Prestatario no encontrado.']);
}
