<?php
require_once 'core.php';
require_once 'security.php';
header('Content-Type: application/json; charset=utf-8');

if (!verifyCsrfToken()) {
    echo json_encode(['success' => false, 'messages' => 'Token CSRF inválido. Recargue la página.']);
    exit;
}

$userId             = (int)$_SESSION['userId'];
$month_year         = cleanInput($_POST['month_year'] ?? date('Y-m'));
$monthly_limit      = (float)($_POST['monthly_limit'] ?? 0);
$warning_threshold  = (float)($_POST['warning_threshold'] ?? 75);
$critical_threshold = (float)($_POST['critical_threshold'] ?? 90);

if (empty($month_year) || !preg_match('/^\d{4}-\d{2}$/', $month_year) || $monthly_limit <= 0) {
    echo json_encode(['success' => false, 'messages' => 'Por favor ingrese un mes/año válido (YYYY-MM) y un monto límite mayor a cero.']);
    exit;
}

$stmt = $connect->prepare("CALL sp_upsert_spending_limit(?, ?, ?, ?, ?)");
if (!$stmt) {
    echo json_encode(['success' => false, 'messages' => 'Error preparando consulta: ' . $connect->error]);
    exit;
}
$stmt->bind_param("isddd", $userId, $month_year, $monthly_limit, $warning_threshold, $critical_threshold);

if ($stmt->execute()) {
    // Clear results so connection can be reused
    while ($connect->more_results()) { $connect->next_result(); }
    echo json_encode(['success' => true, 'messages' => 'Límite de gasto mensual actualizado exitosamente.']);
} else {
    echo json_encode(['success' => false, 'messages' => 'Error al guardar el límite: ' . $stmt->error]);
}
$stmt->close();
