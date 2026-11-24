<?php 	
require_once 'core.php';

$imageUrl = '';
if(isset($_GET['i'])) {
	$productId = (int)$_GET['i'];
	$stmt = $connect->prepare("SELECT product_image FROM product WHERE product_id = ?");
	if($stmt) {
		$stmt->bind_param("i", $productId);
		$stmt->execute();
		$data = $stmt->get_result();
		if($row = $data->fetch_row()) {
			$imageUrl = "stock/" . $row[0];
		}
		$stmt->close();
	}
}

$connect->close();
echo $imageUrl;
