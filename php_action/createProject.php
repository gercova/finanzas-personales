<?php
require_once 'core.php';
header('Content-Type: application/json');

$valid = ['success' => false, 'messages' => ''];

if (!verifyCsrfToken()) {
    $valid['messages'] = 'Error de validación de seguridad (CSRF inválido). Recargue la página.';
    echo json_encode($valid); exit();
}

$userId     = (int)$_SESSION['userId'];
$name       = isset($_POST['project_name']) ? cleanInput($_POST['project_name']) : '';
$client     = isset($_POST['client_name'])  ? cleanInput($_POST['client_name'])  : '';
$budget     = isset($_POST['budget'])       ? (float)$_POST['budget']            : 0;
$startDate  = isset($_POST['start_date'])   ? trim($_POST['start_date'])         : null;
$endDate    = isset($_POST['end_date'])     ? trim($_POST['end_date'])           : null;
$status     = isset($_POST['status'])       ? cleanInput($_POST['status'])       : 'ACTIVE';
$description= isset($_POST['description'])  ? cleanInput($_POST['description'])  : '';

$allowedStatuses = ['ACTIVE','ON_HOLD','COMPLETED'];

if (empty($name) || $budget <= 0) {
    $valid['messages'] = 'El nombre del proyecto y el presupuesto son requeridos.';
    echo json_encode($valid); exit();
}
if (!in_array($status, $allowedStatuses)) {
    $valid['messages'] = 'Estado de proyecto no válido.';
    echo json_encode($valid); exit();
}

$startDate = !empty($startDate) ? $startDate : null;
$endDate   = !empty($endDate)   ? $endDate   : null;

$stmt = $connect->prepare("INSERT INTO projects (project_name, client_name, budget, start_date, end_date, status, description, created_by) VALUES (?,?,?,?,?,?,?,?)");
if ($stmt) {
    $stmt->bind_param("ssdssssi", $name, $client, $budget, $startDate, $endDate, $status, $description, $userId);
    if ($stmt->execute()) {
        $valid['success']  = true;
        $valid['messages'] = 'Proyecto creado exitosamente.';
    } else {
        $valid['messages'] = 'Error al guardar el proyecto.';
    }
    $stmt->close();
}

$connect->close();
echo json_encode($valid);
