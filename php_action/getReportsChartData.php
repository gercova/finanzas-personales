<?php
/**
 * php_action/getReportsChartData.php
 * Provides monthly chart datasets (income, expense, loan, project) and granular drilldown data.
 */
require_once 'core.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['userId'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'messages' => 'No autorizado.']);
    exit;
}

$userId   = (int)$_SESSION['userId'];
$year     = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
if ($year < 2000 || $year > 2100) $year = (int)date('Y');

$month    = isset($_GET['month']) && $_GET['month'] !== 'all' && $_GET['month'] !== '' ? (int)$_GET['month'] : null;
$category = isset($_GET['category']) ? strtolower(trim($_GET['category'])) : 'all';
$action   = isset($_GET['action']) ? trim($_GET['action']) : 'chart';

// Month names in Spanish
$monthNames = [
    1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
    5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
    9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'
];
$monthFullNames = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Setiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

// 1. Fetch available distinct years across transactions
$yearsList = [];
$yearQueries = [
    "SELECT DISTINCT YEAR(income_date) AS yr FROM incomes WHERE created_by = ? AND is_deleted = 0",
    "SELECT DISTINCT YEAR(expense_date) AS yr FROM expenses WHERE created_by = ? AND is_deleted = 0",
    "SELECT DISTINCT YEAR(loan_date) AS yr FROM loans WHERE created_by = ? AND is_deleted = 0",
    "SELECT DISTINCT YEAR(COALESCE(start_date, created_at)) AS yr FROM projects WHERE created_by = ? AND is_deleted = 0"
];
foreach ($yearQueries as $q) {
    $stmt = $connect->prepare($q);
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            if (!empty($r['yr'])) {
                $yearsList[(int)$r['yr']] = true;
            }
        }
        $stmt->close();
    }
}
$yearsList[(int)date('Y')] = true;
$yearsList[$year] = true;
$availableYears = array_keys($yearsList);
rsort($availableYears);

// 2. Compute 12-month series for the chosen year
$incomeData  = array_fill(1, 12, 0.0);
$expenseData = array_fill(1, 12, 0.0);
$loanData    = array_fill(1, 12, 0.0);
$projectData = array_fill(1, 12, 0.0);

// Incomes by month
$stmt = $connect->prepare("
    SELECT MONTH(income_date) AS m, COALESCE(SUM(amount), 0) AS total
    FROM incomes
    WHERE created_by = ? AND is_deleted = 0 AND status <> 'CANCELLED'
      AND YEAR(income_date) = ?
    GROUP BY MONTH(income_date)
");
if ($stmt) {
    $stmt->bind_param("ii", $userId, $year);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $m = (int)$r['m'];
        if ($m >= 1 && $m <= 12) $incomeData[$m] = round((float)$r['total'], 2);
    }
    $stmt->close();
}

// Expenses by month
$stmt = $connect->prepare("
    SELECT MONTH(expense_date) AS m, COALESCE(SUM(amount), 0) AS total
    FROM expenses
    WHERE created_by = ? AND is_deleted = 0
      AND YEAR(expense_date) = ?
    GROUP BY MONTH(expense_date)
");
if ($stmt) {
    $stmt->bind_param("ii", $userId, $year);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $m = (int)$r['m'];
        if ($m >= 1 && $m <= 12) $expenseData[$m] = round((float)$r['total'], 2);
    }
    $stmt->close();
}

// Loans by month (principal_amount)
$stmt = $connect->prepare("
    SELECT MONTH(loan_date) AS m, COALESCE(SUM(principal_amount), 0) AS total
    FROM loans
    WHERE created_by = ? AND is_deleted = 0
      AND YEAR(loan_date) = ?
    GROUP BY MONTH(loan_date)
");
if ($stmt) {
    $stmt->bind_param("ii", $userId, $year);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $m = (int)$r['m'];
        if ($m >= 1 && $m <= 12) $loanData[$m] = round((float)$r['total'], 2);
    }
    $stmt->close();
}

// Projects by month: expenses linked to projects + incomes linked to projects + new project budgets
$stmt = $connect->prepare("
    SELECT MONTH(e.expense_date) AS m, COALESCE(SUM(e.amount), 0) AS total
    FROM expenses e
    WHERE e.created_by = ? AND e.is_deleted = 0 AND e.project_id IS NOT NULL
      AND YEAR(e.expense_date) = ?
    GROUP BY MONTH(e.expense_date)
");
if ($stmt) {
    $stmt->bind_param("ii", $userId, $year);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $m = (int)$r['m'];
        if ($m >= 1 && $m <= 12) $projectData[$m] += round((float)$r['total'], 2);
    }
    $stmt->close();
}

