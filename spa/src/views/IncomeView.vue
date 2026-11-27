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

    <!-- Clickable quick status & recurring filters -->
    <div class="quick-filter-bar">
      <button type="button" class="quick-filter-btn qf-paid"
              style="background-color:#1f8a4c; border-color:#1f8a4c;"
              :class="{ 'is-selected': store.filters.status==='CONFIRMED' }"
              @click="toggleStatusQuick('CONFIRMED')">
        CONFIRMADO
      </button>
      <button type="button" class="quick-filter-btn qf-partial"
              :class="{ 'is-selected': store.filters.status==='PENDING' }"
              @click="toggleStatusQuick('PENDING')">
        PENDIENTE
      </button>
      <button type="button" class="quick-filter-btn qf-pending"
              :class="{ 'is-selected': store.filters.status==='CANCELLED' }"
              @click="toggleStatusQuick('CANCELLED')">
        CANCELADO
      </button>
      <button type="button" class="quick-filter-btn qf-outline-warn"
              :class="{ 'is-selected': store.filters.recurring==='YES' }"
              @click="store.filters.recurring = store.filters.recurring === 'YES' ? '' : 'YES'">
        <i class="glyphicon glyphicon-repeat"></i> Solo recurrentes
      </button>
      <button v-if="store.filters.status || store.filters.recurring" type="button" class="btn btn-xs btn-link" style="padding:2px 6px;" @click="store.filters.status = ''; store.filters.recurring = ''">
        Ver todos
      </button>
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

        <template #filters>
          <div style="background:#f9f9f9; border:1px solid #e5e5e5; border-radius:4px; padding:10px 12px; margin-bottom:12px;">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; margin-bottom:10px;">
              <div style="font-size:12.5px; font-weight:700; color:#444;">
                <i class="glyphicon glyphicon-filter text-primary"></i> Filtros de búsqueda
                <span v-if="store.activeFilterCount > 0" class="badge" style="background:#337ab7; margin-left:4px;">
                  {{ store.activeFilterCount }}
                </span>
                <span v-if="store.activeFilterCount > 0 || store.search" class="text-success" style="margin-left:10px; font-weight:600;">
                  · Total filtrado: S/ {{ fmtMoney(filteredTotal) }}
                </span>
              </div>
              <div style="display:flex; align-items:center; flex-wrap:wrap; gap:4px;">
                <span class="text-muted" style="font-size:11px; margin-right:2px;">Rango rápido:</span>
                <button type="button" class="btn btn-xs btn-default" @click="applyDatePreset('today')">Hoy</button>
                <button type="button" class="btn btn-xs btn-default" @click="applyDatePreset('this_month')">Este mes</button>
                <button type="button" class="btn btn-xs btn-default" @click="applyDatePreset('last_month')">Mes ant.</button>
                <button type="button" class="btn btn-xs btn-default" @click="applyDatePreset('this_year')">Este año</button>
                <button
                  v-if="store.activeFilterCount > 0 || store.search"
                  type="button"
                  class="btn btn-xs btn-danger"
                  style="margin-left:6px;"
                  @click="store.resetFilters()"
                >
                  <i class="glyphicon glyphicon-remove"></i> Limpiar filtros
                </button>
              </div>
            </div>

            <div class="row" style="margin-bottom:8px;">
              <div class="col-xs-12 col-sm-6 col-md-3" style="margin-bottom:6px;">
                <label class="control-label" style="font-size:11.5px; color:#555; margin-bottom:3px; display:block;">
                  <i class="glyphicon glyphicon-credit-card"></i> Cuenta
                </label>
                <select class="form-control input-sm" v-model="store.filters.accountId">
                  <option value="">Todas las cuentas</option>
                  <option v-for="a in accountFilterOptions" :key="a.value" :value="a.value">{{ a.label }}</option>
                </select>
              </div>

              <div class="col-xs-12 col-sm-6 col-md-3" style="margin-bottom:6px;">
                <label class="control-label" style="font-size:11.5px; color:#555; margin-bottom:3px; display:block;">
                  <i class="glyphicon glyphicon-tags"></i> Categoría
                </label>
                <select class="form-control input-sm" v-model="store.filters.categoryId">
                  <option value="">Todas las categorías</option>
                  <option value="__NONE__">Sin categoría</option>
                  <option v-for="c in categoryFilterOptions" :key="c.value" :value="c.value">{{ c.label }}</option>
                </select>
              </div>

              <div class="col-xs-12 col-sm-6 col-md-3" style="margin-bottom:6px;">
                <label class="control-label" style="font-size:11.5px; color:#555; margin-bottom:3px; display:block;">
                  <i class="glyphicon glyphicon-briefcase"></i> Proyecto
                </label>
                <select class="form-control input-sm" v-model="store.filters.projectId">
                  <option value="">Todos los proyectos</option>
                  <option value="__NONE__">Sin proyecto</option>
                  <option v-for="p in projectFilterOptions" :key="p.value" :value="p.value">{{ p.label }}</option>
                </select>
              </div>

              <div class="col-xs-12 col-sm-6 col-md-3" style="margin-bottom:6px;">
                <label class="control-label" style="font-size:11.5px; color:#555; margin-bottom:3px; display:block;">
                  <i class="glyphicon glyphicon-tag"></i> Estado
                </label>
                <select class="form-control input-sm" v-model="store.filters.status">
                  <option value="">Todos los estados</option>
                  <option value="CONFIRMED">Confirmado</option>
                  <option value="PENDING">Pendiente</option>
                  <option value="CANCELLED">Cancelado</option>
                </select>
              </div>
            </div>

            <div class="row">
              <div class="col-xs-6 col-sm-3 col-md-3" style="margin-bottom:4px;">
                <label class="control-label" style="font-size:11.5px; color:#555; margin-bottom:3px; display:block;">
                  <i class="glyphicon glyphicon-calendar"></i> Fecha Desde
                </label>
                <input type="date" class="form-control input-sm" v-model="store.filters.dateFrom" />
              </div>

              <div class="col-xs-6 col-sm-3 col-md-3" style="margin-bottom:4px;">
                <label class="control-label" style="font-size:11.5px; color:#555; margin-bottom:3px; display:block;">
                  Fecha Hasta
                </label>
                <input type="date" class="form-control input-sm" v-model="store.filters.dateTo" />
              </div>

              <div class="col-xs-12 col-sm-6 col-md-3" style="margin-bottom:4px;">
                <label class="control-label" style="font-size:11.5px; color:#555; margin-bottom:3px; display:block;">
                  <i class="glyphicon glyphicon-repeat"></i> Recurrencia
                </label>
                <select class="form-control input-sm" v-model="store.filters.recurring">
                  <option value="">Todos</option>
                  <option value="YES">Solo recurrentes</option>
                  <option value="NO">No recurrentes</option>
                </select>
              </div>

              <div class="col-xs-12 col-sm-6 col-md-3" style="margin-bottom:4px;">
                <label class="control-label" style="font-size:11.5px; color:#555; margin-bottom:3px; display:block;">
                  <i class="glyphicon glyphicon-usd"></i> Monto (S/)
                </label>
                <div style="display:flex; gap:6px; align-items:center;">
                  <input type="number" step="0.01" min="0" class="form-control input-sm" placeholder="Mín." v-model="store.filters.minAmount" />
                  <span class="text-muted">-</span>
                  <input type="number" step="0.01" min="0" class="form-control input-sm" placeholder="Máx." v-model="store.filters.maxAmount" />
                </div>
              </div>
            </div>
          </div>
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
import { getDatePresetRange, buildRefOptions } from '@/utils/filters'
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

const accountFilterOptions = computed(() =>
  buildRefOptions(accounts.value, store.rows, {
    formIdKey: 'id', formNameKey: 'account_name',
    rowIdKey: 'account_id', rowNameKey: 'account_name',
  })
)
const categoryFilterOptions = computed(() =>
  buildRefOptions(categories.value, store.rows, {
    formIdKey: 'id', formNameKey: 'category_name',
    rowIdKey: 'category_id', rowNameKey: 'category_name',
  })
)
const projectFilterOptions = computed(() =>
  buildRefOptions(projects.value, store.rows, {
    formIdKey: 'id', formNameKey: 'project_name',
    rowIdKey: 'project_id', rowNameKey: 'project_name',
  })
)

const filteredTotal = computed(() =>
  store.filtered.reduce((acc, r) => acc + (r.amount || 0), 0)
)

function toggleStatusQuick(status) {
  store.filters.status = store.filters.status === status ? '' : status
}

function applyDatePreset(preset) {
  const { dateFrom, dateTo } = getDatePresetRange(preset)
  store.filters.dateFrom = dateFrom
  store.filters.dateTo   = dateTo
}

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
