<?php
require_once __DIR__ . '/core.php';
require_once __DIR__ . '/security.php';
header('Content-Type: application/json; charset=utf-8');

if (!verifyCsrfToken()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'messages' => 'Error de validación CSRF.']);
    exit;
}

$lender_id        = !empty($_POST['lender_id']) ? (int)$_POST['lender_id'] : null;
$lender_name      = cleanInput($_POST['lender_name'] ?? '');
$lender_type      = strtoupper(cleanInput($_POST['lender_type'] ?? 'PERSONA'));
$concept          = cleanInput($_POST['concept'] ?? '');
$account_id       = (int)($_POST['account_id'] ?? 0);
$debt_date        = cleanInput($_POST['debt_date'] ?? '');
$due_date         = !empty($_POST['due_date']) ? cleanInput($_POST['due_date']) : null;
$principal_amount = (float)($_POST['principal_amount'] ?? 0);
$interest_type    = cleanInput($_POST['interest_type'] ?? 'NONE');
$interest_value   = !empty($_POST['interest_value']) ? (float)$_POST['interest_value'] : null;
$notes            = cleanInput($_POST['notes'] ?? '');
$userId           = (int)$_SESSION['userId'];

$validLenderTypes = ['PERSONA', 'EMPRESA', 'ENTIDAD_FINANCIERA'];
if (!in_array($lender_type, $validLenderTypes, true)) $lender_type = 'PERSONA';

$validTypes = ['NONE', 'PERCENTAGE', 'FIXED', 'NINGUNO', 'PORCENTAJE', 'FIJO'];
if (!in_array($interest_type, $validTypes, true)) $interest_type = 'NONE';
if ($interest_type === 'NINGUNO')    $interest_type = 'NONE';
if ($interest_type === 'PORCENTAJE') $interest_type = 'PERCENTAGE';
if ($interest_type === 'FIJO')       $interest_type = 'FIXED';

if ($lender_id && $lender_id > 0) {
    if (verifyOwnedEntity($connect, 'lenders', 'lender_id', $lender_id, $userId)) {
        $lStmt = $connect->prepare("SELECT full_name, lender_type FROM lenders WHERE lender_id = ? AND created_by = ?");
        if ($lStmt) {
            $lStmt->bind_param("ii", $lender_id, $userId);
            $lStmt->execute();
            $lRes = $lStmt->get_result()->fetch_assoc();
            $lStmt->close();
            if ($lRes && !empty($lRes['full_name'])) {
                $lender_name = $lRes['full_name'];
                $lender_type = $lRes['lender_type'] ?: $lender_type;
            }
        }
    } else {
        $lender_id = null;
    }
}

if (empty($lender_name) || empty($concept) || $account_id <= 0 || $principal_amount <= 0 || empty($debt_date)) {
    echo json_encode(['success' => false, 'messages' => 'Datos incompletos o inválidos.']);
    exit;
}

if (!verifyOwnedEntity($connect, 'accounts', 'account_id', $account_id, $userId)) {
    echo json_encode(['success' => false, 'messages' => 'Cuenta destino no válida o sin permiso.']);
    exit;
}

if ($interest_type === 'PERCENTAGE' && $interest_value > 0) {
    $total_payable = $principal_amount + ($principal_amount * $interest_value / 100);
} elseif ($interest_type === 'FIXED' && $interest_value > 0) {
    $total_payable = $principal_amount + $interest_value;
} else {
    $total_payable = $principal_amount;
    $interest_value = null;
}

$balance_pending = $total_payable;

$stmt = $connect->prepare(
    "INSERT INTO debts (lender_id, lender_name, lender_type, concept, account_id, debt_date, due_date, principal_amount, interest_type, interest_value, total_payable, amount_paid, balance_pending, status, notes, created_by)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, 'PENDIENTE', ?, ?)"
);
if (!$stmt) {
    echo json_encode(['success' => false, 'messages' => 'Error preparando consulta: ' . $connect->error]);
    exit;
}

$stmt->bind_param(
    "isssissdsdddsi",
    $lender_id, $lender_name, $lender_type, $concept, $account_id, $debt_date, $due_date,
    $principal_amount, $interest_type, $interest_value,
    $total_payable, $balance_pending, $notes, $userId
);

// Account balance credit is handled automatically by trg_debts_after_insert
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'messages' => 'Préstamo recibido registrado exitosamente.'], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['success' => false, 'messages' => 'Error al guardar: ' . $connect->error]);
}
$stmt->close();
