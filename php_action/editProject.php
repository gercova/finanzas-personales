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
$id          = isset($_POST['edit_project_id'])   ? (int)$_POST['edit_project_id']           : 0;
$name        = isset($_POST['edit_project_name']) ? cleanInput($_POST['edit_project_name'])   : '';
$client      = isset($_POST['edit_client_name'])  ? cleanInput($_POST['edit_client_name'])    : '';
$budget      = isset($_POST['edit_budget'])       ? (float)$_POST['edit_budget']              : 0;
$startDate   = isset($_POST['edit_start_date'])   ? trim($_POST['edit_start_date'])           : '';
$endDate     = isset($_POST['edit_end_date'])     ? trim($_POST['edit_end_date'])             : '';
$status      = isset($_POST['edit_status'])       ? cleanInput($_POST['edit_status'])         : 'ACTIVE';
$description = isset($_POST['edit_description'])  ? cleanInput($_POST['edit_description'])   : '';

$allowedStatuses = ['ACTIVE', 'ON_HOLD', 'COMPLETED'];

if ($id < 1 || empty($name) || $budget <= 0) {
    $valid['messages'] = 'El nombre del proyecto y un presupuesto mayor a 0 son requeridos.';
    echo json_encode($valid, JSON_UNESCAPED_UNICODE);
    exit();
}
if (!in_array($status, $allowedStatuses, true)) {
    $valid['messages'] = 'Estado de proyecto no válido.';
    echo json_encode($valid, JSON_UNESCAPED_UNICODE);
    exit();
}
if (!verifyOwnedEntity($connect, 'projects', 'project_id', $id, $userId)) {
    $valid['messages'] = 'Proyecto no encontrado o sin permiso.';
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
    $stmt = $connect->prepare("UPDATE projects SET project_name=?, client_name=?, budget=?, start_date=?, end_date=?, status=?, description=? WHERE project_id=? AND created_by=? AND is_deleted=0");
    if ($stmt) {
        $stmt->bind_param("ssdssssii", $name, $client, $budget, $startDateVal, $endDateVal, $status, $description, $id, $userId);
        if ($stmt->execute()) {
            $valid['success']  = true;
            $valid['messages'] = 'Proyecto actualizado exitosamente.';
        } else {
            $valid['messages'] = 'Error al actualizar el proyecto.';
        }
        $stmt->close();
    }
} catch (\Throwable $e) {
    $valid['messages'] = 'Error de base de datos: ' . $e->getMessage();
}

echo json_encode($valid, JSON_UNESCAPED_UNICODE);
