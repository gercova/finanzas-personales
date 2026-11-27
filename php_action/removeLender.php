<?php
require_once __DIR__ . '/core.php';
require_once __DIR__ . '/security.php';
header('Content-Type: application/json; charset=utf-8');

if (!verifyCsrfToken()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'messages' => 'Error de validación CSRF.']);
    exit;
}

$userId = (int)$_SESSION['userId'];
$id     = (int)($_POST['id'] ?? $_POST['lender_id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'messages' => 'ID inválido.']);
    exit;
}

if (!verifyOwnedEntity($connect, 'lenders', 'lender_id', $id, $userId)) {
    echo json_encode(['success' => false, 'messages' => 'Registro no encontrado o sin permiso.']);
    exit;
}

try {
    $chk = $connect->prepare("SELECT COUNT(*) AS total FROM debts WHERE lender_id = ? AND created_by = ? AND is_deleted = 0 AND status != 'PAGADO'");
    if ($chk) {
        $chk->bind_param("ii", $id, $userId);
        $chk->execute();
        $row = $chk->get_result()->fetch_assoc();
        $chk->close();
        if (!empty($row['total']) && (int)$row['total'] > 0) {
            echo json_encode(['success' => false, 'messages' => 'No se puede eliminar porque tiene préstamos recibidos pendientes o activos.']);
            exit;
        }
    }

    $stmt = $connect->prepare("UPDATE lenders SET is_deleted = 1, deleted_at = NOW() WHERE lender_id = ? AND created_by = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'messages' => 'Error interno al eliminar.']);
        exit;
    }

    $stmt->bind_param("ii", $id, $userId);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'messages' => 'Acreedor / entidad eliminado exitosamente.'], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'messages' => 'Error al eliminar: ' . $connect->error]);
    }
    $stmt->close();
} catch (\Throwable $e) {
    echo json_encode(['success' => false, 'messages' => 'Error de base de datos: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
