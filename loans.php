<?php
require_once 'includes/header.php';

$userId = (int)$_SESSION['userId'];

// Load loans KPIs from optimized view v_user_financial_summary (1 query instead of 3)
$totalPrestado  = 0.0;
$totalCobrado   = 0.0;
$totalPendiente = 0.0;

$stmtSum = $connect->prepare(
    "SELECT prestamos_principal_activo, prestamos_cobrado, prestamos_pendientes
     FROM v_user_financial_summary WHERE user_id = ?"
);
if ($stmtSum) {
    $stmtSum->bind_param("i", $userId);
    $stmtSum->execute();
    if ($rowSum = $stmtSum->get_result()->fetch_assoc()) {
        $totalPrestado  = (float)$rowSum['prestamos_principal_activo'];
        $totalCobrado   = (float)$rowSum['prestamos_cobrado'];
        $totalPendiente = (float)$rowSum['prestamos_pendientes'];
    }
    $stmtSum->close();
}

// Accounts strictly owned by current user
$loanAccounts = [];
$stmtLA = $connect->prepare("SELECT account_id AS id, account_name, account_type FROM accounts WHERE created_by=? AND status=1 AND is_deleted=0 ORDER BY account_name");
if ($stmtLA) { $stmtLA->bind_param("i", $userId); $stmtLA->execute(); $r=$stmtLA->get_result(); while($row=$r->fetch_assoc()) $loanAccounts[]=$row; $stmtLA->close(); }
?>
<?php require_once 'modal/loanModal.php'; ?>

<div class="row">
  <div class="col-xs-12">
    <ol class="breadcrumb">
      <li><a href="dashboard.php">Inicio</a></li>
      <li class="active">Préstamos</li>
    </ol>

    <!-- Stat Cards -->
    <div class="row" style="margin-bottom:20px;">
      <div class="col-xs-12 col-sm-4">
        <div class="panel panel-danger">
          <div class="panel-heading"><i class="glyphicon glyphicon-share-alt"></i> Total Prestado</div>
          <div class="panel-body text-center">
            <h3 style="margin:0;font-weight:700;" class="text-danger">S/ <?php echo number_format($totalPrestado, 2); ?></h3>
            <small class="text-muted">préstamos activos</small>
          </div>
        </div>
      </div>
      <div class="col-xs-12 col-sm-4">
        <div class="panel panel-success">
          <div class="panel-heading"><i class="glyphicon glyphicon-ok-sign"></i> Total Cobrado</div>
          <div class="panel-body text-center">
            <h3 style="margin:0;font-weight:700;" class="text-success">S/ <?php echo number_format($totalCobrado, 2); ?></h3>
            <small class="text-muted">recuperado</small>
          </div>
        </div>
      </div>
      <div class="col-xs-12 col-sm-4">
        <div class="panel panel-warning">
          <div class="panel-heading"><i class="glyphicon glyphicon-time"></i> Total Pendiente</div>
          <div class="panel-body text-center">
            <h3 style="margin:0;font-weight:700;" class="text-warning">S/ <?php echo number_format($totalPendiente, 2); ?></h3>
            <small class="text-muted">por cobrar</small>
          </div>
        </div>
      </div>
    </div>

    <!-- Legend -->
    <div style="margin-bottom:12px;">
      <span class="label" style="background:#e74c3c;padding:4px 10px;">PENDIENTE</span>&nbsp;
      <span class="label" style="background:#f39c12;padding:4px 10px;">PARCIAL</span>&nbsp;
      <span class="label" style="background:#8e44ad;padding:4px 10px;">PAGADO</span>
    </div>

    <div class="panel panel-default">
      <div class="panel-heading panel-heading-flex" style="display: flex; justify-content: space-between; align-items: center; min-height: 52px; padding: 8px 15px;">
        <div class="page-heading" style="margin: 0; font-size: 16px; font-weight: 600; display: flex; align-items: center;">
          <i class="glyphicon glyphicon-share-alt text-danger" style="margin-right: 8px;"></i> Gestión de Préstamos
        </div>
        <div style="display:inline-flex;gap:6px;align-items:center;">
          <button class="btn btn-default btn-sm" onclick="openImportModal('loan', function(){ loanTableReload && loanTableReload(); })" title="Importar desde Excel/CSV">
            <i class="glyphicon glyphicon-upload"></i> Importar
          </button>
          <button class="btn btn-danger" data-toggle="modal" data-target="#addLoanModal" style="margin: 0; display: inline-flex; align-items: center; gap: 5px;">
            <i class="glyphicon glyphicon-plus-sign"></i> Registrar Préstamo
          </button>
        </div>
      </div>
      <div class="panel-body">
        <div class="remove-messages"></div>
        <div class="table-responsive">
          <table class="table table-hover" id="manageLoanTable" style="width:100%;">
            <thead>
              <tr>
                <th style="width:4%;">#</th>
                <th>Prestatario</th>
                <th>Concepto</th>
                <th>Cuenta</th>
                <th>Fecha Préstamo</th>
                <th>Vencimiento</th>
                <th>Capital</th>
                <th>Interés</th>
                <th>Total a Cobrar</th>
                <th>Cobrado</th>
                <th>Pendiente</th>
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
var loanAccounts = <?php echo json_encode($loanAccounts); ?>;
var loanTableReload = null;
</script>
<script src="custom/js/loans.js"></script>
<script src="custom/js/bulkImport.js"></script>
<?php require_once 'modal/importModal.php'; ?>
<?php require_once 'includes/footer.php'; ?>
