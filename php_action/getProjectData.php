<?php
require_once 'core.php';
header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id < 1) { echo json_encode(null); exit(); }

$stmt = $connect->prepare("SELECT project_id, project_name, client_name, budget, start_date, end_date, status, description FROM projects WHERE project_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$connect->close();
echo json_encode($row);
