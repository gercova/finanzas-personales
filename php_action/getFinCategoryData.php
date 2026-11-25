<?php
require_once 'core.php';
header('Content-Type: application/json; charset=utf-8');

$id     = (int)($_GET['id'] ?? 0);
$userId = (int)$_SESSION['userId'];

if ($id <= 0) {
    echo json_encode(['success' => false, 'messages' => 'ID inválido.']);
    exit;
}

$stmt = $connect->prepare("
    SELECT category_id AS id, category_name, category_type, color, status
    FROM financial_categories
    WHERE category_id = ? AND created_by = ? AND is_deleted = 0
");
if (!$stmt) {
    echo json_encode(['success' => false, 'messages' => 'Error interno.']);
    exit;
}
$stmt->bind_param("ii", $id, $userId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(['success' => false, 'messages' => 'Categoría no encontrada.']);
    exit;
}
echo json_encode(['success' => true, 'data' => $row]);
