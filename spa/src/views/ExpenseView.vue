<template>
  <div>
    <ol class="breadcrumb">
      <li><a href="#" @click.prevent="$router.push('/dashboard')">Inicio</a></li>
      <li class="active">Gastos</li>
    </ol>

    <!-- Spending Quota Alert Banner -->
    <div v-if="spendLimit && spendLimit.monthly_limit > 0" :class="'alert ' + (alertClass || 'alert-info')" style="margin-bottom:15px;">
      <strong><i class="glyphicon glyphicon-stats"></i> Límite mensual de gastos</strong>
      &mdash; Has gastado <strong>S/ {{ fmtMoney(monthlySpent) }}</strong>
      de <strong>S/ {{ fmtMoney(spendLimit.monthly_limit) }}</strong>
      ({{ quotaPct }}%)
      <div class="progress" style="margin-top:8px; margin-bottom:0; height:10px;">
        <div
          :class="'progress-bar progress-bar-' + quotaBarClass"
          role="progressbar"
          :style="{ width: Math.min(quotaPct, 100) + '%', minWidth: '2px' }"
        ></div>
      </div>
    </div>

    <!-- Clickable quick status & receipt filters -->
    <div class="quick-filter-bar">
      <button type="button" class="quick-filter-btn qf-paid"
              style="background-color:#1f8a4c; border-color:#1f8a4c;"
              :class="{ 'is-selected': store.filters.status==='PAGADO' }"
              @click="toggleStatusQuick('PAGADO')">
        PAGADO
      </button>
      <button type="button" class="quick-filter-btn qf-partial"
              :class="{ 'is-selected': store.filters.status==='PENDIENTE' }"
              @click="toggleStatusQuick('PENDIENTE')">
        PENDIENTE
      </button>
      <button type="button" class="quick-filter-btn qf-outline-warn"
              :class="{ 'is-selected': store.filters.receiptFilter==='WITH' }"
              @click="store.filters.receiptFilter = store.filters.receiptFilter === 'WITH' ? '' : 'WITH'">
        <i class="glyphicon glyphicon-picture"></i> Con comprobante
      </button>
      <button type="button" class="quick-filter-btn qf-outline-danger"
              :class="{ 'is-selected': store.filters.receiptFilter==='NONE' }"
              @click="store.filters.receiptFilter = store.filters.receiptFilter === 'NONE' ? '' : 'NONE'">
        <i class="glyphicon glyphicon-ban-circle"></i> Sin comprobante
      </button>
      <button v-if="store.filters.status || store.filters.receiptFilter" type="button" class="btn btn-xs btn-link" style="padding:2px 6px;" @click="store.filters.status = ''; store.filters.receiptFilter = ''">
        Ver todos
      </button>
    </div>

    <div class="panel panel-default">
      <DataTable
        title="Gestión de Gastos"
        icon="glyphicon-arrow-down text-danger"
        :columns="columns"
        :store="store"
      >
        <template #actions>
          <button type="button" class="btn btn-import" @click="importRef.open()">
            <i class="glyphicon glyphicon-upload"></i> Importar
          </button>
          <button type="button" class="btn btn-danger" @click="openAdd">
            <i class="glyphicon glyphicon-plus-sign"></i> Registrar Gasto
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
                <span v-if="store.activeFilterCount > 0 || store.search" class="text-danger" style="margin-left:10px; font-weight:600;">
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
                  <option v-for="p in projects" :key="p.id" :value="String(p.id)">{{ p.project_name }}</option>
                </select>
              </div>

              <div class="col-xs-12 col-sm-6 col-md-3" style="margin-bottom:6px;">
                <label class="control-label" style="font-size:11.5px; color:#555; margin-bottom:3px; display:block;">
                  <i class="glyphicon glyphicon-tag"></i> Estado
                </label>
                <select class="form-control input-sm" v-model="store.filters.status">
                  <option value="">Todos los estados</option>
                  <option value="PAGADO">Pagado</option>
                  <option value="PENDIENTE">Pendiente</option>
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
                  <i class="glyphicon glyphicon-picture"></i> Comprobante
                </label>
                <select class="form-control input-sm" v-model="store.filters.receiptFilter">
                  <option value="">Todos</option>
                  <option value="WITH">Con comprobante</option>
                  <option value="NONE">Sin comprobante</option>
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

        <template #cell-account_name="{ row }">{{ row.account_name || '-' }}</template>
        <template #cell-category_name="{ row }">{{ row.category_name || '-' }}</template>
        <template #cell-expense_date="{ row }">{{ fmtDate(row.expense_date) }}</template>
        <template #cell-amount="{ row }">S/ {{ fmtMoney(row.amount) }}</template>
        <template #cell-receipt_image="{ row }">
          <a v-if="row.receipt_image" :href="api.assetUrl(row.receipt_image)" target="_blank" rel="noopener">
            <img :src="api.assetUrl(row.receipt_image)" alt="Comprobante" style="height:36px;border-radius:3px;border:1px solid #ddd;" />
          </a>
          <span v-else>-</span>
        </template>
        <template #cell-status="{ row }">
          <span :class="row.status === 'PAGADO' ? 'label label-success' : 'label label-warning'">
            {{ row.status === 'PAGADO' ? 'PAGADO' : 'PENDIENTE' }}
          </span>
        </template>
        <template #cell-actions="{ row }">
          <ActionDropdown label="">
            <li><a href="#" @click.prevent="editExpense(row.id)"><i class="glyphicon glyphicon-edit"></i> Editar</a></li>
            <li class="divider"></li>
            <li><a href="#" @click.prevent="removeExpense(row.id)"><i class="glyphicon glyphicon-trash text-danger"></i> Eliminar</a></li>
          </ActionDropdown>
        </template>
      </DataTable>
    </div>

    <!-- Add Modal -->
    <AppModal v-model="showAdd" title="Registrar Gasto" icon="glyphicon-plus-sign"
              header-color="#c0392b" size="lg" @closed="resetForm">
      <template #body>
        <div v-if="formError" class="alert alert-danger">{{ formError }}</div>
        <ExpenseForm ref="addFormRef" :accounts="accounts" :categories="categories" :projects="projects" />
      </template>
      <template #footer>
        <button class="btn btn-default" @click="showAdd = false">Cancelar</button>
        <button class="btn btn-danger" :disabled="saving" @click="submitAdd">
          <i class="glyphicon glyphicon-floppy-disk"></i>
          {{ saving ? 'Guardando…' : 'Guardar' }}
        </button>
      </template>
    </AppModal>

    <!-- Edit Modal -->
    <AppModal v-model="showEdit" title="Editar Gasto" icon="glyphicon-edit"
              header-color="#2980b9" size="lg" @closed="resetForm">
      <template #body>
        <div v-if="formError" class="alert alert-danger">{{ formError }}</div>
        <ExpenseForm ref="editFormRef" :accounts="accounts" :categories="categories" :projects="projects" :edit-id="editId" />
      </template>
      <template #footer>
        <button class="btn btn-default" @click="showEdit = false">Cancelar</button>
        <button class="btn btn-warning" :disabled="saving" @click="submitEdit">
          <i class="glyphicon glyphicon-floppy-disk"></i>
          {{ saving ? 'Guardando…' : 'Actualizar' }}
        </button>
      </template>
    </AppModal>

    <ImportModal ref="importRef" module="expense" @imported="onMutated" />
  </div>
