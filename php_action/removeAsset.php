<?php
require_once 'core.php';
header('Content-Type: application/json');

$valid = ['success' => false, 'messages' => ''];

if (!verifyCsrfToken()) {
    $valid['messages'] = 'Error de validación de seguridad (CSRF inválido). Recargue la página.';
    echo json_encode($valid); exit();
}

$assetId = isset($_POST['asset_id']) ? (int)$_POST['asset_id'] : 0;
$userId  = (int)$_SESSION['userId'];

if ($assetId < 1) {
    $valid['messages'] = 'ID de activo inválido.';
    echo json_encode($valid); exit();
}

// Fetch image path before deleting
$chk = $connect->prepare("SELECT image_path FROM assets WHERE asset_id = ? AND created_by = ?");
$chk->bind_param("ii", $assetId, $userId);
$chk->execute();
$row = $chk->get_result()->fetch_assoc();
$chk->close();

if (!$row) {
    $valid['messages'] = 'Activo no encontrado.';
    echo json_encode($valid); exit();
}

$stmt = $connect->prepare("DELETE FROM assets WHERE asset_id = ? AND created_by = ?");
if ($stmt) {
    $stmt->bind_param("ii", $assetId, $userId);
    if ($stmt->execute()) {
        // Remove image file
        if (!empty($row['image_path']) && file_exists('../' . $row['image_path'])) {
            @unlink('../' . $row['image_path']);
        }
        $valid['success']  = true;
        $valid['messages'] = 'Activo eliminado exitosamente.';
    } else {
        $valid['messages'] = 'Error al eliminar el activo.';
    }
    $stmt->close();
}

$connect->close();
echo json_encode($valid);
