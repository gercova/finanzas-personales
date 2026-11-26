<?php
/**
 * php_action/editBorrower.php
 * Updates an existing borrower's details.
 */
require_once 'core.php';
require_once 'security.php';
header('Content-Type: application/json; charset=utf-8');

verifyCsrfToken();

if (!isset($_SESSION['userId'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'messages' => 'No autorizado.']);
    exit;
}

$userId          = (int)$_SESSION['userId'];
$id              = (int)($_POST['id'] ?? $_POST['borrower_id'] ?? 0);
$document_type   = strtoupper(cleanInput($_POST['document_type'] ?? 'DNI'));
$document_number = trim(cleanInput($_POST['document_number'] ?? ''));
$full_name       = cleanInput($_POST['full_name'] ?? '');
$phone           = cleanInput($_POST['phone'] ?? '');
$email           = cleanInput($_POST['email'] ?? '');
$address         = cleanInput($_POST['address'] ?? '');
$notes           = cleanInput($_POST['notes'] ?? '');
$status          = (int)($_POST['status'] ?? 1);

$validTypes = ['DNI', 'RUC', 'CE', 'PASAPORTE', 'OTRO'];
if (!in_array($document_type, $validTypes)) {
    $document_type = 'DNI';
}
if (!in_array($status, [1, 2])) {
    $status = 1;
}

if ($id <= 0 || empty($document_number) || empty($full_name)) {
    echo json_encode(['success' => false, 'messages' => 'Datos incompletos o inválidos.']);
    exit;
}

// Check ownership
if (!verifyOwnedEntity($connect, 'borrowers', 'borrower_id', $id, $userId)) {
    echo json_encode(['success' => false, 'messages' => 'Prestatario no válido o sin permiso.']);
    exit;
}

// Check if another active borrower has this document number
$chkStmt = $connect->prepare("SELECT borrower_id FROM borrowers WHERE created_by = ? AND document_number = ? AND borrower_id != ? AND is_deleted = 0");
if ($chkStmt) {
    $chkStmt->bind_param("isi", $userId, $document_number, $id);
    $chkStmt->execute();
    $chkStmt->store_result();
    if ($chkStmt->num_rows > 0) {
        $chkStmt->close();
        echo json_encode(['success' => false, 'messages' => 'Ya existe otro prestatario registrado con este número de documento.']);
        exit;
    }
    $chkStmt->close();
}

$stmt = $connect->prepare("
    UPDATE borrowers
    SET document_type = ?, document_number = ?, full_name = ?, phone = ?, email = ?,
        address = ?, notes = ?, status = ?
    WHERE borrower_id = ? AND created_by = ? AND is_deleted = 0
");
if (!$stmt) {
    echo json_encode(['success' => false, 'messages' => 'Error preparando actualización: ' . $connect->error]);
    exit;
}

$stmt->bind_param("sssssssiii", $document_type, $document_number, $full_name, $phone, $email, $address, $notes, $status, $id, $userId);

if ($stmt->execute()) {
    // Optionally keep loan records updated with the new name if changed
    $upLoan = $connect->prepare("UPDATE loans SET borrower_name = ? WHERE borrower_id = ? AND created_by = ?");
    if ($upLoan) {
        $upLoan->bind_param("sii", $full_name, $id, $userId);
        $upLoan->execute();
        $upLoan->close();
    }

    echo json_encode(['success' => true, 'messages' => 'Prestatario actualizado exitosamente.'], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['success' => false, 'messages' => 'Error al actualizar: ' . $connect->error]);
}
$stmt->close();