</template>

<script setup>
import { ref, computed, inject, nextTick, onMounted } from 'vue'
import { useExpenseStore } from '@/stores/useExpenseStore'
import { useDashboardStore } from '@/stores/useDashboardStore'
import { useReportStore } from '@/stores/useReportStore'
import { useAccountStore } from '@/stores/useAccountStore'
import { useFormOptionsStore } from '@/stores/useFormOptionsStore'
import { useAuthStore } from '@/stores/useAuthStore'
import DataTable      from '@/components/DataTable.vue'
import ActionDropdown from '@/components/ActionDropdown.vue'
import AppModal       from '@/components/AppModal.vue'
import ImportModal    from '@/components/ImportModal.vue'
import ExpenseForm    from '@/components/forms/ExpenseForm.vue'
import { fmtMoney, fmtDate } from '@/utils/format'
import { confirmDelete }     from '@/utils/confirmDelete'
import { getDatePresetRange, buildRefOptions } from '@/utils/filters'
import * as api from '@/api'

const toast          = inject('toast')
const store          = useExpenseStore()
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
  { key: 'expense_date',  label: 'Fecha' },
  { key: 'amount',        label: 'Monto' },
  { key: 'receipt_image', label: 'Comprobante' },
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
const categories = computed(() => optionsStore.expenseCategories)
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

