<?php
require_once __DIR__ . '/core.php';
header('Content-Type: application/json; charset=utf-8');

$userId = (int)$_SESSION['userId'];

$stmt = $connect->prepare("
    SELECT lender_id AS id, lender_type, document_type, document_number, full_name,
           phone, email, address, notes, status, created_at
    FROM lenders
    WHERE created_by = ? AND is_deleted = 0
    ORDER BY full_name ASC
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
        'id'              => (int)$row['id'],
        'lender_type'     => (string)($row['lender_type'] ?? 'PERSONA'),
        'document_type'   => (string)($row['document_type'] ?? ''),
        'document_number' => (string)($row['document_number'] ?? ''),
        'full_name'       => (string)($row['full_name'] ?? ''),
        'phone'           => (string)($row['phone'] ?? ''),
        'email'           => (string)($row['email'] ?? ''),
        'address'         => (string)($row['address'] ?? ''),
        'notes'           => (string)($row['notes'] ?? ''),
        'status'          => (int)$row['status'],
        'created_at'      => (string)($row['created_at'] ?? ''),
    ];
}
$stmt->close();

echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
