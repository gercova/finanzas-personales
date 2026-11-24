<?php require_once 'includes/header.php'; ?>

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
            <div class="panel-heading">
                <h3 class="panel-title"><i class="glyphicon glyphicon-briefcase"></i> Gestión de Proyectos</h3>
                <div class="pull-right" style="margin-top:-5px;">
                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addProjectModal">
                        <i class="glyphicon glyphicon-plus"></i> Nuevo Proyecto
                    </button>
                </div>
                <div class="clearfix"></div>
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

<script src="assests/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="assests/plugins/datatables/extensions/Responsive/js/dataTables.responsive.min.js"></script>
<script src="custom/js/projects.js"></script>
<script>$('#navProjects').addClass('active');</script>

<?php require_once 'includes/footer.php'; ?>
