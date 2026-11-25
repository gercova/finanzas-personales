<?php
/**
 * php_action/getDashboardData.php
 * Returns KPI, accounts balances, recent transactions and quota data as JSON.
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

$dashSets = callProcedureMulti(
    $connect,
    "CALL sp_get_dashboard_data(?, ?, ?, ?)",
    "isss",
    [$userId, $currentMonth, $monthStart, $monthEnd]
);

$kpiData = [
    'saldo_disponible'     => 0.0,
    'total_gastos_mes'      => 0.0,
    'prestamos_pendientes' => 0.0,
    'balance_total'        => 0.0,
    'monthly_limit'        => 4000.0,
    'warning_threshold'    => 75.0,
    'critical_threshold'   => 90.0,
    'quota_pct'            => 0.0,
    'quota_alert_class'    => 'success',
    'show_alert'           => false
];

if (!empty($dashSets[0][0])) {
    $row = $dashSets[0][0];
    $kpiData['saldo_disponible']     = (float)$row['saldo_disponible'];
    $kpiData['total_gastos_mes']      = (float)$row['total_gastos_mes'];
    $kpiData['prestamos_pendientes'] = (float)$row['prestamos_pendientes'];
    $kpiData['balance_total']        = (float)$row['balance_total'];

    if ($row['monthly_limit'] !== null) {
        $kpiData['monthly_limit']      = (float)$row['monthly_limit'];
        $kpiData['warning_threshold']  = (float)$row['warning_threshold'];
        $kpiData['critical_threshold'] = (float)$row['critical_threshold'];
    }

    if ($kpiData['monthly_limit'] > 0) {
        $kpiData['quota_pct'] = round(($kpiData['total_gastos_mes'] / $kpiData['monthly_limit']) * 100, 1);
        if ($kpiData['quota_pct'] >= $kpiData['critical_threshold']) {
            $kpiData['quota_alert_class'] = 'danger';
            $kpiData['show_alert']        = true;
        } elseif ($kpiData['quota_pct'] >= $kpiData['warning_threshold']) {
            $kpiData['quota_alert_class'] = 'warning';
            $kpiData['show_alert']        = true;
        }
    }
}

$accounts   = !empty($dashSets[1]) ? $dashSets[1] : [];
$recentTxns = !empty($dashSets[2]) ? $dashSets[2] : [];

echo json_encode([
    'success'     => true,
    'kpi'         => $kpiData,
    'accounts'    => $accounts,
    'recentTxns'  => $recentTxns,
    'month_label' => date('F Y')
], JSON_UNESCAPED_UNICODE);
