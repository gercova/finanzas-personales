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

    <div class="panel panel-default">
      <DataTable
        title="Gestión de Gastos"
        icon="glyphicon-arrow-down text-danger"
        :columns="columns"
        :store="store"
      >
        <template #actions>
          <button class="btn btn-default btn-sm" @click="importRef.open()">
            <i class="glyphicon glyphicon-upload"></i> Importar
          </button>
          <button class="btn btn-danger" @click="openAdd">
            <i class="glyphicon glyphicon-plus-sign"></i> Registrar Gasto
          </button>
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
  if (!confirm('¿Eliminar este gasto?')) return
  const { data } = await api.removeExpense(id)
  if (data.success) { await onMutated(); toast.success(data.messages) }
  else toast.danger(data.messages)
}

onMounted(async () => {
  await Promise.all([store.load(), dashboardStore.load(), optionsStore.load()])
})
</script>
