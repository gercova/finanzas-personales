<?php
/**
 * php_action/createBorrower.php
 * Registers a new borrower in the borrowers table.
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

if (empty($document_number) || empty($full_name)) {
    echo json_encode(['success' => false, 'messages' => 'El número de documento y el nombre completo son obligatorios.']);
    exit;
}

// Check if duplicate document already exists for this user (not deleted)
$chkStmt = $connect->prepare("SELECT borrower_id FROM borrowers WHERE created_by = ? AND document_number = ? AND is_deleted = 0");
if ($chkStmt) {
    $chkStmt->bind_param("is", $userId, $document_number);
    $chkStmt->execute();
    $chkStmt->store_result();
    if ($chkStmt->num_rows > 0) {
        $chkStmt->close();
        echo json_encode(['success' => false, 'messages' => 'Ya existe un prestatario registrado con este número de documento.']);
        exit;
    }
    $chkStmt->close();
}

$stmt = $connect->prepare("
    INSERT INTO borrowers (document_type, document_number, full_name, phone, email, address, notes, status, created_by)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");
if (!$stmt) {
    echo json_encode(['success' => false, 'messages' => 'Error preparando inserción: ' . $connect->error]);
    exit;
}

$stmt->bind_param("sssssssii", $document_type, $document_number, $full_name, $phone, $email, $address, $notes, $status, $userId);

if ($stmt->execute()) {
    $newId = $stmt->insert_id;
    echo json_encode([
        'success'      => true,
        'messages'     => 'Prestatario registrado exitosamente.',
        'borrower_id'  => $newId,
        'full_name'    => $full_name,
        'document_num' => $document_number
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['success' => false, 'messages' => 'Error al guardar el prestatario: ' . $connect->error]);
}
$stmt->close();
