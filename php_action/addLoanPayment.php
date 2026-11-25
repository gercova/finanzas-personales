<?php
require_once 'core.php';
require_once 'security.php';
header('Content-Type: application/json');

verifyCsrfToken();

$loan_id                = (int)($_POST['loan_id']                ?? 0);
$destination_account_id = (int)($_POST['destination_account_id'] ?? 0);
$payment_date           = cleanInput($_POST['payment_date']       ?? '');
$amount                 = (float)($_POST['amount']                ?? 0);
$notes                  = cleanInput($_POST['notes']              ?? '');
$userId                 = (int)$_SESSION['userId'];

if ($loan_id <= 0 || $destination_account_id <= 0 || $amount <= 0 || empty($payment_date)) {
    echo json_encode(['success'=>false,'messages'=>'Datos incompletos o inválidos.']);
    exit;
}

if (!verifyOwnedEntity($connect, 'loans', 'loan_id', $loan_id, $userId)) {
    echo json_encode(['success'=>false,'messages'=>'Préstamo no encontrado o sin permiso.']);
    exit;
}
if (!verifyOwnedEntity($connect, 'accounts', 'account_id', $destination_account_id, $userId)) {
    echo json_encode(['success'=>false,'messages'=>'Cuenta destino no válida o sin permiso.']);
    exit;
}

// Handle receipt upload
$receipt_image = null;
if (!empty($_FILES['receipt_image']['name'])) {
    $allowed_mime = ['image/jpeg','image/png','image/gif','image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $_FILES['receipt_image']['tmp_name']);
    finfo_close($finfo);
    if (in_array($mime, $allowed_mime)) {
        $ext = pathinfo($_FILES['receipt_image']['name'], PATHINFO_EXTENSION);
        $filename = bin2hex(random_bytes(16)) . '.' . strtolower($ext);
        $uploadDir = __DIR__ . '/../assests/images/receipts/';
        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
        if (is_writable($uploadDir) && @move_uploaded_file($_FILES['receipt_image']['tmp_name'], $uploadDir . $filename)) {
            $receipt_image = 'assests/images/receipts/' . $filename;
        }
    }
}

// Atomic Stored Procedure handles insertion + trigger updates loan totals & destination account balance
$sets = callProcedureMulti(
    $connect,
    "CALL sp_record_loan_payment(?, ?, ?, ?, ?, ?, ?)",
    "iiisdss",
    [$userId, $loan_id, $destination_account_id, $payment_date, $amount, $receipt_image, $notes]
);

if (!empty($sets[0][0])) {
    $res = $sets[0][0];
    echo json_encode([
        'success'  => (int)$res['success'] === 1,
        'messages' => $res['messages']
    ]);
} else {
    echo json_encode(['success' => false, 'messages' => 'Error al registrar el pago del préstamo.']);
}
