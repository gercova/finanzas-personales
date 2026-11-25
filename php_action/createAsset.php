<?php
require_once 'core.php';
header('Content-Type: application/json');

$valid = ['success' => false, 'messages' => ''];

if (!verifyCsrfToken()) {
    $valid['messages'] = 'Error de validación de seguridad (CSRF inválido). Recargue la página.';
    echo json_encode($valid); exit();
}

$userId        = (int)$_SESSION['userId'];
$assetName     = isset($_POST['asset_name'])          ? cleanInput($_POST['asset_name'])          : '';
$category      = isset($_POST['category'])            ? cleanInput($_POST['category'])            : '';
$estimatedVal  = isset($_POST['estimated_value'])     ? (float)$_POST['estimated_value']          : 0;
$purchasePrice = isset($_POST['purchase_price'])      ? (float)$_POST['purchase_price']           : 0;
$purchaseDate  = isset($_POST['purchase_date'])       ? trim($_POST['purchase_date'])              : null;
$docNumber     = isset($_POST['document_number'])     ? cleanInput($_POST['document_number'])     : '';
$location      = isset($_POST['location_or_custody']) ? cleanInput($_POST['location_or_custody']) : '';
$status        = isset($_POST['status'])              ? cleanInput($_POST['status'])              : 'ACTIVO';
$notes         = isset($_POST['notes'])               ? cleanInput($_POST['notes'])               : '';

$allowedCats    = ['VEHICULO','INMUEBLE','DOCUMENTO_FIDUCIARIO','EQUIPO_TECNOLOGICO','OTRO'];
$allowedStatuses= ['ACTIVO','EN_GARANTIA','VENDIDO','DADO_DE_BAJA'];

if (empty($assetName) || empty($category) || $estimatedVal <= 0) {
    $valid['messages'] = 'El nombre, categoría y valor estimado son requeridos.';
    echo json_encode($valid); exit();
}
if (!in_array($category, $allowedCats) || !in_array($status, $allowedStatuses)) {
    $valid['messages'] = 'Valores de categoría o estado no válidos.';
    echo json_encode($valid); exit();
}

// File upload
$imagePath = null;
if (isset($_FILES['asset_image']) && $_FILES['asset_image']['error'] === UPLOAD_ERR_OK) {
    $tmpFile     = $_FILES['asset_image']['tmp_name'];
    $origName    = $_FILES['asset_image']['name'];
    $ext         = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $allowedExts = ['jpg','jpeg','png','gif'];
    $allowedMimes= ['image/jpeg','image/png','image/gif'];
    $finfo       = finfo_open(FILEINFO_MIME_TYPE);
    $mime        = finfo_file($finfo, $tmpFile);
    finfo_close($finfo);
    if (!in_array($ext, $allowedExts) || !in_array($mime, $allowedMimes)) {
        $valid['messages'] = 'Formato de imagen no permitido. Solo JPG, PNG y GIF.';
        echo json_encode($valid); exit();
    }
    $newName  = bin2hex(random_bytes(16)) . '.' . $ext;
    $uploadDir = '../assests/images/assets/';
    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
    $destPath = $uploadDir . $newName;
    if (is_writable($uploadDir) && is_uploaded_file($tmpFile) && @move_uploaded_file($tmpFile, $destPath)) {
        $imagePath = 'assests/images/assets/' . $newName;
    } else {
        $valid['messages'] = 'No se pudo guardar la imagen del activo (verifique permisos de carpeta).';
        echo json_encode($valid); exit();
    }
}

$purchaseDate = !empty($purchaseDate) ? $purchaseDate : null;
$purchasePrice= $purchasePrice > 0 ? $purchasePrice : null;

$stmt = $connect->prepare("INSERT INTO assets (asset_name, category, estimated_value, purchase_date, purchase_price, document_number, location_or_custody, status, image_path, notes, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
if ($stmt) {
    $stmt->bind_param("ssdsdsssssi", $assetName, $category, $estimatedVal, $purchaseDate, $purchasePrice, $docNumber, $location, $status, $imagePath, $notes, $userId);
    if ($stmt->execute()) {
        $valid['success']  = true;
        $valid['messages'] = 'Activo registrado exitosamente.';
    } else {
        $valid['messages'] = 'Error al guardar en la base de datos.';
    }
    $stmt->close();
} else {
    $valid['messages'] = 'Error preparando la consulta.';
}

$connect->close();
echo json_encode($valid);
