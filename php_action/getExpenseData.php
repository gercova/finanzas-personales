<?php
require_once 'core.php';
header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
$userId = (int)$_SESSION['userId'];

if ($id <= 0) { echo json_encode(['success'=>false,'messages'=>'ID inválido.']); exit; }

$stmt = $connect->prepare(
    "SELECT id, concept, account_id, category_id, project_id, expense_date, amount, status, receipt_image, notes
     FROM expenses WHERE id=? AND created_by=?"
);
if (!$stmt) { echo json_encode(['success'=>false,'messages'=>'Error interno.']); exit; }
$stmt->bind_param("ii", $id, $userId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) { echo json_encode(['success'=>false,'messages'=>'Registro no encontrado.']); exit; }
echo json_encode(['success'=>true,'data'=>$row]);
