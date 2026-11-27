<template>
  <div>
    <ol class="breadcrumb">
      <li><a href="#" @click.prevent="$router.push('/dashboard')">Inicio</a></li>
      <li class="active">Proyectos</li>
    </ol>

    <!-- Clickable quick status & forecast filters -->
    <div class="quick-filter-bar">
      <button type="button" class="quick-filter-btn qf-paid"
              style="background-color:#1f8a4c; border-color:#1f8a4c;"
              :class="{ 'is-selected': store.filters.status==='ACTIVE' }"
              @click="toggleStatusQuick('ACTIVE')">
        ACTIVO
      </button>
      <button type="button" class="quick-filter-btn qf-partial"
              :class="{ 'is-selected': store.filters.status==='ON_HOLD' }"
              @click="toggleStatusQuick('ON_HOLD')">
        EN ESPERA
      </button>
      <button type="button" class="quick-filter-btn qf-paid"
              style="background-color:#0f85a6; border-color:#0f85a6;"
              :class="{ 'is-selected': store.filters.status==='COMPLETED' }"
              @click="toggleStatusQuick('COMPLETED')">
        COMPLETADO
      </button>
      <button type="button" class="quick-filter-btn qf-outline-danger"
              :class="{ 'is-selected': store.filters.forecast==='HIGH' }"
              @click="store.filters.forecast = store.filters.forecast === 'HIGH' ? '' : 'HIGH'">
        <i class="glyphicon glyphicon-exclamation-sign"></i> Riesgo alto (&gt;95%)
      </button>
      <button v-if="store.filters.status || store.filters.forecast" type="button" class="btn btn-xs btn-link" style="padding:2px 6px;" @click="store.filters.status = ''; store.filters.forecast = ''">
        Ver todos
      </button>
    </div>

    <div class="panel panel-default">
      <DataTable
        title="Gestión de Proyectos"
        icon="glyphicon-briefcase text-primary"
        :columns="columns"
        :store="store"
      >
        <template #actions>
          <button type="button" class="btn btn-import" @click="importRef.open()">
            <i class="glyphicon glyphicon-upload"></i> Importar
          </button>
          <button type="button" class="btn btn-primary" @click="openAdd">
            <i class="glyphicon glyphicon-plus-sign"></i> Nuevo Proyecto
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
                <span v-if="store.activeFilterCount > 0 || store.search" class="text-primary" style="margin-left:10px; font-weight:600;">
                  · Presupuesto filtrado: S/ {{ fmtMoney(filteredSummary.budget) }}
                  | Ingresos: S/ {{ fmtMoney(filteredSummary.income) }}
                  | Gastos: S/ {{ fmtMoney(filteredSummary.expense) }}
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
                  <i class="glyphicon glyphicon-user"></i> Cliente
                </label>
                <select class="form-control input-sm" v-model="store.filters.client">
                  <option value="">Todos los clientes</option>
                  <option value="__NONE__">Sin cliente asignado</option>
                  <option v-for="c in clientFilterOptions" :key="c.value" :value="c.value">{{ c.label }}</option>
                </select>
              </div>

              <div class="col-xs-12 col-sm-6 col-md-3" style="margin-bottom:6px;">
                <label class="control-label" style="font-size:11.5px; color:#555; margin-bottom:3px; display:block;">
                  <i class="glyphicon glyphicon-tag"></i> Estado
                </label>
                <select class="form-control input-sm" v-model="store.filters.status">
                  <option value="">Todos los estados</option>
                  <option value="ACTIVE">Activo</option>
                  <option value="ON_HOLD">En Espera</option>
                  <option value="COMPLETED">Completado</option>
                </select>
              </div>

              <div class="col-xs-12 col-sm-6 col-md-3" style="margin-bottom:6px;">
                <label class="control-label" style="font-size:11.5px; color:#555; margin-bottom:3px; display:block;">
                  <i class="glyphicon glyphicon-dashboard"></i> Pronóstico de Gasto
                </label>
                <select class="form-control input-sm" v-model="store.filters.forecast">
                  <option value="">Todos los niveles</option>
                  <option value="LOW">Bajo (&le; 70%)</option>
                  <option value="MEDIUM">Medio (70% - 95%)</option>
                  <option value="HIGH">Alto (&gt; 95% déficit)</option>
                </select>
              </div>

              <div class="col-xs-12 col-sm-6 col-md-3" style="margin-bottom:6px;">
                <label class="control-label" style="font-size:11.5px; color:#555; margin-bottom:3px; display:block;">
                  <i class="glyphicon glyphicon-usd"></i> Presupuesto (S/)
                </label>
                <div style="display:flex; gap:6px; align-items:center;">
                  <input type="number" step="0.01" min="0" class="form-control input-sm" placeholder="Mín." v-model="store.filters.minBudget" />
                  <span class="text-muted">-</span>
                  <input type="number" step="0.01" min="0" class="form-control input-sm" placeholder="Máx." v-model="store.filters.maxBudget" />
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-xs-12 col-sm-4 col-md-3" style="margin-bottom:4px;">
                <label class="control-label" style="font-size:11.5px; color:#555; margin-bottom:3px; display:block;">
                  <i class="glyphicon glyphicon-calendar"></i> Filtrar por Fecha
                </label>
                <select class="form-control input-sm" v-model="store.filters.dateField">
                  <option value="start_date">Fecha de Inicio</option>
                  <option value="end_date">Fecha de Fin</option>
                </select>
              </div>

              <div class="col-xs-6 col-sm-4 col-md-3" style="margin-bottom:4px;">
                <label class="control-label" style="font-size:11.5px; color:#555; margin-bottom:3px; display:block;">
                  Desde
                </label>
                <input type="date" class="form-control input-sm" v-model="store.filters.dateFrom" />
              </div>

              <div class="col-xs-6 col-sm-4 col-md-3" style="margin-bottom:4px;">
                <label class="control-label" style="font-size:11.5px; color:#555; margin-bottom:3px; display:block;">
                  Hasta
                </label>
                <input type="date" class="form-control input-sm" v-model="store.filters.dateTo" />
              </div>
            </div>
          </div>
        </template>

        <template #cell-client_name="{ row }">{{ row.client_name || '—' }}</template>
        <template #cell-budget="{ row }">S/ {{ fmtMoney(row.budget) }}</template>
        <template #cell-start_date="{ row }">{{ row.start_date ? fmtDate(row.start_date) : '—' }}</template>
        <template #cell-end_date="{ row }">{{ row.end_date ? fmtDate(row.end_date) : '—' }}</template>
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
import { ref, computed, inject, nextTick, onMounted } from 'vue'
import { useProjectStore } from '@/stores/useProjectStore'
import { useIncomeStore } from '@/stores/useIncomeStore'
import { useExpenseStore } from '@/stores/useExpenseStore'
import { useDashboardStore } from '@/stores/useDashboardStore'
import { useReportStore } from '@/stores/useReportStore'
import { useFormOptionsStore } from '@/stores/useFormOptionsStore'
import DataTable      from '@/components/DataTable.vue'
import ActionDropdown from '@/components/ActionDropdown.vue'
import AppModal       from '@/components/AppModal.vue'
import ImportModal    from '@/components/ImportModal.vue'
import ProjectForm    from '@/components/forms/ProjectForm.vue'
import { fmtMoney, fmtDate } from '@/utils/format'
import { confirmDelete }     from '@/utils/confirmDelete'
import { getDatePresetRange } from '@/utils/filters'
import * as api from '@/api'

