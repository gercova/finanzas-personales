<?php
require_once 'core.php';
require_once 'security.php';
header('Content-Type: application/json');

verifyCsrfToken();

$concept      = cleanInput($_POST['concept']     ?? '');
$account_id   = (int)($_POST['account_id']       ?? 0);
$category_id  = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
$project_id   = !empty($_POST['project_id'])  ? (int)$_POST['project_id']  : null;
$expense_date = cleanInput($_POST['expense_date'] ?? '');
$amount       = (float)($_POST['amount']          ?? 0);
$status       = cleanInput($_POST['status']       ?? 'PAGADO');
$notes        = cleanInput($_POST['notes']        ?? '');
$userId       = (int)$_SESSION['userId'];

$validStatuses = ['PAGADO', 'PENDIENTE'];
if (!in_array($status, $validStatuses)) $status = 'PAGADO';

if (empty($concept) || $account_id <= 0 || $amount <= 0 || empty($expense_date)) {
    echo json_encode(['success' => false, 'messages' => 'Datos incompletos o inválidos.']);
    exit;
}

if (!verifyOwnedEntity($connect, 'accounts', 'account_id', $account_id, $userId)) {
    echo json_encode(['success' => false, 'messages' => 'Cuenta no válida o sin permiso.']);
    exit;
}

// Handle receipt image upload
$receipt_image = null;
if (!empty($_FILES['receipt_image']['name'])) {
    $allowed_mime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $_FILES['receipt_image']['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowed_mime)) {
        echo json_encode(['success' => false, 'messages' => 'Tipo de archivo no permitido. Solo imágenes (JPG, PNG, GIF, WebP).']);
        exit;
    }
    $ext = pathinfo($_FILES['receipt_image']['name'], PATHINFO_EXTENSION);
    $filename = bin2hex(random_bytes(16)) . '.' . strtolower($ext);
    $uploadDir = __DIR__ . '/../assests/images/receipts/';
    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
    if (!is_writable($uploadDir) || !@move_uploaded_file($_FILES['receipt_image']['tmp_name'], $uploadDir . $filename)) {
        echo json_encode(['success' => false, 'messages' => 'Error al subir el comprobante (verifique permisos de carpeta).']);
        exit;
    }
    $receipt_image = 'assests/images/receipts/' . $filename;
}

$stmt = $connect->prepare(
    "INSERT INTO expenses (concept, account_id, category_id, project_id, expense_date, amount, status, receipt_image, notes, created_by)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
if (!$stmt) {
    echo json_encode(['success' => false, 'messages' => 'Error preparando consulta: ' . $connect->error]);
    exit;
}
$stmt->bind_param("siiisdsssi", $concept, $account_id, $category_id, $project_id, $expense_date, $amount, $status, $receipt_image, $notes, $userId);

// Account balance deduction is handled automatically by trg_expenses_after_insert
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'messages' => 'Gasto registrado exitosamente.']);
} else {
    echo json_encode(['success' => false, 'messages' => 'Error al guardar: ' . $connect->error]);
}
$stmt->close();
