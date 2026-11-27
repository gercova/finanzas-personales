<?php
/**
 * bulkImport.php — Unified bulk-import handler (Excel / CSV)
 *
 * POST params:
 *   module   : income | expense | loan | debt | project | asset
 *   file     : uploaded file (multipart/form-data)
 *   csrf_token
 *
 * Returns JSON:
 *   { success, messages, inserted, errors: [ {row, field, message} ] }
 */
require_once 'core.php';
require_once 'security.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json; charset=utf-8');

/* ── Auth & CSRF ─────────────────────────────────────────────────── */
if (!isset($_SESSION['userId'])) {
    echo json_encode(['success' => false, 'messages' => 'Sesión no iniciada.']);
    exit;
}
if (!verifyCsrfToken()) {
    echo json_encode(['success' => false, 'messages' => 'Token de seguridad inválido.']);
    exit;
}

$userId  = (int)$_SESSION['userId'];
$module  = strtolower(trim($_POST['module'] ?? ''));
$allowed = ['income', 'expense', 'loan', 'debt', 'project', 'asset'];

if (!in_array($module, $allowed, true)) {
    echo json_encode(['success' => false, 'messages' => 'Módulo no reconocido.']);
    exit;
}

/* ── File validation ─────────────────────────────────────────────── */
if (empty($_FILES['file']['tmp_name']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE   => 'El archivo supera el límite del servidor (upload_max_filesize).',
        UPLOAD_ERR_FORM_SIZE  => 'El archivo supera el límite del formulario.',
        UPLOAD_ERR_PARTIAL    => 'El archivo se subió parcialmente.',
        UPLOAD_ERR_NO_FILE    => 'No se seleccionó ningún archivo.',
        UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal del servidor.',
        UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en disco.',
        UPLOAD_ERR_EXTENSION  => 'Una extensión de PHP interrumpió la subida.',
    ];
    $errCode = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
    $errMsg  = $uploadErrors[$errCode] ?? 'Error desconocido al subir el archivo.';
    echo json_encode(['success' => false, 'messages' => $errMsg]);
    exit;
}

$tmpPath  = $_FILES['file']['tmp_name'];
$origName = $_FILES['file']['name'];
$ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
$maxSize  = 5 * 1024 * 1024; // 5 MB

if (!in_array($ext, ['xlsx', 'xls', 'csv'], true)) {
    echo json_encode(['success' => false, 'messages' => 'Formato no permitido. Use .xlsx, .xls o .csv.']);
    exit;
}
if (filesize($tmpPath) > $maxSize) {
    echo json_encode(['success' => false, 'messages' => 'El archivo supera el tamaño máximo permitido (5 MB).']);
    exit;
}

/* ── Load spreadsheet ────────────────────────────────────────────── */
try {
    $reader = IOFactory::createReaderForFile($tmpPath);
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load($tmpPath);
    $sheet       = $spreadsheet->getActiveSheet();
    $rawRows     = $sheet->toArray(null, true, true, false); // 0-indexed numeric array
} catch (\Throwable $e) {
    echo json_encode(['success' => false, 'messages' => 'No se pudo leer el archivo: ' . $e->getMessage()]);
    exit;
}

if (count($rawRows) < 2) {
    echo json_encode(['success' => false, 'messages' => 'El archivo está vacío o solo contiene la cabecera.']);
    exit;
}

/*
 * Detect official downloadTemplate.php layout (Row 1: title banner, Row 2: INSTRUCCIONES, Row 3: column headers)
 * vs plain 1-header spreadsheet/CSV (Row 1: column headers).
 */
$headerRowsToSkip = 1;
$firstCell  = trim((string)($rawRows[0][0] ?? ''));
$secondCell = trim((string)($rawRows[1][0] ?? ''));
if (
    count($rawRows) >= 4 &&
    stripos($firstCell, 'Plantilla de ') === 0 &&
    stripos($secondCell, 'INSTRUCCIONES:') === 0
) {
    $headerRowsToSkip = 3;
}

/* Preserve exact 1-based Excel row numbers while filtering empty rows */
$rows = [];
for ($idx = $headerRowsToSkip, $len = count($rawRows); $idx < $len; $idx++) {
    $r = $rawRows[$idx];
    $hasData = false;
    foreach ($r as $c) {
        if ($c !== null && trim((string)$c) !== '') {
            $hasData = true;
            break;
        }
    }
    if ($hasData) {
        $rows[] = ['excelRow' => $idx + 1, 'cells' => $r];
    }
}

