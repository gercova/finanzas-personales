<?php
require_once 'core.php';
header('Content-Type: application/json');

$valid = ['success' => false, 'messages' => ''];

if (!verifyCsrfToken()) {
    $valid['messages'] = 'Error de validación de seguridad (CSRF inválido). Recargue la página.';
    echo json_encode($valid); exit();
}

$id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
if ($id < 1) {
    $valid['messages'] = 'ID de proyecto inválido.';
    echo json_encode($valid); exit();
}

$stmt = $connect->prepare("UPDATE projects SET is_deleted = 1, deleted_at = NOW() WHERE project_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $valid['success']  = true;
        $valid['messages'] = 'Proyecto eliminado exitosamente (borrado lógico).';
    } else {
        $valid['messages'] = 'Error al eliminar el proyecto.';
    }
    $stmt->close();
}

$connect->close();
echo json_encode($valid);
