<?php
require_once 'core.php';
require_once 'security.php';
header('Content-Type: application/json');

verifyCsrfToken();

$month_year         = cleanInput($_POST['month_year'] ?? date('Y-m'));
$monthly_limit      = (float)($_POST['monthly_limit'] ?? 0);
$warning_threshold  = (float)($_POST['warning_threshold'] ?? 75);
$critical_threshold = (float)($_POST['critical_threshold'] ?? 90);

if (empty($month_year) || !preg_match('/^\d{4}-\d{2}$/', $month_year) || $monthly_limit <= 0) {
    echo json_encode(['success' => false, 'messages' => 'Por favor ingrese un mes/año válido (YYYY-MM) y un monto límite mayor a cero.']);
    exit;
}

$stmt = $connect->prepare(
    "INSERT INTO spending_limits (month_year, monthly_limit, warning_threshold, critical_threshold)
     VALUES (?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE monthly_limit = VALUES(monthly_limit),
                             warning_threshold = VALUES(warning_threshold),
                             critical_threshold = VALUES(critical_threshold)"
);
if (!$stmt) {
    echo json_encode(['success' => false, 'messages' => 'Error preparando consulta: ' . $connect->error]);
    exit;
}
$stmt->bind_param("sddd", $month_year, $monthly_limit, $warning_threshold, $critical_threshold);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'messages' => 'Límite de gasto mensual actualizado exitosamente.']);
} else {
    echo json_encode(['success' => false, 'messages' => 'Error al guardar el límite: ' . $stmt->error]);
}
$stmt->close();