const spendLimit = computed(() => {
  const kpi = dashboardStore.kpi
  if (kpi && kpi.monthly_limit > 0) {
    return {
      monthly_limit: kpi.monthly_limit,
      warning_threshold: kpi.warning_threshold || 75,
      critical_threshold: kpi.critical_threshold || 90
    }
  }
  return null
})

const monthlySpent = computed(() => {
  const curMonth = new Date().toLocaleDateString('en-CA').slice(0, 7)
  let sum = 0
  for (const r of store.rows) {
    if (r.expense_date?.startsWith(curMonth)) sum += r.amount || 0
  }
  return sum
})

const quotaPct = computed(() => {
  if (!spendLimit.value || spendLimit.value.monthly_limit <= 0) return 0
  return Math.round((monthlySpent.value / spendLimit.value.monthly_limit) * 1000) / 10
})

const quotaBarClass = computed(() => {
  if (!spendLimit.value) return 'success'
  if (quotaPct.value >= spendLimit.value.critical_threshold) return 'danger'
  if (quotaPct.value >= spendLimit.value.warning_threshold) return 'warning'
  return 'success'
})

const alertClass = computed(() => {
  if (quotaBarClass.value === 'danger') return 'alert-danger'
  if (quotaBarClass.value === 'warning') return 'alert-warning'
  return ''
})

function openAdd()   { resetForm(); showAdd.value = true }
function resetForm() { formError.value = '' }

async function onMutated() {
  reportStore.invalidate()
  accountStore.invalidate()
  await Promise.all([store.reload(), dashboardStore.reload(), authStore.refreshHeaderMetrics()])
}

async function submitAdd() {
  saving.value = true; formError.value = ''
  try {
    const fd = addFormRef.value?.getFormData()
    if (!fd) { formError.value = 'Complete los campos requeridos y verifique que el monto sea mayor a 0.'; return }
    const { data } = await api.createExpense(fd)
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
    const { data } = await api.editExpense(fd)
    if (data.success) { showEdit.value = false; await onMutated(); toast.success(data.messages) }
    else formError.value = data.messages
  } catch { formError.value = 'Error de comunicación.' }
  finally { saving.value = false }
}

async function editExpense(id) {
  const { data } = await api.getExpenseData(id)
  if (data.success) {
    resetForm()
    editId.value = id
    showEdit.value = true
    await nextTick()
    editFormRef.value?.setData(data.data)
  } else {
    toast.danger(data.messages || 'No se pudo cargar el gasto.')
  }
}

async function removeExpense(id) {
  if (!(await confirmDelete('Esta acción revertirá el ajuste en la cuenta asociada.', '¿Eliminar este gasto?'))) return
  const { data } = await api.removeExpense(id)
  if (data.success) { await onMutated(); toast.success(data.messages) }
  else toast.danger(data.messages)
}

onMounted(async () => {
  await Promise.all([store.load(), dashboardStore.load(), optionsStore.load()])
})
</script>
