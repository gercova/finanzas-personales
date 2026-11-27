<template>
  <div>
    <ol class="breadcrumb">
      <li><a href="#" @click.prevent="$router.push('/dashboard')">Inicio</a></li>
      <li class="active">Ingresos</li>
    </ol>

    <!-- Stat cards -->
    <div class="row" style="margin-bottom:20px;">
      <div class="col-xs-12 col-sm-4">
        <div class="panel panel-success">
          <div class="panel-heading"><i class="glyphicon glyphicon-calendar"></i> Ingresos del Mes</div>
          <div class="panel-body text-center">
            <h3 style="margin:0;font-weight:700;" class="text-success">S/ {{ fmtMoney(stats.month) }}</h3>
            <small class="text-muted">{{ monthLabel }}</small>
          </div>
        </div>
      </div>
      <div class="col-xs-12 col-sm-4">
        <div class="panel panel-info">
          <div class="panel-heading"><i class="glyphicon glyphicon-stats"></i> Ingresos del Año</div>
          <div class="panel-body text-center">
            <h3 style="margin:0;font-weight:700;" class="text-info">S/ {{ fmtMoney(stats.year) }}</h3>
            <small class="text-muted">{{ new Date().getFullYear() }}</small>
          </div>
        </div>
      </div>
      <div class="col-xs-12 col-sm-4">
        <div class="panel panel-warning">
          <div class="panel-heading"><i class="glyphicon glyphicon-repeat"></i> Recurrentes</div>
          <div class="panel-body text-center">
            <h3 style="margin:0;font-weight:700;" class="text-warning">{{ stats.recurring }}</h3>
            <small class="text-muted">activos</small>
          </div>
        </div>
      </div>
    </div>

    <!-- Main panel -->
    <div class="panel panel-default">
      <DataTable
        title="Gestión de Ingresos"
        icon="glyphicon-arrow-up text-success"
        :columns="columns"
        :store="store"
      >
        <template #actions>
          <button type="button" class="btn btn-import" @click="importRef.open()">
            <i class="glyphicon glyphicon-upload"></i> Importar
          </button>
          <button type="button" class="btn btn-success" @click="openAdd">
            <i class="glyphicon glyphicon-plus-sign"></i> Registrar Ingreso
          </button>
        </template>

        <template #cell-concept="{ row }">
          {{ row.concept }}
          <i v-if="row.is_recurring" class="glyphicon glyphicon-repeat text-info" title="Recurrente" style="margin-left:4px;"></i>
        </template>
        <template #cell-account_name="{ row }">{{ row.account_name || '-' }}</template>
        <template #cell-category_name="{ row }">{{ row.category_name || '-' }}</template>
        <template #cell-project_name="{ row }">{{ row.project_name || '-' }}</template>
        <template #cell-income_date="{ row }">{{ fmtDate(row.income_date) }}</template>
        <template #cell-amount="{ row }">S/ {{ fmtMoney(row.amount) }}</template>
        <template #cell-status="{ row }">
          <span :class="'label label-' + statusBadgeClass(row.status)">{{ statusLabel(row.status) }}</span>
        </template>
        <template #cell-actions="{ row }">
          <ActionDropdown label="">
            <li><a href="#" @click.prevent="editIncome(row.id)"><i class="glyphicon glyphicon-edit"></i> Editar</a></li>
            <li class="divider"></li>
            <li><a href="#" @click.prevent="removeIncome(row.id)"><i class="glyphicon glyphicon-trash text-danger"></i> Eliminar</a></li>
          </ActionDropdown>
        </template>
      </DataTable>
    </div>

    <!-- Add Modal -->
    <AppModal v-model="showAdd" title="Registrar Ingreso" icon="glyphicon-plus-sign"
              header-color="#27ae60" size="lg" @closed="resetForm">
      <template #body>
        <div v-if="formError" class="alert alert-danger">{{ formError }}</div>
        <IncomeForm ref="addFormRef" :accounts="accounts" :categories="categories" :projects="projects" />
      </template>
      <template #footer>
        <button class="btn btn-default" @click="showAdd = false">Cancelar</button>
        <button class="btn btn-success" :disabled="saving" @click="submitAdd">
          <i class="glyphicon glyphicon-floppy-disk"></i>
          {{ saving ? 'Guardando…' : 'Guardar' }}
        </button>
      </template>
    </AppModal>

    <!-- Edit Modal -->
    <AppModal v-model="showEdit" title="Editar Ingreso" icon="glyphicon-edit"
              header-color="#2980b9" size="lg" @closed="resetForm">
      <template #body>
        <div v-if="formError" class="alert alert-danger">{{ formError }}</div>
        <IncomeForm ref="editFormRef" :accounts="accounts" :categories="categories" :projects="projects" :edit-id="editId" />
      </template>
      <template #footer>
        <button class="btn btn-default" @click="showEdit = false">Cancelar</button>
        <button class="btn btn-primary" :disabled="saving" @click="submitEdit">
          <i class="glyphicon glyphicon-floppy-disk"></i>
          {{ saving ? 'Guardando…' : 'Actualizar' }}
        </button>
      </template>
    </AppModal>

    <ImportModal ref="importRef" module="income" @imported="onMutated" />
  </div>
</template>

