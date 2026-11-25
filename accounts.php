<?php
if (!isset($_GET['classic'])) {
    header('Location: app.php#/accounts');
    exit();
}
require_once 'includes/header.php';
?>
<?php require_once 'modal/accountModal.php'; ?>

<div class="row">
  <div class="col-xs-12">
    <ol class="breadcrumb">
      <li><a href="dashboard.php">Inicio</a></li>
      <li class="active">Cuentas y Categorías</li>
    </ol>

    <div class="accounts-messages"></div>

    <!-- Panel 1: Accounts (Bancos, Billeteras, Efectivo) -->
    <div class="panel panel-default">
      <div class="panel-heading panel-heading-flex" style="display: flex; justify-content: space-between; align-items: center; min-height: 52px; padding: 8px 15px;">
        <div class="page-heading" style="margin: 0; font-size: 16px; font-weight: 600; display: flex; align-items: center;">
          <i class="glyphicon glyphicon-credit-card text-primary" style="margin-right: 8px;"></i> Mis Cuentas Financieras y Efectivo
        </div>
        <button class="btn btn-primary" data-toggle="modal" data-target="#addAccountModal" style="margin: 0; display: inline-flex; align-items: center; gap: 5px;">
          <i class="glyphicon glyphicon-plus-sign"></i> Nueva Cuenta
        </button>
      </div>
      <div class="panel-body">
        <div class="table-responsive">
          <table class="table table-hover" id="manageAccountsTable" style="width:100%;">
            <thead>
              <tr>
                <th style="width:4%;">#</th>
                <th>Nombre de Cuenta</th>
                <th>Tipo</th>
                <th>N° Cuenta / Ref.</th>
                <th>Moneda</th>
                <th>Saldo Inicial</th>
                <th>Saldo Actual</th>
                <th>Estado</th>
                <th style="width:12%;">Acción</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>
    </div>

    <!-- Panel 2: Financial Categories -->
    <div class="panel panel-default">
      <div class="panel-heading panel-heading-flex" style="display: flex; justify-content: space-between; align-items: center; min-height: 52px; padding: 8px 15px;">
        <div class="page-heading" style="margin: 0; font-size: 16px; font-weight: 600; display: flex; align-items: center;">
          <i class="glyphicon glyphicon-tags text-info" style="margin-right: 8px;"></i> Mis Categorías de Ingresos y Gastos
        </div>
        <button class="btn btn-info" data-toggle="modal" data-target="#addFinCategoryModal" style="margin: 0; display: inline-flex; align-items: center; gap: 5px;">
          <i class="glyphicon glyphicon-plus-sign"></i> Nueva Categoría
        </button>
      </div>
      <div class="panel-body">
        <div class="table-responsive">
          <table class="table table-hover" id="manageFinCategoriesTable" style="width:100%;">
            <thead>
              <tr>
                <th style="width:4%;">#</th>
                <th>Nombre de Categoría</th>
                <th>Tipo</th>
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

<script src="custom/js/accounts.js"></script>
<?php require_once 'includes/footer.php'; ?>