$stmt = $connect->prepare("
    SELECT MONTH(i.income_date) AS m, COALESCE(SUM(i.amount), 0) AS total
    FROM incomes i
    WHERE i.created_by = ? AND i.is_deleted = 0 AND i.project_id IS NOT NULL AND i.status <> 'CANCELLED'
      AND YEAR(i.income_date) = ?
    GROUP BY MONTH(i.income_date)
");
if ($stmt) {
    $stmt->bind_param("ii", $userId, $year);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $m = (int)$r['m'];
        if ($m >= 1 && $m <= 12) $projectData[$m] += round((float)$r['total'], 2);
    }
    $stmt->close();
}

// If no project transactions in a month, also consider projects created with budget in that month
$stmt = $connect->prepare("
    SELECT MONTH(COALESCE(start_date, created_at)) AS m, COALESCE(SUM(budget), 0) AS total
    FROM projects
    WHERE created_by = ? AND is_deleted = 0
      AND YEAR(COALESCE(start_date, created_at)) = ?
    GROUP BY MONTH(COALESCE(start_date, created_at))
");
if ($stmt) {
    $stmt->bind_param("ii", $userId, $year);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $m = (int)$r['m'];
        if ($m >= 1 && $m <= 12) {
            // If project data is 0, add budget
            if ($projectData[$m] == 0) {
                $projectData[$m] = round((float)$r['total'], 2);
            }
        }
    }
    $stmt->close();
}

// Assemble categories and series for Highcharts
$categoriesX = array_values($monthNames);

// Build series (colors matching user reference image: Blue, Purple, Green, Orange)
$allSeries = [
    [
        'id'    => 'income',
        'key'   => 'income',
        'name'  => 'Ingresos',
        'color' => '#3498db', // vibrant blue
        'data'  => array_values($incomeData)
    ],
    [
        'id'    => 'expense',
        'key'   => 'expense',
        'name'  => 'Gastos',
        'color' => '#5b2c6f', // deep purple (matching the purple bars in image)
        'data'  => array_values($expenseData)
    ],
    [
        'id'    => 'loan',
        'key'   => 'loan',
        'name'  => 'Préstamos',
        'color' => '#2ecc71', // vibrant green (matching green bars in image)
        'data'  => array_values($loanData)
    ],
    [
        'id'    => 'project',
        'key'   => 'project',
        'name'  => 'Proyectos',
        'color' => '#e67e22', // warm orange
        'data'  => array_values($projectData)
    ]
];

// Totals for year
$totals = [
    'income'  => array_sum($incomeData),
    'expense' => array_sum($expenseData),
    'loan'    => array_sum($loanData),
    'project' => array_sum($projectData)
];

// 3. Granular Drilldown Data (if requested or by default for current selection)
$drilldown = null;

