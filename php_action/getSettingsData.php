<?php
/**
 * php_action/getSettingsData.php
 * Returns current user info and monthly spending quota settings as JSON.
 */
require_once 'core.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['userId'])) {
    echo json_encode(['success' => false, 'messages' => 'Sesión no iniciada.']);
    exit;
}

$userId       = (int)$_SESSION['userId'];
$currentMonth = date('Y-m');
list($monthStart, $monthEnd) = getMonthDateBounds($currentMonth);

$user = null;
$stmtUser = $connect->prepare("SELECT user_id, username, full_name, email, role, created_at FROM users WHERE user_id = ?");
if ($stmtUser) {
    $stmtUser->bind_param("i", $userId);
    $stmtUser->execute();
    $user = $stmtUser->get_result()->fetch_assoc();
    $stmtUser->close();
}

$currentLimit = null;
$stmtLim = $connect->prepare("SELECT monthly_limit, warning_threshold, critical_threshold FROM spending_limits WHERE created_by = ? AND month_year = ?");
if ($stmtLim) {
    $stmtLim->bind_param("is", $userId, $currentMonth);
    $stmtLim->execute();
    $currentLimit = $stmtLim->get_result()->fetch_assoc();
    $stmtLim->close();
}

$spentThisMonth = 0.0;
$stmtSp = $connect->prepare("SELECT COALESCE(SUM(amount),0) AS total FROM expenses WHERE created_by = ? AND is_deleted = 0 AND expense_date >= ? AND expense_date < ?");
if ($stmtSp) {
    $stmtSp->bind_param("iss", $userId, $monthStart, $monthEnd);
    $stmtSp->execute();
    $spentThisMonth = (float)$stmtSp->get_result()->fetch_assoc()['total'];
    $stmtSp->close();
}

$limitVal = $currentLimit ? (float)$currentLimit['monthly_limit'] : 4000.00;
$warnVal  = $currentLimit ? (float)$currentLimit['warning_threshold'] : 75.00;
$critVal  = $currentLimit ? (float)$currentLimit['critical_threshold'] : 90.00;
$pctSpent = $limitVal > 0 ? round(($spentThisMonth / $limitVal) * 100, 1) : 0;
$barType  = $pctSpent >= $critVal ? 'danger' : ($pctSpent >= $warnVal ? 'warning' : 'success');

echo json_encode([
    'success' => true,
    'user'    => $user,
    'quota'   => [
        'month_year'         => $currentMonth,
        'monthly_limit'      => $limitVal,
        'warning_threshold'  => $warnVal,
        'critical_threshold' => $critVal,
        'spent_this_month'   => $spentThisMonth,
        'pct_spent'          => $pctSpent,
        'bar_type'           => $barType
    ]
], JSON_UNESCAPED_UNICODE);
