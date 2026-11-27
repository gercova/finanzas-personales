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

$userId = (int)$_SESSION['userId'];
$id     = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
if ($id < 1) {
    $valid['messages'] = 'ID de proyecto inválido.';
    echo json_encode($valid, JSON_UNESCAPED_UNICODE);
    exit();
}

try {
    $stmt = $connect->prepare("UPDATE projects SET is_deleted = 1, deleted_at = NOW() WHERE project_id = ? AND created_by = ? AND is_deleted = 0");
    if ($stmt) {
        $stmt->bind_param("ii", $id, $userId);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $valid['success']  = true;
            $valid['messages'] = 'Proyecto eliminado exitosamente.';
        } else {
            $valid['messages'] = 'No se pudo eliminar el proyecto o no encontrado.';
        }
        $stmt->close();
    }
} catch (\Throwable $e) {
    $valid['messages'] = 'Error de base de datos: ' . $e->getMessage();
}

echo json_encode($valid, JSON_UNESCAPED_UNICODE);
