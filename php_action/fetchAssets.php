<?php
require_once __DIR__ . '/core.php';
header('Content-Type: application/json; charset=utf-8');

$userId = (int)$_SESSION['userId'];

$stmt = $connect->prepare("
    SELECT asset_id AS id, asset_name, category, estimated_value, purchase_price, purchase_date,
           document_number, location_or_custody, status, image_path, notes
    FROM assets
    WHERE created_by = ? AND is_deleted = 0
    ORDER BY created_at DESC, asset_id DESC
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
    $imgPath = null;
    if (!empty($row['image_path']) && file_exists(__DIR__ . '/../' . $row['image_path'])) {
        $imgPath = (string)$row['image_path'];
    }

    $data[] = [
        'id'                  => (int)$row['id'],
        'asset_name'          => (string)($row['asset_name'] ?? ''),
        'category'            => (string)($row['category'] ?? 'OTRO'),
        'estimated_value'     => (float)$row['estimated_value'],
        'purchase_price'      => $row['purchase_price'] !== null ? (float)$row['purchase_price'] : null,
        'purchase_date'       => $row['purchase_date'] ? date('Y-m-d', strtotime($row['purchase_date'])) : '',
        'document_number'     => (string)($row['document_number'] ?? ''),
        'location_or_custody' => (string)($row['location_or_custody'] ?? ''),
        'status'              => (string)($row['status'] ?? 'ACTIVO'),
        'image_path'          => $imgPath,
        'notes'               => (string)($row['notes'] ?? ''),
    ];
}
$stmt->close();
echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
