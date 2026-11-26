<?php
require_once __DIR__ . '/core.php';
header('Content-Type: application/json; charset=utf-8');

$userId = (int)$_SESSION['userId'];
$id     = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'messages' => 'ID de acreedor inválido.']);
    exit;
}

$stmt = $connect->prepare("
    SELECT lender_id AS id, lender_type, document_type, document_number, full_name,
           phone, email, address, notes, status
    FROM lenders
    WHERE lender_id = ? AND created_by = ? AND is_deleted = 0
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
    echo json_encode(['success' => false, 'messages' => 'Acreedor / entidad no encontrada.']);
}
