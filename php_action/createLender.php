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

if (empty($document_number) || empty($full_name)) {
    echo json_encode(['success' => false, 'messages' => 'El número de documento y el nombre / razón social son obligatorios.']);
    exit;
}

if (!tableExists($connect, 'lenders')) {
    echo json_encode(['success' => false, 'messages' => 'La tabla lenders aún no existe. Ejecute la migración 004 desde Configuración General del Sistema.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $chkStmt = $connect->prepare("SELECT lender_id FROM lenders WHERE created_by = ? AND document_number = ? AND is_deleted = 0");
    if ($chkStmt) {
        $chkStmt->bind_param("is", $userId, $document_number);
        $chkStmt->execute();
        $chkStmt->store_result();
        if ($chkStmt->num_rows > 0) {
            $chkStmt->close();
            echo json_encode(['success' => false, 'messages' => 'Ya existe un acreedor / entidad registrado con este documento.']);
            exit;
        }
        $chkStmt->close();
    }

    $stmt = $connect->prepare("
        INSERT INTO lenders (lender_type, document_type, document_number, full_name, phone, email, address, notes, status, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) {
        echo json_encode(['success' => false, 'messages' => 'Error preparando inserción: ' . $connect->error]);
        exit;
    }

    $stmt->bind_param("ssssssssii", $lender_type, $document_type, $document_number, $full_name, $phone, $email, $address, $notes, $status, $userId);

    if ($stmt->execute()) {
        $newId = $stmt->insert_id;
        echo json_encode([
            'success'     => true,
            'messages'    => 'Acreedor / entidad registrado exitosamente.',
            'lender_id'   => $newId,
            'lender_type' => $lender_type,
            'full_name'   => $full_name,
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'messages' => 'Error al guardar: ' . $connect->error]);
    }
    $stmt->close();
} catch (\Throwable $e) {
    echo json_encode(['success' => false, 'messages' => 'Error de base de datos: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