<script setup>
import { ref, computed, inject, nextTick, onMounted } from 'vue'
import { useIncomeStore } from '@/stores/useIncomeStore'
import { useDashboardStore } from '@/stores/useDashboardStore'
import { useReportStore } from '@/stores/useReportStore'
import { useAccountStore } from '@/stores/useAccountStore'
import { useFormOptionsStore } from '@/stores/useFormOptionsStore'
import { useAuthStore } from '@/stores/useAuthStore'
import DataTable      from '@/components/DataTable.vue'
import ActionDropdown from '@/components/ActionDropdown.vue'
import AppModal       from '@/components/AppModal.vue'
import ImportModal    from '@/components/ImportModal.vue'
import IncomeForm     from '@/components/forms/IncomeForm.vue'
import { fmtMoney, fmtDate } from '@/utils/format'
import { confirmDelete }     from '@/utils/confirmDelete'
import * as api from '@/api'

const toast          = inject('toast')
const store          = useIncomeStore()
const dashboardStore = useDashboardStore()
const reportStore    = useReportStore()
const accountStore   = useAccountStore()
const optionsStore   = useFormOptionsStore()
const authStore      = useAuthStore()

const columns = [
  { key: '_index',        label: '#' },
  { key: 'concept',       label: 'Concepto' },
  { key: 'account_name',  label: 'Cuenta' },
  { key: 'category_name', label: 'Categoría' },
  { key: 'project_name',  label: 'Proyecto' },
  { key: 'income_date',   label: 'Fecha' },
  { key: 'amount',        label: 'Monto' },
  { key: 'status',        label: 'Estado' },
  { key: 'actions',       label: 'Acción' },
]

const showAdd     = ref(false)
const showEdit    = ref(false)
const saving      = ref(false)
const formError   = ref('')
const editId      = ref(null)
const importRef   = ref(null)
const addFormRef  = ref(null)
const editFormRef = ref(null)

const accounts   = computed(() => optionsStore.accounts)
const categories = computed(() => optionsStore.incomeCategories)
const projects   = computed(() => optionsStore.projects)

const today      = new Date()
const monthLabel = today.toLocaleString('es', { month: 'long', year: 'numeric' })

const stats = computed(() => {
  const curMonth = today.toLocaleDateString('en-CA').slice(0, 7)
  const curYear  = String(today.getFullYear())
  let month = 0, year = 0, recurring = 0
  for (const r of store.rows) {
    if (r.status === 'CANCELLED' || r.status === 'CANCELADO') continue
    if (r.income_date?.startsWith(curMonth)) month += r.amount || 0
    if (r.income_date?.startsWith(curYear))  year  += r.amount || 0
    if (r.is_recurring) recurring++
  }
  return { month, year, recurring }
})

function statusLabel(s) {
  if (s === 'CONFIRMED' || s === 'ACTIVO') return 'CONFIRMADO'
  if (s === 'PENDING' || s === 'PENDIENTE') return 'PENDIENTE'
  if (s === 'CANCELLED' || s === 'CANCELADO') return 'CANCELADO'
  return s || '—'
}

function statusBadgeClass(s) {
  if (s === 'CONFIRMED' || s === 'ACTIVO') return 'success'
  if (s === 'PENDING' || s === 'PENDIENTE') return 'warning'
  return 'default'
}

function openAdd()   { resetForm(); showAdd.value = true }
function resetForm() { formError.value = '' }

async function onMutated() {
  dashboardStore.invalidate()
  reportStore.invalidate()
  accountStore.invalidate()
  await Promise.all([store.reload(), authStore.refreshHeaderMetrics()])
}

async function submitAdd() {
  saving.value = true; formError.value = ''
  try {
    const fd = addFormRef.value?.getFormData()
    if (!fd) { formError.value = 'Complete los campos requeridos y verifique que el monto sea mayor a 0.'; return }
    const { data } = await api.createIncome(fd)
    if (data.success) { showAdd.value = false; await onMutated(); toast.success(data.messages) }
    else formError.value = data.messages
  } catch { formError.value = 'Error de comunicación.' }
  finally { saving.value = false }
}

async function submitEdit() {
  saving.value = true; formError.value = ''
  try {
    const fd = editFormRef.value?.getFormData()
    if (!fd) { formError.value = 'Complete los campos requeridos y verifique que el monto sea mayor a 0.'; return }
    const { data } = await api.editIncome(fd)
    if (data.success) { showEdit.value = false; await onMutated(); toast.success(data.messages) }
    else formError.value = data.messages
  } catch { formError.value = 'Error de comunicación.' }
  finally { saving.value = false }
}

async function editIncome(id) {
  const { data } = await api.getIncomeData(id)
  if (data.success) {
    resetForm()
    editId.value = id
    showEdit.value = true
    await nextTick()
    editFormRef.value?.setData(data.data)
  } else {
    toast.danger(data.messages || 'No se pudo cargar el ingreso.')
  }
}

async function removeIncome(id) {
  if (!(await confirmDelete('Esta acción no se puede deshacer.', '¿Eliminar este ingreso?'))) return
  const { data } = await api.removeIncome(id)
  if (data.success) { await onMutated(); toast.success(data.messages) }
  else toast.danger(data.messages)
}

onMounted(async () => {
  await Promise.all([store.load(), optionsStore.load()])
})
</script>