if (count($rows) === 0) {
    echo json_encode(['success' => false, 'messages' => 'El archivo no contiene filas de datos.']);
    exit;
}

if (count($rows) > 500) {
    echo json_encode(['success' => false, 'messages' => 'El archivo supera el límite de 500 filas por importación.']);
    exit;
}

/* ── Helper: resolve date value (Excel serial or string) ─────────── */
function resolveDate($val): ?string
{
    if ($val === null) return null;
    if ($val instanceof \DateTimeInterface) {
        return $val->format('Y-m-d');
    }

    $str = trim((string)$val);
    if ($str === '') return null;

    // Excel numeric date serial (restrict to plausible modern serial dates ~1927-2173
    // so bare numbers like "2026" or amounts are never misinterpreted as 1905 serials)
    if (is_numeric($str)) {
        $num = (float)$str;
        if ($num >= 10000 && $num <= 100000) {
            try {
                $dtObj = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($num);
                return $dtObj->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }
        return null;
    }

    // String date — accept common explicit formats
    foreach (['Y-m-d', 'Y-m-d H:i:s', 'Y/m/d', 'd/m/Y', 'd-m-Y', 'd/m/Y H:i:s', 'd/m/y', 'm/d/Y'] as $fmt) {
        $dt = \DateTime::createFromFormat($fmt, $str);
        if ($dt && $dt->format($fmt) === $str) {
            $y = (int)$dt->format('Y');
            if ($y >= 1900 && $y <= 2100) {
                return $dt->format('Y-m-d');
            }
        }
    }

    // Last resort for English/ISO date strings containing separators
    if (preg_match('/[\/\-]/', $str)) {
        $ts = strtotime($str);
        if ($ts !== false) {
            $y = (int)date('Y', $ts);
            if ($y >= 1900 && $y <= 2100) {
                return date('Y-m-d', $ts);
            }
        }
    }

    return null;
}

/* ── Helper: get cell value safely ──────────────────────────────── */
function cell(array $row, int $idx): string
{
    return isset($row[$idx]) ? trim((string)$row[$idx]) : '';
}

/* ── Helper: parse monetary / numeric cell cleanly ──────────────── */
function parseNumericCell(string $raw): ?float
{
    $clean = trim(str_ireplace(['S/', '$', 'PEN', 'USD', ' '], '', $raw));
    if ($clean === '') return null;

    // Convert "1,500.50" -> "1500.50"
    if (preg_match('/^-?\d{1,3}(,\d{3})+(\.\d+)?$/', $clean)) {
        $clean = str_replace(',', '', $clean);
    } elseif (preg_match('/^-?\d+,\d{1,2}$/', $clean)) {
        // Convert "150,50" -> "150.50"
        $clean = str_replace(',', '.', $clean);
    }

    return is_numeric($clean) ? (float)$clean : null;
}

/* ── Load user accounts ──────────────────────────────────────────── */
function getUserAccounts(mysqli $db, int $userId): array
{
    $map  = [];
    $stmt = $db->prepare("SELECT account_id, LOWER(account_name) AS n FROM accounts WHERE created_by=? AND status=1 AND is_deleted=0");
    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) $map[$r['n']] = (int)$r['account_id'];
        $stmt->close();
    }
    return $map;
}

/* ── Load user financial categories ─────────────────────────────── */
function getUserCategories(mysqli $db, int $userId, string $type): array
{
    $map  = [];
    $stmt = $db->prepare("SELECT category_id, LOWER(category_name) AS n FROM financial_categories WHERE created_by=? AND category_type=? AND status=1 AND is_deleted=0");
    if ($stmt) {
        $stmt->bind_param('is', $userId, $type);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) $map[$r['n']] = (int)$r['category_id'];
        $stmt->close();
    }
    return $map;
}

/* ── Load user projects ──────────────────────────────────────────── */
function getUserProjects(mysqli $db, int $userId): array
{
    $map  = [];
    $stmt = $db->prepare("SELECT project_id, LOWER(project_name) AS n FROM projects WHERE created_by=? AND is_deleted=0");
    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) $map[$r['n']] = (int)$r['project_id'];
        $stmt->close();
    }
    return $map;
}

