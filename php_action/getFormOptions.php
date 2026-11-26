<?php
/**
 * php_action/getFormOptions.php
 * Returns current user's active dropdown options for modal forms (accounts, financial categories, projects).
 */
require_once 'core.php';
header('Content-Type: application/json; charset=utf-8');

$userId = (int)$_SESSION['userId'];

$accounts = $incomeCategories = $expenseCategories = $projects = [];

$stmt = $connect->prepare("SELECT account_id AS id, account_name, account_type FROM accounts WHERE created_by=? AND status=1 AND is_deleted=0 ORDER BY account_name");
if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $accounts[] = $row;
    $stmt->close();
}

$stmt = $connect->prepare("SELECT category_id AS id, category_name FROM financial_categories WHERE created_by=? AND category_type='INCOME' AND status=1 AND is_deleted=0 ORDER BY category_name");
if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $incomeCategories[] = $row;
    $stmt->close();
}

$stmt = $connect->prepare("SELECT category_id AS id, category_name FROM financial_categories WHERE created_by=? AND category_type='EXPENSE' AND status=1 AND is_deleted=0 ORDER BY category_name");
if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $expenseCategories[] = $row;
    $stmt->close();
}

$stmt = $connect->prepare("SELECT project_id AS id, project_name FROM projects WHERE (created_by=? OR created_by IS NULL) AND status='ACTIVE' AND is_deleted=0 ORDER BY project_name");
if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $projects[] = $row;
    $stmt->close();
}

echo json_encode([
    'success'           => true,
    'accounts'          => $accounts,
    'incomeCategories'  => $incomeCategories,
    'expenseCategories' => $expenseCategories,
    'projects'          => $projects,
], JSON_UNESCAPED_UNICODE);
