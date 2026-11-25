<?php
if (!isset($_GET['classic'])) {
    header('Location: app.php#/projects');
    exit();
}
require_once 'includes/header.php';
?>

<!-- Page header -->
<div class="row">
    <div class="col-xs-12">
        <ol class="breadcrumb">
            <li><a href="dashboard.php">Inicio</a></li>
            <li class="active">Proyectos</li>
        </ol>
    </div>
</div>

<!-- Table panel -->
<div class="row">
    <div class="col-xs-12">
        <div class="panel panel-default">
            <div class="panel-heading panel-heading-flex" style="display: flex; justify-content: space-between; align-items: center; min-height: 52px; padding: 8px 15px;">
                <div class="page-heading" style="margin: 0; font-size: 16px; font-weight: 600; display: flex; align-items: center;">
                    <i class="glyphicon glyphicon-briefcase text-primary" style="margin-right: 8px;"></i> Gestión de Proyectos
                </div>
                <div style="display:inline-flex;gap:6px;align-items:center;">
                    <button type="button" class="btn btn-default btn-sm" onclick="openImportModal('project', function(){ projectTableReload && projectTableReload(); })" title="Importar desde Excel/CSV">
                        <i class="glyphicon glyphicon-upload"></i> Importar
                    </button>
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addProjectModal" style="margin: 0; display: inline-flex; align-items: center; gap: 5px;">
                        <i class="glyphicon glyphicon-plus-sign"></i> Nuevo Proyecto
                    </button>
                </div>
            </div>
            <div class="panel-body table-responsive">
                <table id="projectsTable" class="table table-striped table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Proyecto</th>
                            <th>Cliente</th>
                            <th>Presupuesto</th>
                            <th>Inicio</th>
                            <th>Fin</th>
                            <th>Estado</th>
                            <th>Total Ingresos</th>
                            <th>Total Gastos</th>
                            <th>Pronóstico</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'modal/projectModal.php'; ?>
<?php include 'modal/importModal.php'; ?>
<script>var projectTableReload = null;</script>
<script src="assests/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="assests/plugins/datatables/extensions/Responsive/js/dataTables.responsive.min.js"></script>
<script src="custom/js/projects.js"></script>
<script src="custom/js/bulkImport.js"></script>
<script>$('#navProjects').addClass('active');</script>

<?php require_once 'includes/footer.php'; ?>