/* ═══════════════════════════════════════════════════════════════════
   MODULE PROCESSORS
   Each function receives $rows, $userId, $connect
   Returns ['inserted' => int, 'errors' => array]
   ═══════════════════════════════════════════════════════════════════ */

/* ── INCOME ─────────────────────────────────────────────────────── */
function processIncome(array $rows, int $userId, mysqli $db): array
{
    /*
     * Expected columns (0-indexed):
     * 0 concept | 1 amount | 2 account_name | 3 category_name | 4 project_name
     * 5 income_date | 6 status | 7 is_recurring | 8 notes
     */
    $accounts   = getUserAccounts($db, $userId);
    $categories = getUserCategories($db, $userId, 'INCOME');
    $projects   = getUserProjects($db, $userId);

    $validStatuses = ['CONFIRMED', 'PENDING', 'CANCELLED', 'ACTIVO', 'PENDIENTE', 'CANCELADO'];
    $statusMap     = ['ACTIVO' => 'CONFIRMED', 'PENDIENTE' => 'PENDING', 'CANCELADO' => 'CANCELLED'];

    $stmt = $db->prepare(
        "INSERT INTO incomes (concept, account_id, category_id, project_id, income_date, amount, is_recurring, status, notes, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    if (!$stmt) {
        return ['inserted' => 0, 'errors' => [['row' => 1, 'field' => 'BD', 'message' => 'Error preparando consulta: ' . $db->error]]];
    }

    $inserted = 0;
    $errors   = [];

    foreach ($rows as $entry) {
        $rowNum    = $entry['excelRow'];
        $row       = $entry['cells'];
        $rowErrors = [];

        $concept     = cell($row, 0);
        $amountRaw   = cell($row, 1);
        $accountName = strtolower(cell($row, 2));
        $catName     = strtolower(cell($row, 3));
        $projName    = strtolower(cell($row, 4));
        $dateRaw     = $row[5] ?? null;
        $statusRaw   = strtoupper(cell($row, 6)) ?: 'CONFIRMED';
        $isRecurring = in_array(strtolower(cell($row, 7)), ['1', 'si', 'sí', 'yes', 'true'], true) ? 1 : 0;
        $notes       = cell($row, 8);

        if (empty($concept)) $rowErrors[] = ['field' => 'Concepto', 'message' => 'Campo requerido.'];
        $amount = parseNumericCell($amountRaw);
        if ($amount === null || $amount <= 0) $rowErrors[] = ['field' => 'Monto', 'message' => 'Debe ser un número mayor a 0.'];

        $accountId = $accounts[$accountName] ?? null;
        if (!$accountId) $rowErrors[] = ['field' => 'Cuenta', 'message' => "\"" . cell($row, 2) . "\" no encontrada o no le pertenece."];

        $incomeDate = resolveDate($dateRaw);
        if (!$incomeDate) $rowErrors[] = ['field' => 'Fecha', 'message' => 'Formato inválido. Use YYYY-MM-DD o DD/MM/YYYY.'];

        if (!in_array($statusRaw, $validStatuses, true)) $statusRaw = 'CONFIRMED';
        $status = $statusMap[$statusRaw] ?? $statusRaw;

        $categoryId = !empty($catName) ? ($categories[$catName] ?? null) : null;
        $projectId  = !empty($projName) ? ($projects[$projName]  ?? null) : null;

        if ($rowErrors) {
            foreach ($rowErrors as $e) $errors[] = array_merge(['row' => $rowNum], $e);
            continue;
        }

        // Account balance update handled automatically by trg_incomes_after_insert
        try {
            $stmt->bind_param("siiisdissi",
                $concept, $accountId, $categoryId, $projectId,
                $incomeDate, $amount, $isRecurring, $status, $notes, $userId
            );
            $stmt->execute();
            $inserted++;
        } catch (\Throwable $e) {
            $errors[] = ['row' => $rowNum, 'field' => 'BD', 'message' => 'Error al insertar: ' . $e->getMessage()];
        }
    }
    $stmt->close();
    return compact('inserted', 'errors');
}

