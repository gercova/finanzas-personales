<?php
require_once __DIR__ . '/core.php';
require_once __DIR__ . '/security.php';
header('Content-Type: application/json; charset=utf-8');

if (!verifyCsrfToken()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'messages' => 'Error de validación CSRF.']);
    exit;
}

$userId          = (int)$_SESSION['userId'];
$id              = (int)($_POST['id'] ?? $_POST['lender_id'] ?? 0);
$lender_type     = strtoupper(cleanInput($_POST['lender_type'] ?? 'PERSONA'));
$document_type   = strtoupper(cleanInput($_POST['document_type'] ?? 'DNI'));
$document_number = trim(cleanInput($_POST['document_number'] ?? ''));
$full_name       = cleanInput($_POST['full_name'] ?? '');
$phone           = cleanInput($_POST['phone'] ?? '');
$email           = cleanInput($_POST['email'] ?? '');
$address         = cleanInput($_POST['address'] ?? '');
$notes           = cleanInput($_POST['notes'] ?? '');
$status          = (int)($_POST['status'] ?? 1);

$validLenderTypes = ['PERSONA', 'EMPRESA', 'ENTIDAD_FINANCIERA'];
if (!in_array($lender_type, $validLenderTypes, true)) $lender_type = 'PERSONA';

$validDocTypes = ['DNI', 'RUC', 'CE', 'PASAPORTE', 'OTRO'];
if (!in_array($document_type, $validDocTypes, true)) $document_type = 'DNI';

if (!in_array($status, [1, 2], true)) $status = 1;

if ($id <= 0 || empty($document_number) || empty($full_name)) {
    echo json_encode(['success' => false, 'messages' => 'Datos incompletos o inválidos.']);
    exit;
}

if (!verifyOwnedEntity($connect, 'lenders', 'lender_id', $id, $userId)) {
    echo json_encode(['success' => false, 'messages' => 'Acreedor / entidad no válida o sin permiso.']);
    exit;
}

try {
    $chkStmt = $connect->prepare("SELECT lender_id FROM lenders WHERE created_by = ? AND document_number = ? AND lender_id != ? AND is_deleted = 0");
    if ($chkStmt) {
        $chkStmt->bind_param("isi", $userId, $document_number, $id);
        $chkStmt->execute();
        $chkStmt->store_result();
        if ($chkStmt->num_rows > 0) {
            $chkStmt->close();
            echo json_encode(['success' => false, 'messages' => 'Ya existe otro acreedor registrado con este documento.']);
            exit;
        }
        $chkStmt->close();
    }

    $stmt = $connect->prepare("
        UPDATE lenders
        SET lender_type = ?, document_type = ?, document_number = ?, full_name = ?, phone = ?, email = ?,
            address = ?, notes = ?, status = ?
        WHERE lender_id = ? AND created_by = ? AND is_deleted = 0
    ");
    if (!$stmt) {
        echo json_encode(['success' => false, 'messages' => 'Error preparando actualización: ' . $connect->error]);
        exit;
    }

    $stmt->bind_param("ssssssssiii", $lender_type, $document_type, $document_number, $full_name, $phone, $email, $address, $notes, $status, $id, $userId);

    if ($stmt->execute()) {
        $upDebt = $connect->prepare("UPDATE debts SET lender_name = ?, lender_type = ? WHERE lender_id = ? AND created_by = ?");
        if ($upDebt) {
            $upDebt->bind_param("ssii", $full_name, $lender_type, $id, $userId);
            $upDebt->execute();
            $upDebt->close();
        }
        echo json_encode(['success' => true, 'messages' => 'Acreedor / entidad actualizado exitosamente.'], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'messages' => 'Error al actualizar: ' . $connect->error]);
    }
    $stmt->close();
} catch (\Throwable $e) {
    echo json_encode(['success' => false, 'messages' => 'Error de base de datos: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
