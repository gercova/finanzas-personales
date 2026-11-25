<template>
  <div>
    <ol class="breadcrumb">
      <li><a href="#" @click.prevent="$router.push('/dashboard')">Inicio</a></li>
      <li class="active">Órdenes</li>
    </ol>

    <div class="panel panel-default">
      <DataTable
        title="Gestión de órdenes"
        icon="glyphicon-shopping-cart text-primary"
        :headers="['#', 'Fecha de la orden', 'Nombre del cliente', 'Teléfono', 'Total de ítems', 'Estado de pago', 'Opciones']"
        :store="store"
      >
        <template #actions>
          <a href="orders.php?o=add" class="btn btn-default button1">
            <i class="glyphicon glyphicon-plus-sign"></i> Agregar orden
          </a>
        </template>
      </DataTable>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useOrderStore } from '@/stores/useOrderStore'
import DataTable from '@/components/DataTable.vue'

const store = useOrderStore()

onMounted(async () => {
  await store.load()
})
</script>
