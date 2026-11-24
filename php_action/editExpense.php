<?php
require_once 'core.php';
require_once 'security.php';
header('Content-Type: application/json');

verifyCsrfToken();

$expense_id  = (int)($_POST['expense_id']       ?? 0);
$concept     = cleanInput($_POST['concept']      ?? '');
$account_id  = (int)($_POST['account_id']        ?? 0);
$category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
$project_id  = !empty($_POST['project_id'])  ? (int)$_POST['project_id']  : null;
$expense_date = cleanInput($_POST['expense_date'] ?? '');
$amount      = (float)($_POST['amount']           ?? 0);
$status      = cleanInput($_POST['status']        ?? 'PAGADO');
$notes       = cleanInput($_POST['notes']         ?? '');
$userId      = (int)$_SESSION['userId'];

$validStatuses = ['PAGADO', 'PENDIENTE'];
if (!in_array($status, $validStatuses)) $status = 'PAGADO';

if ($expense_id <= 0 || empty($concept) || $account_id <= 0 || $amount <= 0 || empty($expense_date)) {
    echo json_encode(['success' => false, 'messages' => 'Datos incompletos o inválidos.']);
    exit;
}

// Get old record
$stmtOld = $connect->prepare("SELECT amount, account_id, status, receipt_image FROM expenses WHERE expense_id=? AND created_by=?");
if (!$stmtOld) { echo json_encode(['success'=>false,'messages'=>'Error interno.']); exit; }
$stmtOld->bind_param("ii", $expense_id, $userId);
$stmtOld->execute();
$old = $stmtOld->get_result()->fetch_assoc();
$stmtOld->close();
if (!$old) { echo json_encode(['success'=>false,'messages'=>'Registro no encontrado.']); exit; }

$receipt_image = $old['receipt_image'];

// Handle new image upload
if (!empty($_FILES['receipt_image']['name'])) {
    $allowed_mime = ['image/jpeg','image/png','image/gif','image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $_FILES['receipt_image']['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowed_mime)) {
        echo json_encode(['success'=>false,'messages'=>'Tipo de archivo no permitido.']);
        exit;
    }
    $ext = pathinfo($_FILES['receipt_image']['name'], PATHINFO_EXTENSION);
    $filename = bin2hex(random_bytes(16)) . '.' . strtolower($ext);
    $uploadDir = __DIR__ . '/../assests/images/receipts/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    if (move_uploaded_file($_FILES['receipt_image']['tmp_name'], $uploadDir . $filename)) {
        if ($old['receipt_image'] && file_exists(__DIR__ . '/../' . $old['receipt_image'])) {
            @unlink(__DIR__ . '/../' . $old['receipt_image']);
        }
        $receipt_image = 'assests/images/receipts/' . $filename;
    }
}

$stmt = $connect->prepare(
    "UPDATE expenses SET concept=?, account_id=?, category_id=?, project_id=?, expense_date=?, amount=?, status=?, receipt_image=?, notes=?
     WHERE expense_id=? AND created_by=?"
);
if (!$stmt) { echo json_encode(['success'=>false,'messages'=>'Error preparando consulta: ' . $connect->error]); exit; }
$stmt->bind_param("siiiidsssii",
    $concept, $account_id, $category_id, $project_id,
    $expense_date, $amount, $status, $receipt_image, $notes,
    $expense_id, $userId
);

if ($stmt->execute()) {
    // Reverse old balance effect
    if ($old['status'] === 'PAGADO') {
        $s = $connect->prepare("UPDATE accounts SET current_balance = current_balance + ? WHERE account_id = ?");
        if ($s) { $s->bind_param("di", $old['amount'], $old['account_id']); $s->execute(); $s->close(); }
    }
    // Apply new balance effect
    if ($status === 'PAGADO') {
        $s = $connect->prepare("UPDATE accounts SET current_balance = current_balance - ? WHERE account_id = ?");
        if ($s) { $s->bind_param("di", $amount, $account_id); $s->execute(); $s->close(); }
    }
    echo json_encode(['success'=>true,'messages'=>'Gasto actualizado exitosamente.']);
} else {
    echo json_encode(['success'=>false,'messages'=>'Error al actualizar: '.$connect->error]);
}
$stmt->close();
