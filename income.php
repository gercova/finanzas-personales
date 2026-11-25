<?php require_once 'includes/header.php'; ?>
<?php require_once 'modal/incomeModal.php'; ?>

<?php
// ── Stat Cards (SARGable date range & strict per-user scoping) ────────────────
$userId       = (int)$_SESSION['userId'];
$currentMonth = date('Y-m');
$currentYear  = (int)date('Y');
list($monthStart, $monthEnd) = getMonthDateBounds($currentMonth);
list($yearStart, $yearEnd)   = getYearDateBounds($currentYear);

$stmtMes      = $connect->prepare("SELECT COALESCE(SUM(amount),0) AS total FROM incomes WHERE created_by=? AND is_deleted=0 AND status<>'CANCELLED' AND income_date>=? AND income_date<?");
$totalMes     = 0;
if ($stmtMes) {
    $stmtMes->bind_param("iss", $userId, $monthStart, $monthEnd);
    $stmtMes->execute();
    $totalMes = (float)$stmtMes->get_result()->fetch_assoc()['total'];
    $stmtMes->close();
}

$stmtYear = $connect->prepare("SELECT COALESCE(SUM(amount),0) AS total FROM incomes WHERE created_by=? AND is_deleted=0 AND status<>'CANCELLED' AND income_date>=? AND income_date<?");
$totalYear = 0;
if ($stmtYear) {
    $stmtYear->bind_param("iss", $userId, $yearStart, $yearEnd);
    $stmtYear->execute();
    $totalYear = (float)$stmtYear->get_result()->fetch_assoc()['total'];
    $stmtYear->close();
}

$stmtRec = $connect->prepare("SELECT COUNT(*) AS cnt FROM incomes WHERE created_by=? AND is_deleted=0 AND status='CONFIRMED' AND is_recurring=1");
$totalRecurring = 0;
if ($stmtRec) {
  $stmtRec->bind_param("i", $userId);
  $stmtRec->execute();
  $totalRecurring = (int)$stmtRec->get_result()->fetch_assoc()['cnt'];
  $stmtRec->close();
}

// Accounts for select (owned by user)
$stmtAcc = $connect->prepare("SELECT account_id AS id, account_name, account_type FROM accounts WHERE created_by=? AND status=1 AND is_deleted=0 ORDER BY account_name");
$accounts = [];
if ($stmtAcc) {
  $stmtAcc->bind_param("i", $userId);
  $stmtAcc->execute();
  $resAcc = $stmtAcc->get_result();
  while ($row = $resAcc->fetch_assoc()) $accounts[] = $row;
  $stmtAcc->close();
}

// Financial categories (owned by user)
$stmtCat = $connect->prepare("SELECT category_id AS id, category_name FROM financial_categories WHERE created_by=? AND category_type='INCOME' AND status=1 AND is_deleted=0 ORDER BY category_name");
$finCategories = [];
if ($stmtCat) {
  $stmtCat->bind_param("i", $userId);
  $stmtCat->execute();
  $resCat = $stmtCat->get_result();
  while ($row = $resCat->fetch_assoc()) $finCategories[] = $row;
  $stmtCat->close();
}

// Projects (owned by user)
$stmtProj = $connect->prepare("SELECT project_id AS id, project_name FROM projects WHERE created_by=? AND status='ACTIVE' AND is_deleted=0 ORDER BY project_name");
$projects = [];
if ($stmtProj) {
  $stmtProj->bind_param("i", $userId);
  $stmtProj->execute();
  $resProj = $stmtProj->get_result();
  while ($row = $resProj->fetch_assoc()) $projects[] = $row;
  $stmtProj->close();
}
?>

<div class="row">
  <div class="col-xs-12">
    <ol class="breadcrumb">
      <li><a href="dashboard.php">Inicio</a></li>
      <li class="active">Ingresos</li>
    </ol>

    <!-- Stat Cards -->
    <div class="row" style="margin-bottom:20px;">
      <div class="col-xs-12 col-sm-4">
        <div class="panel panel-success">
          <div class="panel-heading"><i class="glyphicon glyphicon-calendar"></i> Ingresos del Mes</div>
          <div class="panel-body text-center">
            <h3 style="margin:0;font-weight:700;" class="text-success">S/ <?php echo number_format($totalMes, 2); ?></h3>
            <small class="text-muted"><?php echo date('F Y'); ?></small>
          </div>
        </div>
      </div>
      <div class="col-xs-12 col-sm-4">
        <div class="panel panel-info">
          <div class="panel-heading"><i class="glyphicon glyphicon-stats"></i> Ingresos del Año</div>
          <div class="panel-body text-center">
            <h3 style="margin:0;font-weight:700;" class="text-info">S/ <?php echo number_format($totalYear, 2); ?></h3>
            <small class="text-muted"><?php echo $currentYear; ?></small>
          </div>
        </div>
      </div>
      <div class="col-xs-12 col-sm-4">
        <div class="panel panel-warning">
          <div class="panel-heading"><i class="glyphicon glyphicon-repeat"></i> Ingresos Recurrentes</div>
          <div class="panel-body text-center">
            <h3 style="margin:0;font-weight:700;" class="text-warning"><?php echo $totalRecurring; ?></h3>
            <small class="text-muted">activos</small>
          </div>
        </div>
      </div>
    </div><!-- /stat cards -->

    <div class="panel panel-default">
      <div class="panel-heading panel-heading-flex" style="display: flex; justify-content: space-between; align-items: center; min-height: 52px; padding: 8px 15px;">
        <div class="page-heading" style="margin: 0; font-size: 16px; font-weight: 600; display: flex; align-items: center;">
          <i class="glyphicon glyphicon-arrow-up text-success" style="margin-right: 8px;"></i> Gestión de Ingresos
        </div>
        <div style="display:inline-flex;gap:6px;align-items:center;">
          <button class="btn btn-default btn-sm" onclick="openImportModal('income', function(){ incomeTableReload && incomeTableReload(); })" title="Importar desde Excel/CSV">
            <i class="glyphicon glyphicon-upload"></i> Importar
          </button>
          <button class="btn btn-success" data-toggle="modal" data-target="#addIncomeModal" style="margin: 0; display: inline-flex; align-items: center; gap: 5px;">
            <i class="glyphicon glyphicon-plus-sign"></i> Registrar Ingreso
          </button>
        </div>
      </div>
      <div class="panel-body">
        <div class="remove-messages"></div>
        <div class="table-responsive">
          <table class="table table-hover" id="manageIncomeTable" style="width:100%;">
            <thead>
              <tr>
                <th style="width:4%;">#</th>
                <th>Concepto</th>
                <th>Cuenta</th>
                <th>Categoría</th>
                <th>Proyecto</th>
                <th>Fecha</th>
                <th>Monto</th>
                <th>Estado</th>
                <th style="width:12%;">Acción</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
var incomeAccounts    = <?php echo json_encode($accounts); ?>;
var incomeCategories  = <?php echo json_encode($finCategories); ?>;
var incomeProjects    = <?php echo json_encode($projects); ?>;
var incomeTableReload = null; // set by income.js after DataTable init
</script>
<script src="custom/js/income.js"></script>
<script src="custom/js/bulkImport.js"></script>
<?php require_once 'modal/importModal.php'; ?>
<?php require_once 'includes/footer.php'; ?>
