<?php
/**
 * fetchCalendarEvents.php
 * Endpoint for FullCalendar in dashboard.php
 * Returns an array of events: Incomes (green), Expenses (red), Loans (amber/orange)
 */
require_once 'core.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['userId'])) {
    echo json_encode([]);
    exit;
}

$userId = (int)$_SESSION['userId'];

// Optional date range parameters sent by FullCalendar
$startParam = isset($_GET['start']) ? substr(trim($_GET['start']), 0, 10) : null;
$endParam   = isset($_GET['end']) ? substr(trim($_GET['end']), 0, 10) : null;

$useDateFilter = false;
if ($startParam && $endParam && preg_match('/^\d{4}-\d{2}-\d{2}$/', $startParam) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $endParam)) {
    $useDateFilter = true;
}

$events = [];

// 1. INCOMES (Green)
$sqlIncomes = "
    SELECT i.income_id, i.concept, i.amount, i.income_date, i.status, a.account_name, fc.category_name, p.project_name
    FROM incomes i
    LEFT JOIN accounts a ON i.account_id = a.account_id
    LEFT JOIN financial_categories fc ON i.category_id = fc.category_id
    LEFT JOIN projects p ON i.project_id = p.project_id
    WHERE i.created_by = ? AND i.is_deleted = 0 " . ($useDateFilter ? "AND i.income_date BETWEEN ? AND ?" : "") . "
    ORDER BY i.income_date ASC
";

$stmtInc = $connect->prepare($sqlIncomes);
if ($stmtInc) {
    if ($useDateFilter) {
        $stmtInc->bind_param("iss", $userId, $startParam, $endParam);
    } else {
        $stmtInc->bind_param("i", $userId);
    }
    $stmtInc->execute();
    $resInc = $stmtInc->get_result();
    while ($row = $resInc->fetch_assoc()) {
        $monto = (float)$row['amount'];
        $events[] = [
            'id'              => 'inc_' . $row['income_id'],
            'title'           => '+S/ ' . number_format($monto, 2) . ' · ' . $row['concept'],
            'start'           => $row['income_date'],
            'allDay'          => true,
            'backgroundColor' => '#27ae60',
            'borderColor'     => '#1e8449',
            'textColor'       => '#ffffff',
            'type'            => 'ingreso',
            'typeLabel'       => 'Ingreso',
            'badgeClass'      => 'label-success',
            'amount'          => number_format($monto, 2),
            'concept'         => $row['concept'],
            'account'         => $row['account_name'] ?: 'No especificada',
            'category'        => $row['category_name'] ?: 'Sin categoría',
            'project'         => $row['project_name'] ?: '',
            'status'          => $row['status'] ?: 'CONFIRMED'
        ];
    }
    $stmtInc->close();
}

// 2. EXPENSES (Red)
$sqlExpenses = "
    SELECT e.expense_id, e.concept, e.amount, e.expense_date, e.status, a.account_name, fc.category_name, p.project_name
    FROM expenses e
    LEFT JOIN accounts a ON e.account_id = a.account_id
    LEFT JOIN financial_categories fc ON e.category_id = fc.category_id
    LEFT JOIN projects p ON e.project_id = p.project_id
    WHERE e.created_by = ? AND e.is_deleted = 0 " . ($useDateFilter ? "AND e.expense_date BETWEEN ? AND ?" : "") . "
    ORDER BY e.expense_date ASC
";

$stmtExp = $connect->prepare($sqlExpenses);
if ($stmtExp) {
    if ($useDateFilter) {
        $stmtExp->bind_param("iss", $userId, $startParam, $endParam);
    } else {
        $stmtExp->bind_param("i", $userId);
    }
    $stmtExp->execute();
    $resExp = $stmtExp->get_result();
    while ($row = $resExp->fetch_assoc()) {
        $monto = (float)$row['amount'];
        $events[] = [
            'id'              => 'exp_' . $row['expense_id'],
            'title'           => '-S/ ' . number_format($monto, 2) . ' · ' . $row['concept'],
            'start'           => $row['expense_date'],
            'allDay'          => true,
            'backgroundColor' => '#d9534f',
            'borderColor'     => '#c9302c',
            'textColor'       => '#ffffff',
            'type'            => 'gasto',
            'typeLabel'       => 'Gasto',
            'badgeClass'      => 'label-danger',
            'amount'          => number_format($monto, 2),
            'concept'         => $row['concept'],
            'account'         => $row['account_name'] ?: 'No especificada',
            'category'        => $row['category_name'] ?: 'Sin categoría',
            'project'         => $row['project_name'] ?: '',
            'status'          => $row['status'] ?: 'PAGADO'
        ];
    }
    $stmtExp->close();
}

// 3. LOANS (Amber / Orange)
$sqlLoans = "
    SELECT l.loan_id, l.borrower_name, l.concept, l.principal_amount, l.balance_pending, l.loan_date, l.due_date, l.status, a.account_name
    FROM loans l
    LEFT JOIN accounts a ON l.account_id = a.account_id
    WHERE l.created_by = ? AND l.is_deleted = 0 " . ($useDateFilter ? "AND l.loan_date BETWEEN ? AND ?" : "") . "
    ORDER BY l.loan_date ASC
";

$stmtLoans = $connect->prepare($sqlLoans);
if ($stmtLoans) {
    if ($useDateFilter) {
        $stmtLoans->bind_param("iss", $userId, $startParam, $endParam);
    } else {
        $stmtLoans->bind_param("i", $userId);
    }
    $stmtLoans->execute();
    $resLoans = $stmtLoans->get_result();
    while ($row = $resLoans->fetch_assoc()) {
        $monto      = (float)$row['principal_amount'];
        $pendiente  = (float)$row['balance_pending'];
        $borrower   = !empty($row['borrower_name']) ? $row['borrower_name'] : $row['concept'];
        $events[]   = [
            'id'              => 'loan_' . $row['loan_id'],
            'title'           => '⇄ S/ ' . number_format($monto, 2) . ' · ' . $borrower,
            'start'           => $row['loan_date'],
            'allDay'          => true,
            'backgroundColor' => '#f0ad4e',
            'borderColor'     => '#eea236',
            'textColor'       => '#ffffff',
            'type'            => 'prestamo',
            'typeLabel'       => 'Préstamo',
            'badgeClass'      => 'label-warning',
            'amount'          => number_format($monto, 2),
            'balancePending'  => number_format($pendiente, 2),
            'concept'         => $row['concept'],
            'borrower'        => $row['borrower_name'],
            'account'         => $row['account_name'] ?: 'No especificada',
            'dueDate'         => $row['due_date'] ?: '',
            'status'          => $row['status'] ?: 'PENDIENTE'
        ];
    }
    $stmtLoans->close();
}

echo json_encode($events, JSON_UNESCAPED_UNICODE);
