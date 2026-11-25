<?php
require_once 'core.php';
header('Content-Type: application/json');

$valid = ['success' => false, 'messages' => ''];

if (!verifyCsrfToken()) {
    $valid['messages'] = 'Error de validación de seguridad (CSRF inválido). Recargue la página.';
    echo json_encode($valid); exit();
}

$userId = (int)$_SESSION['userId'];
$id     = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
if ($id < 1) {
    $valid['messages'] = 'ID de proyecto inválido.';
    echo json_encode($valid); exit();
}

$stmt = $connect->prepare("UPDATE projects SET is_deleted = 1, deleted_at = NOW() WHERE project_id = ? AND (created_by = ? OR created_by IS NULL)");
if ($stmt) {
    $stmt->bind_param("ii", $id, $userId);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $valid['success']  = true;
        $valid['messages'] = 'Proyecto eliminado exitosamente (borrado lógico).';
    } else {
        $valid['messages'] = 'No se pudo eliminar el proyecto o no encontrado.';
    }
    $stmt->close();
}

$connect->close();
echo json_encode($valid);
