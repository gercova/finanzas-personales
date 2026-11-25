<template>
  <div>
    <ol class="breadcrumb">
      <li><a href="#" @click.prevent="$router.push('/dashboard')">Inicio</a></li>
      <li class="active">Reportes</li>
    </ol>
    <div class="panel panel-default">
      <div class="panel-heading">
        <i class="glyphicon glyphicon-stats"></i> <strong>Reportes Financieros</strong>
      </div>
      <div class="panel-body text-center" style="padding:40px;">
        <p class="text-muted">Los reportes avanzados están disponibles en la vista completa.</p>
        <a href="finance_report.php" class="btn btn-info">
          <i class="glyphicon glyphicon-share-alt"></i> Ver Reportes Completos
        </a>
      </div>
    </div>
  </div>
</template>
