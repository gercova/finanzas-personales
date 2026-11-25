<?php
require_once 'core.php';
header('Content-Type: application/json');

$valid = ['success' => false, 'messages' => ''];

if (!verifyCsrfToken()) {
    $valid['messages'] = 'Error de validación de seguridad (CSRF inválido). Recargue la página.';
    echo json_encode($valid); exit();
}

$userId   = (int)$_SESSION['userId'];
$assetId  = isset($_POST['edit_asset_id'])         ? (int)$_POST['edit_asset_id']                 : 0;
$assetName= isset($_POST['edit_asset_name'])        ? cleanInput($_POST['edit_asset_name'])         : '';
$category = isset($_POST['edit_category'])          ? cleanInput($_POST['edit_category'])           : '';
$estVal   = isset($_POST['edit_estimated_value'])   ? (float)$_POST['edit_estimated_value']         : 0;
$purPrice = isset($_POST['edit_purchase_price'])    ? (float)$_POST['edit_purchase_price']          : 0;
$purDate  = isset($_POST['edit_purchase_date'])     ? trim($_POST['edit_purchase_date'])             : null;
$docNum   = isset($_POST['edit_document_number'])   ? cleanInput($_POST['edit_document_number'])    : '';
$location = isset($_POST['edit_location_or_custody'])? cleanInput($_POST['edit_location_or_custody']): '';
$status   = isset($_POST['edit_status'])            ? cleanInput($_POST['edit_status'])             : 'ACTIVO';
$notes    = isset($_POST['edit_notes'])             ? cleanInput($_POST['edit_notes'])              : '';

$allowedCats    = ['VEHICULO','INMUEBLE','DOCUMENTO_FIDUCIARIO','EQUIPO_TECNOLOGICO','OTRO'];
$allowedStatuses= ['ACTIVO','EN_GARANTIA','VENDIDO','DADO_DE_BAJA'];

if ($assetId < 1 || empty($assetName) || empty($category) || $estVal <= 0) {
    $valid['messages'] = 'El nombre, categoría y valor estimado son requeridos.';
    echo json_encode($valid); exit();
}
if (!in_array($category, $allowedCats) || !in_array($status, $allowedStatuses)) {
    $valid['messages'] = 'Valores de categoría o estado no válidos.';
    echo json_encode($valid); exit();
}

// Verify ownership
$chk = $connect->prepare("SELECT asset_id, image_path FROM assets WHERE asset_id = ? AND created_by = ? AND is_deleted = 0");
$chk->bind_param("ii", $assetId, $userId);
$chk->execute();
$existing = $chk->get_result()->fetch_assoc();
$chk->close();

if (!$existing) {
    $valid['messages'] = 'Activo no encontrado.';
    echo json_encode($valid); exit();
}

// Optional new image
$imagePath = $existing['image_path'];
if (isset($_FILES['edit_asset_image']) && $_FILES['edit_asset_image']['error'] === UPLOAD_ERR_OK) {
    $tmpFile     = $_FILES['edit_asset_image']['tmp_name'];
    $ext         = strtolower(pathinfo($_FILES['edit_asset_image']['name'], PATHINFO_EXTENSION));
    $finfo       = finfo_open(FILEINFO_MIME_TYPE);
    $mime        = finfo_file($finfo, $tmpFile);
    finfo_close($finfo);
    $allowedExts = ['jpg','jpeg','png','gif'];
    $allowedMimes= ['image/jpeg','image/png','image/gif'];
    if (in_array($ext, $allowedExts) && in_array($mime, $allowedMimes)) {
        $newName = bin2hex(random_bytes(16)) . '.' . $ext;
        $uploadDir = '../assests/images/assets/';
        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
        $dest    = $uploadDir . $newName;
        if (is_writable($uploadDir) && is_uploaded_file($tmpFile) && @move_uploaded_file($tmpFile, $dest)) {
            // Remove old file
            if (!empty($existing['image_path']) && file_exists('../' . $existing['image_path'])) {
                @unlink('../' . $existing['image_path']);
            }
            $imagePath = 'assests/images/assets/' . $newName;
        } else {
            $valid['messages'] = 'No se pudo guardar la imagen del activo (verifique permisos de carpeta).';
            echo json_encode($valid); exit();
        }
    }
}

$purDate  = !empty($purDate)  ? $purDate  : null;
$purPrice = $purPrice > 0     ? $purPrice : null;

$stmt = $connect->prepare("UPDATE assets SET asset_name=?, category=?, estimated_value=?, purchase_date=?, purchase_price=?, document_number=?, location_or_custody=?, status=?, image_path=?, notes=? WHERE asset_id=? AND created_by=? AND is_deleted=0");
if ($stmt) {
    $stmt->bind_param("ssdsdsssssii", $assetName, $category, $estVal, $purDate, $purPrice, $docNum, $location, $status, $imagePath, $notes, $assetId, $userId);
    if ($stmt->execute()) {
        $valid['success']  = true;
        $valid['messages'] = 'Activo actualizado exitosamente.';
    } else {
        $valid['messages'] = 'Error al actualizar en la base de datos.';
    }
    $stmt->close();
}

$connect->close();
echo json_encode($valid);