/* ── EXPENSE ─────────────────────────────────────────────────────── */
function processExpense(array $rows, int $userId, mysqli $db): array
{
    /*
     * 0 concept | 1 amount | 2 account_name | 3 category_name | 4 project_name
     * 5 expense_date | 6 status | 7 notes
     */
    $accounts   = getUserAccounts($db, $userId);
    $categories = getUserCategories($db, $userId, 'EXPENSE');
    $projects   = getUserProjects($db, $userId);

    $stmt = $db->prepare(
        "INSERT INTO expenses (concept, account_id, category_id, project_id, expense_date, amount, status, receipt_image, notes, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, NULL, ?, ?)"
    );
    if (!$stmt) {
        return ['inserted' => 0, 'errors' => [['row' => 1, 'field' => 'BD', 'message' => 'Error preparando consulta: ' . $db->error]]];
    }

    $inserted = 0;
    $errors   = [];

    foreach ($rows as $entry) {
        $rowNum    = $entry['excelRow'];
        $row       = $entry['cells'];
        $rowErrors = [];

        $concept     = cell($row, 0);
        $amountRaw   = cell($row, 1);
        $accountName = strtolower(cell($row, 2));
        $catName     = strtolower(cell($row, 3));
        $projName    = strtolower(cell($row, 4));
        $dateRaw     = $row[5] ?? null;
        $statusRaw   = strtoupper(cell($row, 6)) ?: 'PAGADO';
        $notes       = cell($row, 7);

        if (empty($concept)) $rowErrors[] = ['field' => 'Concepto', 'message' => 'Campo requerido.'];
        $amount = parseNumericCell($amountRaw);
        if ($amount === null || $amount <= 0) $rowErrors[] = ['field' => 'Monto', 'message' => 'Debe ser un número mayor a 0.'];

        $accountId = $accounts[$accountName] ?? null;
        if (!$accountId) $rowErrors[] = ['field' => 'Cuenta', 'message' => "\"" . cell($row, 2) . "\" no encontrada o no le pertenece."];

        $expenseDate = resolveDate($dateRaw);
        if (!$expenseDate) $rowErrors[] = ['field' => 'Fecha', 'message' => 'Formato inválido. Use YYYY-MM-DD o DD/MM/YYYY.'];

        if (!in_array($statusRaw, ['PAGADO', 'PENDIENTE'], true)) $statusRaw = 'PAGADO';

        $categoryId = !empty($catName) ? ($categories[$catName] ?? null) : null;
        $projectId  = !empty($projName) ? ($projects[$projName]  ?? null) : null;

        if ($rowErrors) {
            foreach ($rowErrors as $e) $errors[] = array_merge(['row' => $rowNum], $e);
            continue;
        }

        // Account balance update handled automatically by trg_expenses_after_insert
        try {
            $stmt->bind_param("siiisdssi",
                $concept, $accountId, $categoryId, $projectId,
                $expenseDate, $amount, $statusRaw, $notes, $userId
            );
            $stmt->execute();
            $inserted++;
        } catch (\Throwable $e) {
            $errors[] = ['row' => $rowNum, 'field' => 'BD', 'message' => 'Error al insertar: ' . $e->getMessage()];
        }
    }
    $stmt->close();
    return compact('inserted', 'errors');
}

