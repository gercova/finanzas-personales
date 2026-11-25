<?php
require_once 'core.php';
header('Content-Type: application/json');

$valid = ['success' => false, 'messages' => ''];

if (!verifyCsrfToken()) {
    $valid['messages'] = 'Error de validación de seguridad (CSRF inválido). Recargue la página.';
    echo json_encode($valid); exit();
}

$userId     = (int)$_SESSION['userId'];
$id         = isset($_POST['edit_project_id'])   ? (int)$_POST['edit_project_id']           : 0;
$name       = isset($_POST['edit_project_name']) ? cleanInput($_POST['edit_project_name'])   : '';
$client     = isset($_POST['edit_client_name'])  ? cleanInput($_POST['edit_client_name'])    : '';
$budget     = isset($_POST['edit_budget'])       ? (float)$_POST['edit_budget']              : 0;
$startDate  = isset($_POST['edit_start_date'])   ? trim($_POST['edit_start_date'])           : null;
$endDate    = isset($_POST['edit_end_date'])     ? trim($_POST['edit_end_date'])             : null;
$status     = isset($_POST['edit_status'])       ? cleanInput($_POST['edit_status'])         : 'ACTIVE';
$description= isset($_POST['edit_description'])  ? cleanInput($_POST['edit_description'])   : '';

$allowedStatuses = ['ACTIVE','ON_HOLD','COMPLETED'];

if ($id < 1 || empty($name) || $budget <= 0) {
    $valid['messages'] = 'El nombre del proyecto y el presupuesto son requeridos.';
    echo json_encode($valid); exit();
}
if (!in_array($status, $allowedStatuses)) {
    $valid['messages'] = 'Estado de proyecto no válido.';
    echo json_encode($valid); exit();
}

if (!verifyOwnedEntity($connect, 'projects', 'project_id', $id, $userId)) {
    $valid['messages'] = 'Proyecto no encontrado o sin permiso.';
    echo json_encode($valid); exit();
}

$startDate = !empty($startDate) ? $startDate : null;
$endDate   = !empty($endDate)   ? $endDate   : null;

$stmt = $connect->prepare("UPDATE projects SET project_name=?, client_name=?, budget=?, start_date=?, end_date=?, status=?, description=? WHERE project_id=? AND created_by=? AND is_deleted=0");
if ($stmt) {
    $stmt->bind_param("ssdssssii", $name, $client, $budget, $startDate, $endDate, $status, $description, $id, $userId);
    if ($stmt->execute()) {
        $valid['success']  = true;
        $valid['messages'] = 'Proyecto actualizado exitosamente.';
    } else {
        $valid['messages'] = 'Error al actualizar el proyecto.';
    }
    $stmt->close();
}

$connect->close();
echo json_encode($valid);
