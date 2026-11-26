<?php
require_once __DIR__ . '/core.php';
require_once __DIR__ . '/security.php';
header('Content-Type: application/json; charset=utf-8');

if (!verifyCsrfToken()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'messages' => 'Error de validación CSRF.']);
    exit;
}

$debt_id           = (int)($_POST['debt_id'] ?? 0);
$source_account_id = (int)($_POST['source_account_id'] ?? 0);
$payment_date      = cleanInput($_POST['payment_date'] ?? '');
$amount            = (float)($_POST['amount'] ?? 0);
$notes             = cleanInput($_POST['notes'] ?? '');
$userId            = (int)$_SESSION['userId'];

if ($debt_id <= 0 || $source_account_id <= 0 || $amount <= 0 || empty($payment_date)) {
    echo json_encode(['success' => false, 'messages' => 'Datos incompletos o inválidos.']);
    exit;
}

if (!verifyOwnedEntity($connect, 'debts', 'debt_id', $debt_id, $userId)) {
    echo json_encode(['success' => false, 'messages' => 'Préstamo recibido no encontrado o sin permiso.']);
    exit;
}
if (!verifyOwnedEntity($connect, 'accounts', 'account_id', $source_account_id, $userId)) {
    echo json_encode(['success' => false, 'messages' => 'Cuenta origen no válida o sin permiso.']);
    exit;
}

$receipt_image = null;
if (!empty($_FILES['receipt_image']['name'])) {
    $allowed_mime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $_FILES['receipt_image']['tmp_name']);
    finfo_close($finfo);
    if (in_array($mime, $allowed_mime, true)) {
        $ext       = pathinfo($_FILES['receipt_image']['name'], PATHINFO_EXTENSION);
        $filename  = bin2hex(random_bytes(16)) . '.' . strtolower($ext);
        $uploadDir = __DIR__ . '/../assests/images/receipts/';
        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
        if (is_writable($uploadDir) && @move_uploaded_file($_FILES['receipt_image']['tmp_name'], $uploadDir . $filename)) {
            $receipt_image = 'assests/images/receipts/' . $filename;
        }
    }
}

// Atomic Stored Procedure handles insertion + trigger updates debt totals & deducts source account balance
$sets = callProcedureMulti(
    $connect,
    "CALL sp_record_debt_payment(?, ?, ?, ?, ?, ?, ?)",
    "iiisdss",
    [$userId, $debt_id, $source_account_id, $payment_date, $amount, $receipt_image, $notes]
);

if (!empty($sets[0][0])) {
    $res = $sets[0][0];
    echo json_encode([
        'success'  => (int)$res['success'] === 1,
        'messages' => $res['messages'],
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['success' => false, 'messages' => 'Error al registrar el pago.']);
}
