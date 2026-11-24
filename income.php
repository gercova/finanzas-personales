<?php require_once 'includes/header.php'; ?>
<?php require_once 'modal/incomeModal.php'; ?>

<?php
// ── Stat Cards ────────────────────────────────────────────────────────────────
$currentMonth = date('Y-m');
$currentYear  = date('Y');

$stmtMes = $connect->prepare(
    "SELECT COALESCE(SUM(amount),0) AS total FROM incomes WHERE DATE_FORMAT(income_date,'%Y-%m')=? AND created_by=?"
);
$totalMes = 0;
if ($stmtMes) {
    $stmtMes->bind_param("si", $currentMonth, $_SESSION['userId']);
    $stmtMes->execute();
    $totalMes = (float)$stmtMes->get_result()->fetch_assoc()['total'];
    $stmtMes->close();
}

$stmtYear = $connect->prepare(
    "SELECT COALESCE(SUM(amount),0) AS total FROM incomes WHERE YEAR(income_date)=? AND created_by=?"
);
$totalYear = 0;
if ($stmtYear) {
    $stmtYear->bind_param("ii", $currentYear, $_SESSION['userId']);
    $stmtYear->execute();
    $totalYear = (float)$stmtYear->get_result()->fetch_assoc()['total'];
    $stmtYear->close();
}

$stmtRec = $connect->prepare(
    "SELECT COUNT(*) AS cnt FROM incomes WHERE is_recurring=1 AND status='CONFIRMED' AND created_by=?"
);
$totalRecurring = 0;
if ($stmtRec) {
    $stmtRec->bind_param("i", $_SESSION['userId']);
    $stmtRec->execute();
    $totalRecurring = (int)$stmtRec->get_result()->fetch_assoc()['cnt'];
    $stmtRec->close();
}

// Accounts for select
$stmtAcc = $connect->prepare("SELECT account_id AS id, account_name, account_type FROM accounts WHERE status=1 ORDER BY account_name");
$accounts = [];
if ($stmtAcc) {
    $stmtAcc->execute();
    $resAcc = $stmtAcc->get_result();
    while ($row = $resAcc->fetch_assoc()) $accounts[] = $row;
    $stmtAcc->close();
}

// Financial categories
$stmtCat = $connect->prepare("SELECT category_id AS id, category_name FROM financial_categories WHERE category_type = 'INCOME' AND status=1 ORDER BY category_name");
$finCategories = [];
if ($stmtCat) {
    $stmtCat->execute();
    $resCat = $stmtCat->get_result();
    while ($row = $resCat->fetch_assoc()) $finCategories[] = $row;
    $stmtCat->close();
}

// Projects
$stmtProj = $connect->prepare("SELECT project_id AS id, project_name FROM projects WHERE status='ACTIVE' ORDER BY project_name");
$projects = [];
if ($stmtProj) {
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
      <div class="panel-heading">
        <div class="page-heading">
          <i class="glyphicon glyphicon-arrow-up text-success"></i> Gestión de Ingresos
        </div>
      </div>
      <div class="panel-body">
        <div class="remove-messages"></div>
        <div class="div-action pull pull-right" style="padding-bottom:20px;">
          <button class="btn btn-success" data-toggle="modal" data-target="#addIncomeModal">
            <i class="glyphicon glyphicon-plus-sign"></i> Registrar Ingreso
          </button>
        </div>
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
</script>
<script src="custom/js/income.js"></script>
<?php require_once 'includes/footer.php'; ?>