/* ── LOAN ────────────────────────────────────────────────────────── */
function processLoan(array $rows, int $userId, mysqli $db): array
{
    /*
     * 0 borrower_name | 1 concept | 2 account_name | 3 loan_date | 4 due_date
     * 5 principal_amount | 6 interest_type | 7 interest_value | 8 notes
     */
    $accounts = getUserAccounts($db, $userId);

    $stmt = $db->prepare(
        "INSERT INTO loans (borrower_name, concept, account_id, loan_date, due_date,
                            principal_amount, interest_type, interest_value, total_receivable,
                            amount_repaid, balance_pending, status, notes, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, 'PENDIENTE', ?, ?)"
    );
    if (!$stmt) {
        return ['inserted' => 0, 'errors' => [['row' => 1, 'field' => 'BD', 'message' => 'Error preparando consulta: ' . $db->error]]];
    }

    $inserted = 0;
    $errors   = [];

    foreach ($rows as $entry) {
        $rowNum    = $entry['excelRow'];
        $row       = $entry['cells'];
        $rowErrors = [];

        $borrower    = cell($row, 0);
        $concept     = cell($row, 1);
        $accountName = strtolower(cell($row, 2));
        $loanDateRaw = $row[3] ?? null;
        $dueDateRaw  = $row[4] ?? null;
        $principalRaw= cell($row, 5);
        $intType     = strtoupper(cell($row, 6)) ?: 'NONE';
        $intValueRaw = cell($row, 7);
        $notes       = cell($row, 8);

        if (empty($borrower))  $rowErrors[] = ['field' => 'Prestatario', 'message' => 'Campo requerido.'];
        if (empty($concept))   $rowErrors[] = ['field' => 'Concepto',    'message' => 'Campo requerido.'];

        $accountId = $accounts[$accountName] ?? null;
        if (!$accountId) $rowErrors[] = ['field' => 'Cuenta', 'message' => "\"" . cell($row, 2) . "\" no encontrada o no le pertenece."];

        $loanDate = resolveDate($loanDateRaw);
        if (!$loanDate) $rowErrors[] = ['field' => 'Fecha Préstamo', 'message' => 'Formato inválido. Use YYYY-MM-DD o DD/MM/YYYY.'];

        $dueDate = null;
        if (trim((string)$dueDateRaw) !== '') {
            $dueDate = resolveDate($dueDateRaw);
            if (!$dueDate) $rowErrors[] = ['field' => 'Fecha Vencimiento', 'message' => 'Formato inválido. Use YYYY-MM-DD o DD/MM/YYYY.'];
        }

        $principal = parseNumericCell($principalRaw);
        if ($principal === null || $principal <= 0) $rowErrors[] = ['field' => 'Capital', 'message' => 'Debe ser un número mayor a 0.'];

        $intTypeMap = ['NINGUNO' => 'NONE', 'PORCENTAJE' => 'PERCENTAGE', 'FIJO' => 'FIXED'];
        $intType    = $intTypeMap[$intType] ?? $intType;
        if (!in_array($intType, ['NONE', 'PERCENTAGE', 'FIXED'], true)) $intType = 'NONE';

        $intValueParsed = parseNumericCell($intValueRaw);
        $intValue = ($intValueParsed !== null && $intValueParsed > 0) ? $intValueParsed : null;

        if ($rowErrors) {
            foreach ($rowErrors as $e) $errors[] = array_merge(['row' => $rowNum], $e);
            continue;
        }

        // Compute total_receivable server-side
        if ($intType === 'PERCENTAGE' && $intValue > 0) {
            $total = $principal + ($principal * $intValue / 100);
        } elseif ($intType === 'FIXED' && $intValue > 0) {
            $total = $principal + $intValue;
        } else {
            $total    = $principal;
            $intValue = null;
        }

        // Account balance update handled automatically by trg_loans_after_insert
        try {
            $stmt->bind_param("ssissdsdddsi",
                $borrower, $concept, $accountId, $loanDate, $dueDate,
                $principal, $intType, $intValue, $total, $total, $notes, $userId
            );
            $stmt->execute();
            $inserted++;
        } catch (\Throwable $e) {
            $errors[] = ['row' => $rowNum, 'field' => 'BD', 'message' => 'Error al insertar: ' . $e->getMessage()];
        }
    }
    $stmt->close();
    return compact('inserted', 'errors');
}

