<?php
require_once __DIR__ . '/core.php';
require_once __DIR__ . '/security.php';
header('Content-Type: application/json; charset=utf-8');

if (!verifyCsrfToken()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'messages' => 'Error de validación CSRF.']);
    exit;
}

$debt_id          = (int)($_POST['debt_id'] ?? 0);
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
$status           = cleanInput($_POST['status'] ?? 'PENDIENTE');
$notes            = cleanInput($_POST['notes'] ?? '');
$userId           = (int)$_SESSION['userId'];

$validLenderTypes = ['PERSONA', 'EMPRESA', 'ENTIDAD_FINANCIERA'];
if (!in_array($lender_type, $validLenderTypes, true)) $lender_type = 'PERSONA';

$validTypes    = ['NONE', 'PERCENTAGE', 'FIXED', 'NINGUNO', 'PORCENTAJE', 'FIJO'];
$validStatuses = ['PENDIENTE', 'PARCIAL', 'PAGADO'];
if (!in_array($interest_type, $validTypes, true)) $interest_type = 'NONE';
if ($interest_type === 'NINGUNO')    $interest_type = 'NONE';
if ($interest_type === 'PORCENTAJE') $interest_type = 'PERCENTAGE';
if ($interest_type === 'FIJO')       $interest_type = 'FIXED';
if (!in_array($status, $validStatuses, true)) $status = 'PENDIENTE';

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

if ($debt_id <= 0 || empty($lender_name) || empty($concept) || $account_id <= 0 || $principal_amount <= 0 || empty($debt_date)) {
    echo json_encode(['success' => false, 'messages' => 'Datos incompletos o inválidos.']);
    exit;
}

$stmtOld = $connect->prepare("SELECT principal_amount, account_id, amount_paid FROM debts WHERE debt_id=? AND created_by=? AND is_deleted=0");
if (!$stmtOld) {
    echo json_encode(['success' => false, 'messages' => 'Error interno.']);
    exit;
}
$stmtOld->bind_param("ii", $debt_id, $userId);
$stmtOld->execute();
$old = $stmtOld->get_result()->fetch_assoc();
$stmtOld->close();
if (!$old) {
    echo json_encode(['success' => false, 'messages' => 'Registro no encontrado.']);
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

$balance_pending = max(0, $total_payable - (float)$old['amount_paid']);
if ($status === 'PAGADO') {
    $balance_pending = 0.00;
}

$stmt = $connect->prepare(
    "UPDATE debts SET lender_id=?, lender_name=?, lender_type=?, concept=?, account_id=?, debt_date=?, due_date=?, principal_amount=?, interest_type=?, interest_value=?, total_payable=?, balance_pending=?, status=?, notes=?
     WHERE debt_id=? AND created_by=? AND is_deleted=0"
);
if (!$stmt) {
    echo json_encode(['success' => false, 'messages' => 'Error preparando consulta: ' . $connect->error]);
    exit;
}

$stmt->bind_param(
    "isssissdsdddssii",
    $lender_id, $lender_name, $lender_type, $concept, $account_id, $debt_date, $due_date,
    $principal_amount, $interest_type, $interest_value,
    $total_payable, $balance_pending, $status, $notes,
    $debt_id, $userId
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'messages' => 'Préstamo recibido actualizado exitosamente.'], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['success' => false, 'messages' => 'Error al actualizar: ' . $connect->error]);
}
$stmt->close();
