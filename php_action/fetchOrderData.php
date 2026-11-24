<?php
require_once 'core.php';

$valid = array('order' => array(), 'order_item' => array());

if(isset($_POST['orderId'])) {
	$orderId = (int)$_POST['orderId'];
	$stmt = $connect->prepare("SELECT orders.order_id, orders.order_date, orders.client_name, orders.client_contact, orders.sub_total, orders.vat, orders.total_amount, orders.discount, orders.grand_total, orders.paid, orders.due, orders.payment_type, orders.payment_status FROM orders WHERE orders.order_id = ?");
	if($stmt) {
		$stmt->bind_param("i", $orderId);
		$stmt->execute();
		$result = $stmt->get_result();
		$data = $result->fetch_row();
		$valid['order'] = $data ? $data : array();
		$stmt->close();
	}
}

$connect->close();
echo json_encode($valid);
