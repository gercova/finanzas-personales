<?php
require_once 'core.php';
header('Content-Type: application/json; charset=utf-8');

if (!verifyCsrfToken()) {
    echo json_encode(['success' => false, 'messages' => 'Error de validación CSRF. Recargue la página.']);
    exit;
}

$userId       = (int)$_SESSION['userId'];
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

if (empty($categoryName)) {
    echo json_encode(['success' => false, 'messages' => 'El nombre de la categoría es obligatorio.']);
    exit;
}

$stmt = $connect->prepare("
    INSERT INTO financial_categories (category_name, category_type, color, created_by, status)
    VALUES (?, ?, ?, ?, ?)
");
if (!$stmt) {
    echo json_encode(['success' => false, 'messages' => 'Error preparando consulta.']);
    exit;
}
$stmt->bind_param("sssii", $categoryName, $categoryType, $color, $userId, $status);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'messages' => 'Categoría registrada exitosamente.']);
} else {
    echo json_encode(['success' => false, 'messages' => 'Error al guardar la categoría.']);
}
$stmt->close();