/* ── PROJECT ─────────────────────────────────────────────────────── */
function processProject(array $rows, int $userId, mysqli $db): array
{
    /*
     * 0 project_name | 1 client_name | 2 budget | 3 start_date | 4 end_date
     * 5 status | 6 description
     */
    $colCheck = $db->query("SHOW COLUMNS FROM projects LIKE 'created_by'");
    $hasCreatedBy = $colCheck && $colCheck->num_rows > 0;

    $stmt = $hasCreatedBy
        ? $db->prepare(
            "INSERT INTO projects (project_name, client_name, budget, start_date, end_date, status, description, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        )
        : $db->prepare(
            "INSERT INTO projects (project_name, client_name, budget, start_date, end_date, status, description)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

    if (!$stmt) {
        return ['inserted' => 0, 'errors' => [['row' => 1, 'field' => 'BD', 'message' => 'Error preparando consulta: ' . $db->error]]];
    }

    $allowedStatuses = ['ACTIVE', 'ON_HOLD', 'COMPLETED'];

    $inserted = 0;
    $errors   = [];

    foreach ($rows as $entry) {
        $rowNum    = $entry['excelRow'];
        $row       = $entry['cells'];
        $rowErrors = [];

        $name        = cell($row, 0);
        $client      = cell($row, 1);
        $budgetRaw   = cell($row, 2);
        $startRaw    = $row[3] ?? null;
        $endRaw      = $row[4] ?? null;
        $statusRaw   = strtoupper(cell($row, 5)) ?: 'ACTIVE';
        $description = cell($row, 6);

        if (empty($name)) $rowErrors[] = ['field' => 'Nombre Proyecto', 'message' => 'Campo requerido.'];
        $budget = parseNumericCell($budgetRaw);
        if ($budget === null || $budget <= 0) $rowErrors[] = ['field' => 'Presupuesto', 'message' => 'Debe ser un número mayor a 0.'];

        $statusMap = ['ACTIVO' => 'ACTIVE', 'EN_ESPERA' => 'ON_HOLD', 'COMPLETADO' => 'COMPLETED'];
        $status    = $statusMap[$statusRaw] ?? $statusRaw;
        if (!in_array($status, $allowedStatuses, true)) $status = 'ACTIVE';

        $startDate = null;
        if (trim((string)$startRaw) !== '') {
            $startDate = resolveDate($startRaw);
            if (!$startDate) $rowErrors[] = ['field' => 'Fecha Inicio', 'message' => 'Formato inválido. Use YYYY-MM-DD o DD/MM/YYYY.'];
        }

        $endDate = null;
        if (trim((string)$endRaw) !== '') {
            $endDate = resolveDate($endRaw);
            if (!$endDate) $rowErrors[] = ['field' => 'Fecha Fin', 'message' => 'Formato inválido. Use YYYY-MM-DD o DD/MM/YYYY.'];
        }

        if ($rowErrors) {
            foreach ($rowErrors as $e) $errors[] = array_merge(['row' => $rowNum], $e);
            continue;
        }

        try {
            if ($hasCreatedBy) {
                $stmt->bind_param("ssdssssi", $name, $client, $budget, $startDate, $endDate, $status, $description, $userId);
            } else {
                $stmt->bind_param("ssdssss", $name, $client, $budget, $startDate, $endDate, $status, $description);
            }
            $stmt->execute();
            $inserted++;
        } catch (\Throwable $e) {
            $errors[] = ['row' => $rowNum, 'field' => 'BD', 'message' => 'Error al insertar: ' . $e->getMessage()];
        }
    }
    $stmt->close();
    return compact('inserted', 'errors');
}

/* ── ASSET ───────────────────────────────────────────────────────── */
function processAsset(array $rows, int $userId, mysqli $db): array
{
    /*
     * 0 asset_name | 1 category | 2 estimated_value | 3 purchase_price
     * 4 purchase_date | 5 document_number | 6 location_or_custody | 7 status | 8 notes
     */
    $allowedCats     = ['VEHICULO', 'INMUEBLE', 'DOCUMENTO_FIDUCIARIO', 'EQUIPO_TECNOLOGICO', 'OTRO'];
    $allowedStatuses = ['ACTIVO', 'EN_GARANTIA', 'VENDIDO', 'DADO_DE_BAJA'];

    $stmt = $db->prepare(
        "INSERT INTO assets (asset_name, category, estimated_value, purchase_date, purchase_price,
                             document_number, location_or_custody, status, image_path, notes, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?)"
    );
    if (!$stmt) {
        return ['inserted' => 0, 'errors' => [['row' => 1, 'field' => 'BD', 'message' => 'Error preparando consulta: ' . $db->error]]];
    }

    $inserted = 0;
    $errors   = [];

    foreach ($rows as $entry) {
        $rowNum    = $entry['excelRow'];
        $row       = $entry['cells'];
        $rowErrors = [];

        $assetName   = cell($row, 0);
        $category    = strtoupper(cell($row, 1));
        $estValRaw   = cell($row, 2);
        $purchPrRaw  = cell($row, 3);
        $purchDateRaw= $row[4] ?? null;
        $docNumber   = cell($row, 5);
        $location    = cell($row, 6);
        $statusRaw   = strtoupper(cell($row, 7)) ?: 'ACTIVO';
        $notes       = cell($row, 8);

        $catMap = [
            'VEHICULO'             => 'VEHICULO',
            'VEHÍCULO'             => 'VEHICULO',
            'INMUEBLE'             => 'INMUEBLE',
            'DOCUMENTO_FIDUCIARIO' => 'DOCUMENTO_FIDUCIARIO',
            'DOCUMENTO FIDUCIARIO' => 'DOCUMENTO_FIDUCIARIO',
            'EQUIPO_TECNOLOGICO'   => 'EQUIPO_TECNOLOGICO',
            'EQUIPO TECNOLOGICO'   => 'EQUIPO_TECNOLOGICO',
            'TECNOLOGICO'          => 'EQUIPO_TECNOLOGICO',
            'OTRO'                 => 'OTRO',
        ];
        $category = $catMap[$category] ?? $category;

        if (empty($assetName)) $rowErrors[] = ['field' => 'Nombre Activo', 'message' => 'Campo requerido.'];
        if (!in_array($category, $allowedCats, true))
            $rowErrors[] = ['field' => 'Categoría', 'message' => "\"$category\" no es válida. Use: " . implode(', ', $allowedCats)];

        $estVal = parseNumericCell($estValRaw);
        if ($estVal === null || $estVal <= 0) $rowErrors[] = ['field' => 'Valor Estimado', 'message' => 'Debe ser un número mayor a 0.'];

        $purchParsed = parseNumericCell($purchPrRaw);
        $purchPrice  = ($purchParsed !== null && $purchParsed > 0) ? $purchParsed : null;

        $purchDate = null;
        if (trim((string)$purchDateRaw) !== '') {
            $purchDate = resolveDate($purchDateRaw);
            if (!$purchDate) $rowErrors[] = ['field' => 'Fecha Adquisición', 'message' => 'Formato inválido. Use YYYY-MM-DD o DD/MM/YYYY.'];
        }

        $statusNorm = strtoupper(str_replace(' ', '_', $statusRaw));
        if (!in_array($statusNorm, $allowedStatuses, true)) $statusNorm = 'ACTIVO';

        if ($rowErrors) {
            foreach ($rowErrors as $e) $errors[] = array_merge(['row' => $rowNum], $e);
            continue;
        }

        try {
            $stmt->bind_param("ssdsdssssi",
                $assetName, $category, $estVal, $purchDate, $purchPrice,
                $docNumber, $location, $statusNorm, $notes, $userId
            );
            $stmt->execute();
            $inserted++;
        } catch (\Throwable $e) {
            $errors[] = ['row' => $rowNum, 'field' => 'BD', 'message' => 'Error al insertar: ' . $e->getMessage()];
        }
    }
    $stmt->close();
    return compact('inserted', 'errors');
}

/* ── DEBT (Préstamos Recibidos) ──────────────────────────────────── */
function processDebt(array $rows, int $userId, mysqli $db): array
{
    /*
     * 0 lender_name | 1 lender_type | 2 concept | 3 account_name | 4 debt_date
     * 5 due_date | 6 principal_amount | 7 interest_type | 8 interest_value | 9 notes
     */
    $accounts = getUserAccounts($db, $userId);

    $stmt = $db->prepare(
        "INSERT INTO debts (lender_name, lender_type, concept, account_id, debt_date, due_date,
                            principal_amount, interest_type, interest_value, total_payable,
                            amount_paid, balance_pending, status, notes, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, 'PENDIENTE', ?, ?)"
    );
    if (!$stmt) {
        return ['inserted' => 0, 'errors' => [['row' => 1, 'field' => 'BD', 'message' => 'Error preparando consulta: ' . $db->error]]];
    }

    $inserted = 0;
    $errors   = [];

    foreach ($rows as $entry) {
        $rowNum    = $entry['excelRow'];
        $row       = $entry['cells'];
        $rowErrors = [];

        $lender      = cell($row, 0);
        $lenderType  = strtoupper(cell($row, 1)) ?: 'PERSONA';
        $concept     = cell($row, 2);
        $accountName = strtolower(cell($row, 3));
        $debtDateRaw = $row[4] ?? null;
        $dueDateRaw  = $row[5] ?? null;
        $principalRaw= cell($row, 6);
        $intType     = strtoupper(cell($row, 7)) ?: 'NONE';
        $intValueRaw = cell($row, 8);
        $notes       = cell($row, 9);

        if (empty($lender))  $rowErrors[] = ['field' => 'Acreedor / Entidad', 'message' => 'Campo requerido.'];
        if (empty($concept)) $rowErrors[] = ['field' => 'Concepto', 'message' => 'Campo requerido.'];

        if (!in_array($lenderType, ['PERSONA', 'EMPRESA', 'ENTIDAD_FINANCIERA'], true)) {
            $lenderType = 'PERSONA';
        }

        $accountId = $accounts[$accountName] ?? null;
        if (!$accountId) $rowErrors[] = ['field' => 'Cuenta Destino', 'message' => "\"" . cell($row, 3) . "\" no encontrada o no le pertenece."];

        $debtDate = resolveDate($debtDateRaw);
        if (!$debtDate) $rowErrors[] = ['field' => 'Fecha Préstamo', 'message' => 'Formato inválido. Use YYYY-MM-DD o DD/MM/YYYY.'];

        $dueDate = null;
        if (trim((string)$dueDateRaw) !== '') {
            $dueDate = resolveDate($dueDateRaw);
            if (!$dueDate) $rowErrors[] = ['field' => 'Fecha Vencimiento', 'message' => 'Formato inválido. Use YYYY-MM-DD o DD/MM/YYYY.'];
        }

        $principal = parseNumericCell($principalRaw);
        if ($principal === null || $principal <= 0) $rowErrors[] = ['field' => 'Capital', 'message' => 'Debe ser un número mayor a 0.'];

        $intTypeMap = ['NINGUNO' => 'NONE', 'PORCENTAJE' => 'PERCENTAGE', 'FIJO' => 'FIXED'];
        $intType    = $intTypeMap[$intType] ?? $intType;
        if (!in_array($intType, ['NONE', 'PERCENTAGE', 'FIXED'], true)) $intType = 'NONE';

        $intValueParsed = parseNumericCell($intValueRaw);
        $intValue = ($intValueParsed !== null && $intValueParsed > 0) ? $intValueParsed : null;

        if ($rowErrors) {
            foreach ($rowErrors as $e) $errors[] = array_merge(['row' => $rowNum], $e);
            continue;
        }

        if ($intType === 'PERCENTAGE' && $intValue > 0) {
            $total = $principal + ($principal * $intValue / 100);
        } elseif ($intType === 'FIXED' && $intValue > 0) {
            $total = $principal + $intValue;
        } else {
            $total    = $principal;
            $intValue = null;
        }

        // Account balance update handled automatically by trg_debts_after_insert
        try {
            $stmt->bind_param("sssissdsdddsi",
                $lender, $lenderType, $concept, $accountId, $debtDate, $dueDate,
                $principal, $intType, $intValue, $total, $total, $notes, $userId
            );
            $stmt->execute();
            $inserted++;
        } catch (\Throwable $e) {
            $errors[] = ['row' => $rowNum, 'field' => 'BD', 'message' => 'Error al insertar: ' . $e->getMessage()];
        }
    }
    $stmt->close();
    return compact('inserted', 'errors');
}

