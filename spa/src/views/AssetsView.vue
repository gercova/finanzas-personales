<template>
  <div>
    <ol class="breadcrumb">
      <li><a href="#" @click.prevent="$router.push('/dashboard')">Inicio</a></li>
      <li class="active">Activos Valiosos</li>
    </ol>

    <!-- Stat cards matching assets.php -->
    <div class="row" style="margin-bottom:20px;">
      <div class="col-xs-12 col-sm-4">
        <div class="panel panel-default text-center" style="box-shadow:0 1px 3px rgba(0,0,0,0.08);">
          <div class="panel-body">
            <h2 class="text-primary" style="margin:0 0 5px; font-weight:700;">{{ assetStats.totalCount }}</h2>
            <p style="margin:0; color:#666;"><i class="glyphicon glyphicon-certificate"></i> Total de Activos</p>
          </div>
        </div>
      </div>
      <div class="col-xs-12 col-sm-4">
        <div class="panel panel-default text-center" style="box-shadow:0 1px 3px rgba(0,0,0,0.08);">
          <div class="panel-body">
            <h2 class="text-success" style="margin:0 0 5px; font-weight:700;">S/ {{ fmt(assetStats.totalValue) }}</h2>
            <p style="margin:0; color:#666;"><i class="glyphicon glyphicon-usd"></i> Valor Total Estimado (Activos)</p>
          </div>
        </div>
      </div>
      <div class="col-xs-12 col-sm-4">
        <div class="panel panel-default text-center" style="box-shadow:0 1px 3px rgba(0,0,0,0.08);">
          <div class="panel-body">
            <h2 class="text-warning" style="margin:0 0 5px; font-weight:700;">{{ assetStats.garantiaCount }}</h2>
            <p style="margin:0; color:#666;"><i class="glyphicon glyphicon-lock"></i> En Garantía de Préstamo</p>
          </div>
        </div>
      </div>
    </div>

    <div class="panel panel-default">
      <DataTable
        title="Registro de Activos Valiosos"
        icon="glyphicon-certificate"
        :headers="['#','Nombre','Categoría','Valor Estimado','Precio Compra','Fecha Compra','N° Documento','Estado','Imagen','Acción']"
        :store="store"
      >
        <template #actions>
          <button class="btn btn-default btn-sm" @click="importRef.open()">
            <i class="glyphicon glyphicon-upload"></i> Importar
          </button>
          <button class="btn btn-success" @click="openAdd">
            <i class="glyphicon glyphicon-plus-sign"></i> Registrar Activo
          </button>
        </template>
      </DataTable>
    </div>

    <!-- Add Modal -->
    <AppModal v-model="showAdd" title="Registrar Activo Valioso" icon="glyphicon-plus"
              size="lg" @closed="resetForm">
      <template #body>
        <div v-if="formError" class="alert alert-danger">{{ formError }}</div>
        <AssetForm ref="addFormRef" />
      </template>
      <template #footer>
        <button class="btn btn-default" @click="showAdd = false">Cancelar</button>
        <button class="btn btn-success" :disabled="saving" @click="submitAdd">
          <i class="glyphicon glyphicon-floppy-disk"></i>
          {{ saving ? 'Guardando…' : 'Guardar Activo' }}
        </button>
      </template>
    </AppModal>

    <!-- Edit Modal -->
    <AppModal v-model="showEdit" title="Editar Activo" icon="glyphicon-edit"
              size="lg" @closed="resetForm">
      <template #body>
        <div v-if="formError" class="alert alert-danger">{{ formError }}</div>
        <AssetForm ref="editFormRef" :edit-id="editId" />
      </template>
      <template #footer>
        <button class="btn btn-default" @click="showEdit = false">Cancelar</button>
        <button class="btn btn-primary" :disabled="saving" @click="submitEdit">
          <i class="glyphicon glyphicon-floppy-disk"></i>
          {{ saving ? 'Guardando…' : 'Actualizar' }}
        </button>
      </template>
    </AppModal>

    <ImportModal ref="importRef" module="asset" @imported="store.reload()" />
  </div>
</template>

<script setup>
import { ref, computed, inject, onMounted, onUnmounted } from 'vue'
import { useAssetStore } from '@/stores/useAssetStore'
import DataTable   from '@/components/DataTable.vue'
import AppModal    from '@/components/AppModal.vue'
import ImportModal from '@/components/ImportModal.vue'
import AssetForm   from '@/components/forms/AssetForm.vue'
import * as api    from '@/api'

const toast    = inject('toast')
const store    = useAssetStore()
const showAdd  = ref(false)
const showEdit = ref(false)
const saving   = ref(false)
const formError = ref('')
const editId    = ref(null)
const importRef   = ref(null)
const addFormRef  = ref(null)
const editFormRef = ref(null)

const assetStats = computed(() => {
  let totalValue = 0, garantiaCount = 0
  for (const r of store.rows) {
    const valStr = String(r[3] || '').replace(/[^0-9.]/g, '')
    totalValue += parseFloat(valStr) || 0
    if (String(r[7] || '').includes('GARANTIA')) {
      garantiaCount++
    }
  }
  return {
    totalCount: store.rows.length,
    totalValue,
    garantiaCount
  }
})

function fmt(n) {
  const num = parseFloat(n) || 0
  return num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')
}

function openAdd()   { resetForm(); showAdd.value = true }
function resetForm() { formError.value = '' }

async function submitAdd() {
  saving.value = true; formError.value = ''
  try {
    const fd = addFormRef.value?.getFormData()
    if (!fd) { formError.value = 'Complete los campos requeridos.'; return }
    const { data } = await api.createAsset(fd)
    if (data.success) { showAdd.value = false; await store.reload(); toast.success(data.messages) }
    else formError.value = data.messages
  } catch { formError.value = 'Error de comunicación.' }
  finally { saving.value = false }
}

async function submitEdit() {
  saving.value = true; formError.value = ''
  try {
    const fd = editFormRef.value?.getFormData()
    if (!fd) { formError.value = 'Complete los campos requeridos.'; return }
    const { data } = await api.editAsset(fd)
    if (data.success) { showEdit.value = false; await store.reload(); toast.success(data.messages) }
    else formError.value = data.messages
  } catch { formError.value = 'Error de comunicación.' }
  finally { saving.value = false }
}

async function handleTableAction(e) {
  const el = e.target.closest('a')
  if (!el) return
  const onclickAttr = el.getAttribute('onclick') || ''

  const editMatch = onclickAttr.match(/editAsset\((\d+)\)/)
  if (editMatch) {
    e.preventDefault()
    const id = parseInt(editMatch[1])
    const { data } = await api.getAssetData(id)
    if (data) {
      editId.value = id
      showEdit.value = true
      setTimeout(() => editFormRef.value?.setData(data), 50)
    }
  }

  const removeMatch = onclickAttr.match(/removeAsset\((\d+)\)/)
  if (removeMatch) {
    e.preventDefault()
    if (!confirm('¿Eliminar este activo?')) return
    const { data } = await api.removeAsset(parseInt(removeMatch[1]))
    if (data.success) { await store.reload(); toast.success(data.messages) }
    else toast.danger(data.messages)
  }
}

onMounted(async () => {
  await store.load()
  document.addEventListener('click', handleTableAction)
})

onUnmounted(() => {
  document.removeEventListener('click', handleTableAction)
})
</script>
