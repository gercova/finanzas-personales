<?php
/**
 * php_action/getFinancialReportData.php
 * Returns financial report stats, monthly trends, and categories distribution as JSON.
 */
require_once 'core.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['userId'])) {
    echo json_encode(['success' => false, 'messages' => 'Sesión no iniciada.']);
    exit;
}

$userId       = (int)$_SESSION['userId'];
$currentMonth = date('Y-m');
$currentYear  = (int)date('Y');
list($monthStart, $monthEnd) = getMonthDateBounds($currentMonth);
list($yearStart, $yearEnd)   = getYearDateBounds($currentYear);
$sixMonthsAgo                = date('Y-m-01', strtotime('-5 months'));

$repSets = callProcedureMulti(
    $connect,
    "CALL sp_get_financial_report(?, ?, ?, ?, ?, ?)",
    "isssss",
    [$userId, $yearStart, $yearEnd, $monthStart, $monthEnd, $sixMonthsAgo]
);

$kpi = [
    'saldo_disponible'     => 0.0,
    'gasto_mes'            => 0.0,
    'gasto_anio'           => 0.0,
    'prestamos_pendientes' => 0.0,
    'balance_total'        => 0.0,
    'total_activos'        => 0.0,
    'patrimonio_neto'      => 0.0,
    'ingresos_anio'        => 0.0,
    'ahorro_neto_anio'     => 0.0
];

if (!empty($repSets[0][0])) {
    $k = $repSets[0][0];
    $kpi['saldo_disponible']     = (float)$k['saldo_disponible'];
    $kpi['gasto_mes']            = (float)$k['gasto_mes'];
    $kpi['gasto_anio']           = (float)$k['gasto_anio'];
    $kpi['prestamos_pendientes'] = (float)$k['prestamos_pendientes'];
    $kpi['balance_total']        = (float)$k['balance_total'];
    $kpi['total_activos']        = (float)$k['total_activos'];
    $kpi['patrimonio_neto']      = (float)$k['patrimonio_neto'];
    $kpi['ingresos_anio']        = (float)$k['ingresos_anio'];
    $kpi['ahorro_neto_anio']     = $kpi['ingresos_anio'] - $kpi['gasto_anio'];
}

$accounts        = !empty($repSets[1]) ? $repSets[1] : [];
$monthlyExpenses = !empty($repSets[2]) ? $repSets[2] : [];
$topCategories   = !empty($repSets[3]) ? $repSets[3] : [];

echo json_encode([
    'success'         => true,
    'kpi'             => $kpi,
    'accounts'        => $accounts,
    'monthlyExpenses' => $monthlyExpenses,
    'topCategories'   => $topCategories,
    'current_year'    => $currentYear,
    'current_month'   => $currentMonth
], JSON_UNESCAPED_UNICODE);
