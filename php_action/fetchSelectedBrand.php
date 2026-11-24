<?php 	
require_once 'core.php';

$row = null;
if(isset($_POST['brandId'])) {
	$brandId = (int)$_POST['brandId'];
	$stmt = $connect->prepare("SELECT brand_id, brand_name, brand_active, brand_status FROM brands WHERE brand_id = ?");
	if($stmt) {
		$stmt->bind_param("i", $brandId);
		$stmt->execute();
		$result = $stmt->get_result();
		if($result->num_rows > 0){ 
			$row = $result->fetch_array();
		}
		$stmt->close();
	}
}

$connect->close();
echo json_encode($row);
