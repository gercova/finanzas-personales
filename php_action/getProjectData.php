<?php
require_once __DIR__ . '/core.php';
header('Content-Type: application/json; charset=utf-8');

$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$userId = (int)$_SESSION['userId'];
if ($id < 1) {
    echo json_encode(null);
    exit();
}

try {
    $stmt = $connect->prepare("SELECT project_id, project_name, client_name, budget, start_date, end_date, status, description FROM projects WHERE project_id = ? AND created_by = ? AND is_deleted = 0");
    if (!$stmt) {
        echo json_encode(null);
        exit();
    }
    $stmt->bind_param("ii", $id, $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    echo json_encode($row ?: null, JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    echo json_encode(null);
}
