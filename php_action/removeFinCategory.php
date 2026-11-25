<?php
require_once 'core.php';
header('Content-Type: application/json; charset=utf-8');

if (!verifyCsrfToken()) {
    echo json_encode(['success' => false, 'messages' => 'Error de validación CSRF. Recargue la página.']);
    exit;
}

$userId     = (int)$_SESSION['userId'];
$categoryId = (int)($_POST['category_id'] ?? 0);

if ($categoryId <= 0 || !verifyOwnedEntity($connect, 'financial_categories', 'category_id', $categoryId, $userId)) {
    echo json_encode(['success' => false, 'messages' => 'Categoría no encontrada o sin permiso.']);
    exit;
}

$stmt = $connect->prepare("
    UPDATE financial_categories SET is_deleted = 1, deleted_at = NOW(), status = 0
    WHERE category_id = ? AND created_by = ? AND is_deleted = 0
");
if (!$stmt) {
    echo json_encode(['success' => false, 'messages' => 'Error preparando consulta.']);
    exit;
}
$stmt->bind_param("ii", $categoryId, $userId);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'messages' => 'Categoría eliminada exitosamente (borrado lógico).']);
} else {
    echo json_encode(['success' => false, 'messages' => 'Error al eliminar la categoría.']);
}
$stmt->close();