if (!empty($_GET['drilldown']) || $action === 'drilldown') {
    $reqCategory = $category !== 'all' ? $category : 'expense';
    $reqMonth    = $month && $month >= 1 && $month <= 12 ? $month : null;

    $items = [];
    $breakdown = [];
    $totalDrill = 0.0;

    if ($reqCategory === 'expense') {
        $sql = "
            SELECT e.expense_id AS id, e.expense_date AS date, e.concept, e.amount, e.status, e.notes,
                   COALESCE(fc.category_name, 'Sin Categoría') AS category_name,
                   COALESCE(a.account_name, 'Sin Cuenta') AS account_name,
                   p.project_name
            FROM expenses e
            LEFT JOIN financial_categories fc ON e.category_id = fc.category_id
            LEFT JOIN accounts a ON e.account_id = a.account_id
            LEFT JOIN projects p ON e.project_id = p.project_id
            WHERE e.created_by = ? AND e.is_deleted = 0 AND YEAR(e.expense_date) = ?
        ";
        if ($reqMonth) $sql .= " AND MONTH(e.expense_date) = " . (int)$reqMonth;
        $sql .= " ORDER BY e.expense_date DESC";

        $stmt = $connect->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ii", $userId, $year);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                $amt = (float)$r['amount'];
                $totalDrill += $amt;
                $catName = $r['category_name'];
                if (!isset($breakdown[$catName])) {
                    $breakdown[$catName] = ['name' => $catName, 'total' => 0.0, 'count' => 0];
                }
                $breakdown[$catName]['total'] += $amt;
                $breakdown[$catName]['count']++;
                $items[] = [
                    'id'            => (int)$r['id'],
                    'date'          => $r['date'],
                    'concept'       => $r['concept'],
                    'amount'        => $amt,
                    'category_name' => $catName,
                    'account_name'  => $r['account_name'],
                    'project_name'  => $r['project_name'] ?: '-',
                    'status'        => $r['status'],
                    'notes'         => $r['notes'] ?: ''
                ];
            }
            $stmt->close();
        }
    } elseif ($reqCategory === 'income') {
        $sql = "
            SELECT i.income_id AS id, i.income_date AS date, i.concept, i.amount, i.status, i.notes,
                   COALESCE(fc.category_name, 'Sin Categoría') AS category_name,
                   COALESCE(a.account_name, 'Sin Cuenta') AS account_name,
                   p.project_name
            FROM incomes i
            LEFT JOIN financial_categories fc ON i.category_id = fc.category_id
            LEFT JOIN accounts a ON i.account_id = a.account_id
            LEFT JOIN projects p ON i.project_id = p.project_id
            WHERE i.created_by = ? AND i.is_deleted = 0 AND i.status <> 'CANCELLED' AND YEAR(i.income_date) = ?
        ";
        if ($reqMonth) $sql .= " AND MONTH(i.income_date) = " . (int)$reqMonth;
        $sql .= " ORDER BY i.income_date DESC";

        $stmt = $connect->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ii", $userId, $year);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                $amt = (float)$r['amount'];
                $totalDrill += $amt;
                $catName = $r['category_name'];
                if (!isset($breakdown[$catName])) {
                    $breakdown[$catName] = ['name' => $catName, 'total' => 0.0, 'count' => 0];
                }
                $breakdown[$catName]['total'] += $amt;
                $breakdown[$catName]['count']++;
                $items[] = [
                    'id'            => (int)$r['id'],
                    'date'          => $r['date'],
                    'concept'       => $r['concept'],
                    'amount'        => $amt,
                    'category_name' => $catName,
                    'account_name'  => $r['account_name'],
                    'project_name'  => $r['project_name'] ?: '-',
                    'status'        => $r['status'],
                    'notes'         => $r['notes'] ?: ''
                ];
            }
            $stmt->close();
        }
    } elseif ($reqCategory === 'loan') {
        $sql = "
            SELECT l.loan_id AS id, l.loan_date AS date, l.borrower_name, l.concept,
                   l.principal_amount AS amount, l.total_receivable, l.amount_repaid, l.balance_pending,
                   l.status, l.notes, COALESCE(a.account_name, 'Sin Cuenta') AS account_name,
                   b.document_type, b.document_number
            FROM loans l
            LEFT JOIN accounts a ON l.account_id = a.account_id
            LEFT JOIN borrowers b ON l.borrower_id = b.borrower_id
            WHERE l.created_by = ? AND l.is_deleted = 0 AND YEAR(l.loan_date) = ?
        ";
        if ($reqMonth) $sql .= " AND MONTH(l.loan_date) = " . (int)$reqMonth;
        $sql .= " ORDER BY l.loan_date DESC";

        $stmt = $connect->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ii", $userId, $year);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                $amt = (float)$r['amount'];
                $totalDrill += $amt;
                $catName = $r['status'];
                if (!isset($breakdown[$catName])) {
                    $breakdown[$catName] = ['name' => 'Estado ' . $catName, 'total' => 0.0, 'count' => 0];
                }
                $breakdown[$catName]['total'] += $amt;
                $breakdown[$catName]['count']++;
                $items[] = [
                    'id'            => (int)$r['id'],
                    'date'          => $r['date'],
                    'concept'       => $r['concept'],
                    'borrower_name' => $r['borrower_name'],
                    'document_info' => !empty($r['document_number']) ? ($r['document_type'] . ': ' . $r['document_number']) : '',
                    'amount'        => $amt,
                    'category_name' => $r['borrower_name'],
                    'account_name'  => $r['account_name'],
                    'status'        => $r['status'],
                    'receivable'    => (float)$r['total_receivable'],
                    'repaid'        => (float)$r['amount_repaid'],
                    'pending'       => (float)$r['balance_pending'],
                    'notes'         => $r['notes'] ?: ''
                ];
            }
            $stmt->close();
        }
    } elseif ($reqCategory === 'project') {
        // Project items: projects + project transactions
        $sql = "
            SELECT p.project_id AS id, COALESCE(p.start_date, p.created_at) AS date, p.project_name AS concept,
                   p.client_name, p.budget AS amount, p.status, p.description AS notes
            FROM projects p
            WHERE p.created_by = ? AND p.is_deleted = 0 AND YEAR(COALESCE(p.start_date, p.created_at)) = ?
        ";
        if ($reqMonth) $sql .= " AND MONTH(COALESCE(p.start_date, p.created_at)) = " . (int)$reqMonth;
        $sql .= " ORDER BY p.project_id DESC";

        $stmt = $connect->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ii", $userId, $year);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                $amt = (float)$r['amount'];
                $totalDrill += $amt;
                $catName = 'Presupuesto: ' . ($r['status'] ?: 'ACTIVO');
                if (!isset($breakdown[$catName])) {
                    $breakdown[$catName] = ['name' => $catName, 'total' => 0.0, 'count' => 0];
                }
                $breakdown[$catName]['total'] += $amt;
                $breakdown[$catName]['count']++;
                $items[] = [
                    'id'            => (int)$r['id'],
                    'date'          => substr($r['date'], 0, 10),
                    'concept'       => $r['concept'],
                    'amount'        => $amt,
                    'category_name' => 'Proyecto (' . ($r['client_name'] ?: 'Propio') . ')',
                    'account_name'  => $r['client_name'] ?: 'Cliente Interno',
                    'status'        => $r['status'],
                    'notes'         => $r['notes'] ?: ''
                ];
            }
            $stmt->close();
        }

        // Also add expenses linked to projects
        $pExpSql = "
            SELECT e.expense_id AS id, e.expense_date AS date, e.concept, e.amount, e.status, e.notes,
                   p.project_name, a.account_name
            FROM expenses e
            JOIN projects p ON e.project_id = p.project_id
            LEFT JOIN accounts a ON e.account_id = a.account_id
            WHERE e.created_by = ? AND e.is_deleted = 0 AND YEAR(e.expense_date) = ?
        ";
        if ($reqMonth) $pExpSql .= " AND MONTH(e.expense_date) = " . (int)$reqMonth;
        $pExpSql .= " ORDER BY e.expense_date DESC";

        $pStmt = $connect->prepare($pExpSql);
        if ($pStmt) {
            $pStmt->bind_param("ii", $userId, $year);
            $pStmt->execute();
            $pRes = $pStmt->get_result();
            while ($pr = $pRes->fetch_assoc()) {
                $amt = (float)$pr['amount'];
                $totalDrill += $amt;
                $catName = 'Gasto Proyecto: ' . $pr['project_name'];
                if (!isset($breakdown[$catName])) {
                    $breakdown[$catName] = ['name' => $catName, 'total' => 0.0, 'count' => 0];
                }
                $breakdown[$catName]['total'] += $amt;
                $breakdown[$catName]['count']++;
                $items[] = [
                    'id'            => (int)$pr['id'],
                    'date'          => $pr['date'],
                    'concept'       => '[Gasto Proyecto] ' . $pr['concept'],
                    'amount'        => $amt,
                    'category_name' => $pr['project_name'],
                    'account_name'  => $pr['account_name'] ?: '-',
                    'status'        => $pr['status'],
                    'notes'         => $pr['notes'] ?: ''
                ];
            }
            $pStmt->close();
        }
    }

    // Sort breakdown by total descending
    $breakdownList = array_values($breakdown);
    usort($breakdownList, fn($a, $b) => $b['total'] <=> $a['total']);
    foreach ($breakdownList as &$b) {
        $b['percentage'] = $totalDrill > 0 ? round(($b['total'] / $totalDrill) * 100, 1) : 0;
    }
    unset($b);

    $catLabels = [
        'income'  => 'Ingresos',
        'expense' => 'Gastos',
        'loan'    => 'Préstamos',
        'project' => 'Proyectos'
    ];

    $drilldown = [
        'category'       => $reqCategory,
        'category_label' => $catLabels[$reqCategory] ?? ucfirst($reqCategory),
        'year'           => $year,
        'month'          => $reqMonth,
        'month_name'     => $reqMonth ? ($monthFullNames[$reqMonth] ?? '') : 'Todos los meses',
        'total_amount'   => round($totalDrill, 2),
        'total_items'    => count($items),
        'breakdown'      => $breakdownList,
        'items'          => $items
    ];
}

echo json_encode([
    'success'         => true,
    'year'            => $year,
    'available_years' => $availableYears,
    'categories'      => $categoriesX,
    'series'          => $allSeries,
    'totals'          => $totals,
    'drilldown'       => $drilldown
], JSON_UNESCAPED_UNICODE);
