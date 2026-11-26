<?php
require_once __DIR__ . '/core.php';
header('Content-Type: application/json; charset=utf-8');

$userId = (int)$_SESSION['userId'];

$stmt = $connect->prepare("
    SELECT category_id AS id, category_name, category_type, color, icon, status
    FROM financial_categories
    WHERE created_by = ? AND is_deleted = 0
    ORDER BY category_type ASC, category_name ASC
");
if (!$stmt) {
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        'id'            => (int)$row['id'],
        'category_name' => (string)($row['category_name'] ?? ''),
        'category_type' => (string)($row['category_type'] ?? 'EXPENSE'),
        'color'         => (string)($row['color'] ?: '#5bc0de'),
        'icon'          => (string)($row['icon'] ?? ''),
        'status'        => (int)$row['status'],
    ];
}
$stmt->close();
echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
