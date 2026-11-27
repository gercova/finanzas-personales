<?php
require_once __DIR__ . '/core.php';
header('Content-Type: application/json; charset=utf-8');

$valid = ['success' => false, 'messages' => ''];

if (!verifyCsrfToken()) {
    http_response_code(403);
    $valid['messages'] = 'Error de validación de seguridad (CSRF inválido). Recargue la página.';
    echo json_encode($valid, JSON_UNESCAPED_UNICODE);
    exit();
}

$userId      = (int)$_SESSION['userId'];
$name        = isset($_POST['project_name']) ? cleanInput($_POST['project_name']) : '';
$client      = isset($_POST['client_name'])  ? cleanInput($_POST['client_name'])  : '';
$budget      = isset($_POST['budget'])       ? (float)$_POST['budget']            : 0;
$startDate   = isset($_POST['start_date'])   ? trim($_POST['start_date'])         : '';
$endDate     = isset($_POST['end_date'])     ? trim($_POST['end_date'])           : '';
$status      = isset($_POST['status'])       ? cleanInput($_POST['status'])       : 'ACTIVE';
$description = isset($_POST['description'])  ? cleanInput($_POST['description'])  : '';

$allowedStatuses = ['ACTIVE', 'ON_HOLD', 'COMPLETED'];

if (empty($name) || $budget <= 0) {
    $valid['messages'] = 'El nombre del proyecto y un presupuesto mayor a 0 son requeridos.';
    echo json_encode($valid, JSON_UNESCAPED_UNICODE);
    exit();
}
if (!in_array($status, $allowedStatuses, true)) {
    $valid['messages'] = 'Estado de proyecto no válido.';
    echo json_encode($valid, JSON_UNESCAPED_UNICODE);
    exit();
}

$startDateVal = $startDate !== '' ? $startDate : null;
$endDateVal   = $endDate !== ''   ? $endDate   : null;

if ($startDateVal !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDateVal)) {
    $valid['messages'] = 'Formato de fecha de inicio inválido (use YYYY-MM-DD).';
    echo json_encode($valid, JSON_UNESCAPED_UNICODE);
    exit();
}
if ($endDateVal !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDateVal)) {
    $valid['messages'] = 'Formato de fecha de fin inválido (use YYYY-MM-DD).';
    echo json_encode($valid, JSON_UNESCAPED_UNICODE);
    exit();
}
if ($startDateVal !== null && $endDateVal !== null && $endDateVal < $startDateVal) {
    $valid['messages'] = 'La fecha de fin no puede ser anterior a la fecha de inicio.';
    echo json_encode($valid, JSON_UNESCAPED_UNICODE);
    exit();
}

try {
    $stmt = $connect->prepare("INSERT INTO projects (project_name, client_name, budget, start_date, end_date, status, description, created_by) VALUES (?,?,?,?,?,?,?,?)");
    if ($stmt) {
        $stmt->bind_param("ssdssssi", $name, $client, $budget, $startDateVal, $endDateVal, $status, $description, $userId);
        if ($stmt->execute()) {
            $valid['success']    = true;
            $valid['messages']   = 'Proyecto creado exitosamente.';
            $valid['project_id'] = $stmt->insert_id;
        } else {
            $valid['messages'] = 'Error al guardar el proyecto.';
        }
        $stmt->close();
    }
} catch (\Throwable $e) {
    $valid['messages'] = 'Error de base de datos: ' . $e->getMessage();
}

echo json_encode($valid, JSON_UNESCAPED_UNICODE);
