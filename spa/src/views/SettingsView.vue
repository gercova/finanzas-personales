<template>
  <div>
    <ol class="breadcrumb">
      <li><a href="#" @click.prevent="$router.push('/dashboard')">Inicio</a></li>
      <li class="active">Configuración</li>
    </ol>
    <div class="panel panel-default">
      <div class="panel-heading">
        <i class="glyphicon glyphicon-wrench"></i> <strong>Configuración</strong>
      </div>
      <div class="panel-body text-center" style="padding:40px;">
        <p class="text-muted">La configuración completa está disponible en la vista de ajustes.</p>
        <a href="setting.php" class="btn btn-default">
          <i class="glyphicon glyphicon-share-alt"></i> Ir a Configuración
        </a>
      </div>
    </div>
  </div>
</template>
