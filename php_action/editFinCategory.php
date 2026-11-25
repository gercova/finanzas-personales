<?php
require_once 'core.php';
header('Content-Type: application/json; charset=utf-8');

if (!verifyCsrfToken()) {
    echo json_encode(['success' => false, 'messages' => 'Error de validación CSRF. Recargue la página.']);
    exit;
}

$userId       = (int)$_SESSION['userId'];
$categoryId   = (int)($_POST['category_id'] ?? 0);
$categoryName = cleanInput($_POST['category_name'] ?? '');
$categoryType = strtoupper(trim($_POST['category_type'] ?? 'EXPENSE'));
$color        = trim($_POST['color'] ?? '#5bc0de');
$status       = isset($_POST['status']) ? (int)$_POST['status'] : 1;

if (!in_array($categoryType, ['INCOME', 'EXPENSE'], true)) {
    $categoryType = 'EXPENSE';
}
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
    $color = '#5bc0de';
}
$status = ($status === 0) ? 0 : 1;

if ($categoryId <= 0 || empty($categoryName)) {
    echo json_encode(['success' => false, 'messages' => 'Datos incompletos.']);
    exit;
}

if (!verifyOwnedEntity($connect, 'financial_categories', 'category_id', $categoryId, $userId)) {
    echo json_encode(['success' => false, 'messages' => 'Categoría no encontrada o sin permiso.']);
    exit;
}

$stmt = $connect->prepare("
    UPDATE financial_categories
    SET category_name = ?, category_type = ?, color = ?, status = ?
    WHERE category_id = ? AND created_by = ? AND is_deleted = 0
");
if (!$stmt) {
    echo json_encode(['success' => false, 'messages' => 'Error preparando consulta.']);
    exit;
}
$stmt->bind_param("sssiii", $categoryName, $categoryType, $color, $status, $categoryId, $userId);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'messages' => 'Categoría actualizada exitosamente.']);
} else {
    echo json_encode(['success' => false, 'messages' => 'Error al actualizar la categoría.']);
}
$stmt->close();
