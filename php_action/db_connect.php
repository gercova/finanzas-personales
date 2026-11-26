<?php
/**
 * Database connection handler & query/procedure helpers
 * Safely loads database credentials from environment variables via env_loader.
 */
require_once __DIR__ . '/env_loader.php';

$localhost = env('DB_HOST', '127.0.0.1');
$username  = env('DB_USER', 'admin');
$password  = env('DB_PASS', '');
$dbname    = env('DB_NAME', 'personal_finance');
$port      = (int)env('DB_PORT', 3306);
$charset   = env('DB_CHARSET', 'utf8mb4');

// Establish MySQLi connection
$connect = new mysqli($localhost, $username, $password, $dbname, $port);

// Check connection
if ($connect->connect_error) {
    if (env('APP_DEBUG', false)) {
        die("Connection Failed: " . $connect->connect_error);
    } else {
        die("Database connection error. Please verify server configuration.");
    }
} else {
    $connect->set_charset($charset);
    $connect->query("SET collation_connection = 'utf8mb4_unicode_ci'");
}

/**
 * Execute a MySQL/MariaDB Stored Procedure and collect all result sets cleanly,
 * draining extra metadata packets so subsequent queries never trigger "Commands out of sync".
 *
 * @param mysqli $connect
 * @param string $sql    e.g. "CALL sp_get_dashboard_data(?, ?, ?, ?)"
 * @param string $types  bind_param type string (e.g. "isss")
 * @param array  $params parameter values
 * @return array         array of result sets (each a list of associative rows)
 */
function callProcedureMulti(mysqli $connect, string $sql, string $types = '', array $params = []): array
{
    $resultSets = [];
    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        return $resultSets;
    }

    if ($types !== '' && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if ($stmt->execute()) {
        do {
            $res = $stmt->get_result();
            if ($res instanceof mysqli_result) {
                $resultSets[] = $res->fetch_all(MYSQLI_ASSOC);
                $res->free();
            }
        } while ($stmt->more_results() && $stmt->next_result());
    }

    $stmt->close();
    return $resultSets;
}

/**
 * Verify that an entity exists, is not soft-deleted, and is owned by $userId.
 * Prevents cross-user IDOR tampering when referencing foreign keys (account_id, category_id, project_id).
 */
function verifyOwnedEntity(mysqli $connect, string $table, string $pkColumn, int $id, int $userId): bool
{
    if ($id <= 0 || $userId <= 0) {
        return false;
    }

    // Allow-list table and column names to guarantee SQL identifier safety
    $allowedTables = [
        'accounts'             => 'account_id',
        'financial_categories' => 'category_id',
        'projects'             => 'project_id',
        'loans'                => 'loan_id',
        'incomes'              => 'income_id',
        'expenses'             => 'expense_id',
        'assets'               => 'asset_id',
        'borrowers'            => 'borrower_id',
        'debts'                => 'debt_id',
        'lenders'              => 'lender_id',
    ];

    if (!isset($allowedTables[$table]) || $allowedTables[$table] !== $pkColumn) {
        return false;
    }

    $sql  = "SELECT 1 FROM `{$table}` WHERE `{$pkColumn}` = ? AND `created_by` = ? AND `is_deleted` = 0 LIMIT 1";
    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("ii", $id, $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $ok  = ($res && $res->num_rows === 1);
    $stmt->close();

    return $ok;
}

/**
 * Compute SARGable half-open date bounds [start, nextMonthStart) for a YYYY-MM string.
 */
function getMonthDateBounds(?string $monthYear = null): array
{
    if ($monthYear === null || !preg_match('/^\d{4}-\d{2}$/', $monthYear)) {
        $monthYear = date('Y-m');
    }
    $start = $monthYear . '-01';
    $end   = date('Y-m-d', strtotime($start . ' +1 month'));
    return [$start, $end];
}

/**
 * Compute SARGable half-open date bounds [Jan 1, nextYear Jan 1) for a YYYY integer.
 */
function getYearDateBounds(?int $year = null): array
{
    if ($year === null || $year < 1970 || $year > 2100) {
        $year = (int)date('Y');
    }
    return [sprintf('%04d-01-01', $year), sprintf('%04d-01-01', $year + 1)];
}
