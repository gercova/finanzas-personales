<?php
require_once 'core.php';
header('Content-Type: application/json');

$assetId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$userId  = (int)$_SESSION['userId'];

if ($assetId < 1) { echo json_encode(null); exit(); }

$stmt = $connect->prepare("SELECT asset_id, asset_name, category, estimated_value, purchase_price, purchase_date, document_number, location_or_custody, status, image_path, notes FROM assets WHERE asset_id = ? AND created_by = ?");
$stmt->bind_param("ii", $assetId, $userId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$connect->close();
echo json_encode($row);
