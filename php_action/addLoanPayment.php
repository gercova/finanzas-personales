<?php
require_once 'core.php';
require_once 'security.php';
header('Content-Type: application/json');

verifyCsrfToken();

$loan_id               = (int)($_POST['loan_id']                ?? 0);
$destination_account_id = (int)($_POST['destination_account_id'] ?? 0);
$payment_date          = cleanInput($_POST['payment_date']       ?? '');
$amount                = (float)($_POST['amount']                ?? 0);
$notes                 = cleanInput($_POST['notes']              ?? '');
$userId                = (int)$_SESSION['userId'];

if ($loan_id <= 0 || $destination_account_id <= 0 || $amount <= 0 || empty($payment_date)) {
    echo json_encode(['success'=>false,'messages'=>'Datos incompletos o inválidos.']);
    exit;
}

// Verify loan ownership
$stmtLoan = $connect->prepare("SELECT total_receivable, amount_repaid, balance_pending, status FROM loans WHERE loan_id=? AND created_by=?");
if (!$stmtLoan) { echo json_encode(['success'=>false,'messages'=>'Error interno.']); exit; }
$stmtLoan->bind_param("ii", $loan_id, $userId);
$stmtLoan->execute();
$loan = $stmtLoan->get_result()->fetch_assoc();
$stmtLoan->close();
if (!$loan) { echo json_encode(['success'=>false,'messages'=>'Préstamo no encontrado.']); exit; }

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
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        if (move_uploaded_file($_FILES['receipt_image']['tmp_name'], $uploadDir . $filename)) {
            $receipt_image = 'assests/images/receipts/' . $filename;
        }
    }
}

// Insert payment record
$stmtPay = $connect->prepare(
    "INSERT INTO loan_payments (loan_id, destination_account_id, payment_date, amount, receipt_image, notes)
     VALUES (?, ?, ?, ?, ?, ?)"
);
if (!$stmtPay) { echo json_encode(['success'=>false,'messages'=>'Error preparando pago: ' . $connect->error]); exit; }
$stmtPay->bind_param("iisdss", $loan_id, $destination_account_id, $payment_date, $amount, $receipt_image, $notes);
$stmtPay->execute();
$stmtPay->close();

// Update loan totals
$newRepaid  = (float)$loan['amount_repaid'] + $amount;
$newPending = max(0, (float)$loan['total_receivable'] - $newRepaid);
$newStatus  = $newPending <= 0 ? 'PAGADO' : ($newRepaid > 0 ? 'PARCIAL' : 'PENDIENTE');

$stmtUp = $connect->prepare(
    "UPDATE loans SET amount_repaid=?, balance_pending=?, status=? WHERE loan_id=?"
);
if (!$stmtUp) { echo json_encode(['success'=>false,'messages'=>'Error actualizando préstamo: ' . $connect->error]); exit; }
$stmtUp->bind_param("ddsi", $newRepaid, $newPending, $newStatus, $loan_id);
$stmtUp->execute();
$stmtUp->close();

// Credit destination account
$stmtAcc = $connect->prepare("UPDATE accounts SET current_balance = current_balance + ? WHERE account_id = ?");
if ($stmtAcc) { $stmtAcc->bind_param("di", $amount, $destination_account_id); $stmtAcc->execute(); $stmtAcc->close(); }

echo json_encode(['success'=>true,'messages'=>'Pago registrado exitosamente. Estado: '.$newStatus]);
