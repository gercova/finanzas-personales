<?php
require_once 'core.php';

$row = null;
if(isset($_POST['productId'])) {
	$productId = (int)$_POST['productId'];
	$stmt = $connect->prepare("SELECT product_id, product_name, product_image, brand_id, categories_id, quantity, rate, active, status FROM product WHERE product_id = ?");
	if($stmt) {
		$stmt->bind_param("i", $productId);
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
