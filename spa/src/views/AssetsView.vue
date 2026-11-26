<template>
  <div>
    <ol class="breadcrumb">
      <li><a href="#" @click.prevent="$router.push('/dashboard')">Inicio</a></li>
      <li class="active">Activos Valiosos</li>
    </ol>

    <!-- Stat cards -->
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
            <h2 class="text-success" style="margin:0 0 5px; font-weight:700;">S/ {{ fmtMoney(assetStats.totalValue) }}</h2>
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
        :columns="columns"
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

        <template #cell-category="{ row }">{{ categoryLabel(row.category) }}</template>
        <template #cell-estimated_value="{ row }">S/ {{ fmtMoney(row.estimated_value) }}</template>
        <template #cell-purchase_price="{ row }">
          {{ row.purchase_price !== null ? 'S/ ' + fmtMoney(row.purchase_price) : '—' }}
        </template>
        <template #cell-purchase_date="{ row }">{{ row.purchase_date ? fmtDate(row.purchase_date) : '—' }}</template>
        <template #cell-document_number="{ row }">{{ row.document_number || '—' }}</template>
        <template #cell-status="{ row }">
          <span :class="'label label-' + statusClass(row.status)">
            {{ String(row.status || '').replace(/_/g, ' ') }}
          </span>
        </template>
        <template #cell-image_path="{ row }">
          <a v-if="row.image_path" :href="api.assetUrl(row.image_path)" target="_blank" rel="noopener">
            <img :src="api.assetUrl(row.image_path)" alt="Imagen" style="height:40px;border-radius:4px;border:1px solid #ddd;" />
          </a>
          <span v-else>—</span>
        </template>
        <template #cell-actions="{ row }">
          <ActionDropdown>
            <li><a href="#" @click.prevent="editAsset(row.id)"><i class="glyphicon glyphicon-edit"></i> Editar</a></li>
            <li><a href="#" @click.prevent="removeAsset(row.id)"><i class="glyphicon glyphicon-trash text-danger"></i> Eliminar</a></li>
          </ActionDropdown>
        </template>
      </DataTable>
    </div>

    <!-- Add Modal -->
    <AppModal v-model="showAdd" title="Registrar Activo Valioso" icon="glyphicon-plus" size="lg" @closed="resetForm">
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
    <AppModal v-model="showEdit" title="Editar Activo" icon="glyphicon-edit" size="lg" @closed="resetForm">
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

    <ImportModal ref="importRef" module="asset" @imported="onMutated" />
  </div>
</template>

<script setup>
import { ref, computed, inject, nextTick, onMounted } from 'vue'
import { useAssetStore } from '@/stores/useAssetStore'
import { useReportStore } from '@/stores/useReportStore'
import DataTable      from '@/components/DataTable.vue'
import ActionDropdown from '@/components/ActionDropdown.vue'
import AppModal       from '@/components/AppModal.vue'
import ImportModal    from '@/components/ImportModal.vue'
import AssetForm      from '@/components/forms/AssetForm.vue'
import { fmtMoney, fmtDate } from '@/utils/format'
import * as api from '@/api'

const toast       = inject('toast')
const store       = useAssetStore()
const reportStore = useReportStore()

const columns = [
  { key: '_index',          label: '#' },
  { key: 'asset_name',      label: 'Nombre' },
  { key: 'category',        label: 'Categoría' },
  { key: 'estimated_value', label: 'Valor Estimado' },
  { key: 'purchase_price',  label: 'Precio Compra' },
  { key: 'purchase_date',   label: 'Fecha Compra' },
  { key: 'document_number', label: 'N° Documento' },
  { key: 'status',          label: 'Estado' },
  { key: 'image_path',      label: 'Imagen' },
  { key: 'actions',         label: 'Acción' },
]

const showAdd     = ref(false)
const showEdit    = ref(false)
const saving      = ref(false)
const formError   = ref('')
const editId      = ref(null)
const importRef   = ref(null)
const addFormRef  = ref(null)
const editFormRef = ref(null)

const assetStats = computed(() => {
  let totalValue = 0, garantiaCount = 0
  for (const r of store.rows) {
    totalValue += r.estimated_value || 0
    if (r.status === 'EN_GARANTIA') garantiaCount++
  }
  return {
    totalCount: store.rows.length,
    totalValue,
    garantiaCount,
  }
})

function categoryLabel(cat) {
  const map = {
    VEHICULO:             '🚗 Vehículo',
    INMUEBLE:             '🏠 Inmueble',
    DOCUMENTO_FIDUCIARIO: '📄 Doc. Fiduciario',
    EQUIPO_TECNOLOGICO:   '💻 Equipo Tecno.',
    OTRO:                 '📦 Otro',
  }
  return map[cat] || cat || '—'
}

function statusClass(status) {
  const map = {
    ACTIVO:       'success',
    EN_GARANTIA:  'warning',
    VENDIDO:      'info',
    DADO_DE_BAJA: 'danger',
  }
  return map[status] || 'default'
}

function openAdd()   { resetForm(); showAdd.value = true }
function resetForm() { formError.value = '' }

async function onMutated() {
  reportStore.invalidate()
  await store.reload()
}

async function submitAdd() {
  saving.value = true; formError.value = ''
  try {
    const fd = addFormRef.value?.getFormData()
    if (!fd) { formError.value = 'Complete el nombre, categoría y un valor estimado mayor a 0.'; return }
    const { data } = await api.createAsset(fd)
    if (data.success) { showAdd.value = false; await onMutated(); toast.success(data.messages) }
    else formError.value = data.messages
  } catch { formError.value = 'Error de comunicación.' }
  finally { saving.value = false }
}

async function submitEdit() {
  saving.value = true; formError.value = ''
  try {
    const fd = editFormRef.value?.getFormData()
    if (!fd) { formError.value = 'Complete el nombre, categoría y un valor estimado mayor a 0.'; return }
    const { data } = await api.editAsset(fd)
    if (data.success) { showEdit.value = false; await onMutated(); toast.success(data.messages) }
    else formError.value = data.messages
  } catch { formError.value = 'Error de comunicación.' }
  finally { saving.value = false }
}

async function editAsset(id) {
  const { data } = await api.getAssetData(id)
  if (data) {
    resetForm()
    editId.value = id
    showEdit.value = true
    await nextTick()
    editFormRef.value?.setData(data)
  } else {
    toast.danger('No se pudo cargar el activo.')
  }
}

async function removeAsset(id) {
  if (!confirm('¿Eliminar este activo?')) return
  const { data } = await api.removeAsset(id)
  if (data.success) { await onMutated(); toast.success(data.messages) }
  else toast.danger(data.messages)
}

onMounted(async () => {
  await store.load()
})
</script>
