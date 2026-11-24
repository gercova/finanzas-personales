<?php
/**
 * updateLoanStatus.php
 * Allows updating the status of loans (PENDIENTE, PARCIAL, PAGADO)
 */
require_once 'core.php';
require_once 'security.php';
header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'messages' => ''];

if (!verifyCsrfToken()) {
    $response['messages'] = 'Error de validación de seguridad (CSRF inválido). Recargue la página.';
    echo json_encode($response);
    exit;
}

$userId   = (int)$_SESSION['userId'];
$loanId   = (int)($_POST['loan_id'] ?? 0);
$newStatus = trim($_POST['status'] ?? '');

$allowedStatuses = ['PENDIENTE', 'PARCIAL', 'PAGADO'];

if ($loanId <= 0 || !in_array($newStatus, $allowedStatuses, true)) {
    $response['messages'] = 'Parámetros inválidos para actualizar el estado.';
    echo json_encode($response);
    exit;
}

// Check loan exists and belongs to user
$stmtCheck = $connect->prepare("
    SELECT loan_id, principal_amount, total_receivable, amount_repaid, balance_pending, status
    FROM loans
    WHERE loan_id = ? AND created_by = ? AND is_deleted = 0
");
if (!$stmtCheck) {
    $response['messages'] = 'Error de base de datos.';
    echo json_encode($response);
    exit;
}
$stmtCheck->bind_param("ii", $loanId, $userId);
$stmtCheck->execute();
$loan = $stmtCheck->get_result()->fetch_assoc();
$stmtCheck->close();

if (!$loan) {
    $response['messages'] = 'Préstamo no encontrado o ha sido eliminado.';
    echo json_encode($response);
    exit;
}

// Adjust amounts if changing status
$total = (float)$loan['total_receivable'];
$repaid = (float)$loan['amount_repaid'];
$pending = (float)$loan['balance_pending'];

if ($newStatus === 'PAGADO') {
    $repaid  = $total;
    $pending = 0.00;
} elseif ($newStatus === 'PENDIENTE' && $loan['status'] === 'PAGADO') {
    $repaid  = 0.00;
    $pending = $total;
}

$stmtUpdate = $connect->prepare("
    UPDATE loans
    SET status = ?, amount_repaid = ?, balance_pending = ?
    WHERE loan_id = ? AND created_by = ?
");
if (!$stmtUpdate) {
    $response['messages'] = 'Error preparando actualización.';
    echo json_encode($response);
    exit;
}
$stmtUpdate->bind_param("sddii", $newStatus, $repaid, $pending, $loanId, $userId);

if ($stmtUpdate->execute()) {
    $response['success'] = true;
    $response['messages'] = "Estado del préstamo actualizado exitosamente a {$newStatus}.";
} else {
    $response['messages'] = 'Error al actualizar el estado del préstamo.';
}
$stmtUpdate->close();

echo json_encode($response);