/* ── Dispatch ────────────────────────────────────────────────────── */
switch ($module) {
    case 'income':  $result = processIncome($rows, $userId, $connect);  break;
    case 'expense': $result = processExpense($rows, $userId, $connect); break;
    case 'loan':    $result = processLoan($rows, $userId, $connect);    break;
    case 'debt':    $result = processDebt($rows, $userId, $connect);    break;
    case 'project': $result = processProject($rows, $userId, $connect); break;
    case 'asset':   $result = processAsset($rows, $userId, $connect);   break;
    default:
        echo json_encode(['success' => false, 'messages' => 'Módulo no reconocido.']);
        exit;
}

$inserted = $result['inserted'];
$errors   = $result['errors'];
$total    = count($rows);

if ($inserted === 0 && count($errors) > 0) {
    $msg = "No se importó ningún registro. Se encontraron " . count($errors) . " error(es).";
    echo json_encode(['success' => false, 'messages' => $msg, 'inserted' => 0, 'errors' => $errors]);
} elseif ($inserted > 0 && count($errors) > 0) {
    $msg = "Se importaron $inserted de $total registros. " . count($errors) . " fila(s) con error fueron omitidas.";
    echo json_encode(['success' => true, 'messages' => $msg, 'inserted' => $inserted, 'errors' => $errors]);
} else {
    echo json_encode(['success' => true, 'messages' => "Se importaron $inserted registro(s) exitosamente.", 'inserted' => $inserted, 'errors' => []]);
}
