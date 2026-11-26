<template>
  <div>
    <ol class="breadcrumb">
      <li><a href="#" @click.prevent="$router.push('/dashboard')">Inicio</a></li>
      <li class="active">Proyectos</li>
    </ol>

    <div class="panel panel-default">
      <DataTable
        title="Gestión de Proyectos"
        icon="glyphicon-briefcase text-primary"
        :columns="columns"
        :store="store"
      >
        <template #actions>
          <button class="btn btn-default btn-sm" @click="importRef.open()">
            <i class="glyphicon glyphicon-upload"></i> Importar
          </button>
          <button class="btn btn-primary" @click="openAdd">
            <i class="glyphicon glyphicon-plus-sign"></i> Nuevo Proyecto
          </button>
        </template>

        <template #cell-client_name="{ row }">{{ row.client_name || '—' }}</template>
        <template #cell-budget="{ row }">S/ {{ fmtMoney(row.budget) }}</template>
        <template #cell-start_date="{ row }">{{ row.start_date || '—' }}</template>
        <template #cell-end_date="{ row }">{{ row.end_date || '—' }}</template>
        <template #cell-status="{ row }">
          <span :class="'label label-' + statusMeta(row.status).cls">{{ statusMeta(row.status).label }}</span>
        </template>
        <template #cell-project_income="{ row }">S/ {{ fmtMoney(row.project_income) }}</template>
        <template #cell-project_expense="{ row }">S/ {{ fmtMoney(row.project_expense) }}</template>
        <template #cell-forecast_ratio="{ row }">
          <span
            v-if="row.forecast_ratio <= 70"
            class="label label-success"
            title="Gasto bajo, proyecto saludable"
          >🟢 BAJO ({{ row.forecast_ratio }}%)</span>
          <span
            v-else-if="row.forecast_ratio <= 95"
            class="label label-warning"
            title="Gasto moderado, vigilar presupuesto"
          >🟡 MEDIO ({{ row.forecast_ratio }}%)</span>
          <span
            v-else
            class="label label-danger"
            title="Gasto alto, riesgo de déficit"
          >🔴 ALTO ({{ row.forecast_ratio }}%)</span>
        </template>
        <template #cell-actions="{ row }">
          <ActionDropdown>
            <li><a href="#" @click.prevent="editProject(row.id)"><i class="glyphicon glyphicon-edit"></i> Editar</a></li>
            <li><a href="#" @click.prevent="removeProject(row.id)"><i class="glyphicon glyphicon-trash text-danger"></i> Eliminar</a></li>
          </ActionDropdown>
        </template>
      </DataTable>
    </div>

    <!-- Add -->
    <AppModal v-model="showAdd" title="Nuevo Proyecto" icon="glyphicon-plus" @closed="resetForm">
      <template #body>
        <div v-if="formError" class="alert alert-danger">{{ formError }}</div>
        <ProjectForm ref="addFormRef" />
      </template>
      <template #footer>
        <button class="btn btn-default" @click="showAdd = false">Cancelar</button>
        <button class="btn btn-primary" :disabled="saving" @click="submitAdd">
          <i class="glyphicon glyphicon-floppy-disk"></i>
          {{ saving ? 'Guardando…' : 'Guardar' }}
        </button>
      </template>
    </AppModal>

    <!-- Edit -->
    <AppModal v-model="showEdit" title="Editar Proyecto" icon="glyphicon-edit" @closed="resetForm">
      <template #body>
        <div v-if="formError" class="alert alert-danger">{{ formError }}</div>
        <ProjectForm ref="editFormRef" :edit-id="editId" />
      </template>
      <template #footer>
        <button class="btn btn-default" @click="showEdit = false">Cancelar</button>
        <button class="btn btn-primary" :disabled="saving" @click="submitEdit">
          <i class="glyphicon glyphicon-floppy-disk"></i>
          {{ saving ? 'Guardando…' : 'Actualizar' }}
        </button>
      </template>
    </AppModal>

    <ImportModal ref="importRef" module="project" @imported="onMutated" />
  </div>
</template>

<script setup>
import { ref, inject, nextTick, onMounted } from 'vue'
import { useProjectStore } from '@/stores/useProjectStore'
import { useDashboardStore } from '@/stores/useDashboardStore'
import { useReportStore } from '@/stores/useReportStore'
import { useFormOptionsStore } from '@/stores/useFormOptionsStore'
import DataTable      from '@/components/DataTable.vue'
import ActionDropdown from '@/components/ActionDropdown.vue'
import AppModal       from '@/components/AppModal.vue'
import ImportModal    from '@/components/ImportModal.vue'
import ProjectForm    from '@/components/forms/ProjectForm.vue'
import { fmtMoney }   from '@/utils/format'
import * as api from '@/api'

const toast          = inject('toast')
const store          = useProjectStore()
const dashboardStore = useDashboardStore()
const reportStore    = useReportStore()
const optionsStore   = useFormOptionsStore()

const columns = [
  { key: '_index',          label: '#' },
  { key: 'project_name',    label: 'Proyecto' },
  { key: 'client_name',     label: 'Cliente' },
  { key: 'budget',          label: 'Presupuesto' },
  { key: 'start_date',      label: 'Inicio' },
  { key: 'end_date',        label: 'Fin' },
  { key: 'status',          label: 'Estado' },
  { key: 'project_income',  label: 'Total Ingresos' },
  { key: 'project_expense', label: 'Total Gastos' },
  { key: 'forecast_ratio',  label: 'Pronóstico' },
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

function statusMeta(st) {
  const map = {
    ACTIVE:    { cls: 'success', label: 'Activo' },
    ON_HOLD:   { cls: 'warning', label: 'En Espera' },
    COMPLETED: { cls: 'info',    label: 'Completado' },
  }
  return map[st] || { cls: 'default', label: st || '—' }
}

function openAdd()   { resetForm(); showAdd.value = true }
function resetForm() { formError.value = '' }

async function onMutated() {
  dashboardStore.invalidate()
  reportStore.invalidate()
  await Promise.all([store.reload(), optionsStore.reload()])
}

async function submitAdd() {
  saving.value = true; formError.value = ''
  try {
    const fd = addFormRef.value?.getFormData()
    if (!fd) { formError.value = 'Complete el nombre y un presupuesto mayor a 0.'; return }
    const { data } = await api.createProject(fd)
    if (data.success) { showAdd.value = false; await onMutated(); toast.success(data.messages) }
    else formError.value = data.messages
  } catch { formError.value = 'Error de comunicación.' }
  finally { saving.value = false }
}

async function submitEdit() {
  saving.value = true; formError.value = ''
  try {
    const fd = editFormRef.value?.getFormData()
    if (!fd) { formError.value = 'Complete el nombre y un presupuesto mayor a 0.'; return }
    const { data } = await api.editProject(fd)
    if (data.success) { showEdit.value = false; await onMutated(); toast.success(data.messages) }
    else formError.value = data.messages
  } catch { formError.value = 'Error de comunicación.' }
  finally { saving.value = false }
}

async function editProject(id) {
  const { data } = await api.getProjectData(id)
  if (data) {
    resetForm()
    editId.value = id
    showEdit.value = true
    await nextTick()
    editFormRef.value?.setData(data)
  } else {
    toast.danger('No se pudo cargar el proyecto.')
  }
}

async function removeProject(id) {
  if (!confirm('¿Eliminar este proyecto?')) return
  const { data } = await api.removeProject(id)
  if (data.success) { await onMutated(); toast.success(data.messages) }
  else toast.danger(data.messages)
}

onMounted(async () => {
  await store.load()
})
</script>