const toast          = inject('toast')
const store          = useProjectStore()
const incomeStore    = useIncomeStore()
const expenseStore   = useExpenseStore()
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

const clientFilterOptions = computed(() => {
  const seen = new Set()
  const list = []
  for (const r of store.rows) {
    const name = String(r.client_name || '').trim()
    if (!name || name === '—') continue
    const key = name.toLowerCase()
    if (seen.has(key)) continue
    seen.add(key)
    list.push({ value: `name:${key}`, label: name })
  }
  return list.sort((a, b) => a.label.localeCompare(b.label, 'es'))
})

const filteredSummary = computed(() => {
  let budget = 0, income = 0, expense = 0
  for (const r of store.filtered) {
    budget  += r.budget || 0
    income  += r.project_income || 0
    expense += r.project_expense || 0
  }
  return { budget, income, expense }
})

function toggleStatusQuick(status) {
  store.filters.status = store.filters.status === status ? '' : status
}

function applyDatePreset(preset) {
  const { dateFrom, dateTo } = getDatePresetRange(preset)
  store.filters.dateFrom = dateFrom
  store.filters.dateTo   = dateTo
}

function statusMeta(st) {
  const map = {
    ACTIVE:    { cls: 'success', label: 'Activo' },
    ON_HOLD:   { cls: 'warning', label: 'En Espera' },
    COMPLETED: { cls: 'info',    label: 'Completado' },
  }
  return map[st] || { cls: 'default', label: st || '—' }
}

function openAdd() {
  resetForm()
  showAdd.value = true
  nextTick(() => addFormRef.value?.reset())
}
function resetForm() { formError.value = '' }

async function onMutated() {
  dashboardStore.invalidate()
  reportStore.invalidate()
  incomeStore.invalidate()
  expenseStore.invalidate()
  await Promise.all([store.reload(), optionsStore.reload()])
}

async function submitAdd() {
  saving.value = true; formError.value = ''
  try {
    const fd = addFormRef.value?.getFormData()
    if (!fd) {
      formError.value = addFormRef.value?.getValidationError() || 'Complete el nombre y un presupuesto mayor a 0.'
      return
    }
    const { data } = await api.createProject(fd)
    if (data.success) { showAdd.value = false; await onMutated(); toast.success(data.messages) }
    else formError.value = data.messages
  } catch (e) { formError.value = e.response?.data?.messages || 'Error de comunicación.' }
  finally { saving.value = false }
}

async function submitEdit() {
  saving.value = true; formError.value = ''
  try {
    const fd = editFormRef.value?.getFormData()
    if (!fd) {
      formError.value = editFormRef.value?.getValidationError() || 'Complete el nombre y un presupuesto mayor a 0.'
      return
    }
    const { data } = await api.editProject(fd)
    if (data.success) { showEdit.value = false; await onMutated(); toast.success(data.messages) }
    else formError.value = data.messages
  } catch (e) { formError.value = e.response?.data?.messages || 'Error de comunicación.' }
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
  if (!(await confirmDelete('Esta acción no se puede deshacer.', '¿Eliminar este proyecto?'))) return
  const { data } = await api.removeProject(id)
  if (data.success) { await onMutated(); toast.success(data.messages) }
  else toast.danger(data.messages)
}

onMounted(async () => {
  await store.load()
})
</script>
