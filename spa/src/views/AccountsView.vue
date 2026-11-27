<template>
  <div>
    <ol class="breadcrumb">
      <li><a href="#" @click.prevent="$router.push('/dashboard')">Inicio</a></li>
      <li class="active">Cuentas y Categorías</li>
    </ol>

    <!-- Panel 1: Accounts (Bancos, Billeteras, Efectivo) -->
    <div class="panel panel-default" style="margin-bottom:25px;">
      <DataTable
        title="Mis Cuentas Financieras y Efectivo"
        icon="glyphicon-credit-card text-primary"
        :columns="accountColumns"
        :store="accountStore"
      >
        <template #actions>
          <button class="btn btn-primary" @click="openAddAccount">
            <i class="glyphicon glyphicon-plus-sign"></i> Nueva Cuenta
          </button>
        </template>

        <template #cell-account_name="{ row }">
          <span :style="{ display:'inline-block', width:'10px', height:'10px', borderRadius:'50%', background: row.color || '#2e6da4', marginRight:'6px' }"></span>
          <strong>{{ row.account_name }}</strong>
        </template>
        <template #cell-account_type="{ row }">
          <span :class="'label label-' + accountTypeMeta(row.account_type).cls">
            {{ accountTypeMeta(row.account_type).label }}
          </span>
        </template>
        <template #cell-account_number="{ row }">{{ row.account_number || '—' }}</template>
        <template #cell-initial_balance="{ row }">S/ {{ fmtMoney(row.initial_balance) }}</template>
        <template #cell-current_balance="{ row }">
          <strong :class="row.current_balance >= 0 ? 'text-success' : 'text-danger'">
            S/ {{ fmtMoney(row.current_balance) }}
          </strong>
        </template>
        <template #cell-status="{ row }">
          <span :class="row.status === 1 ? 'label label-success' : 'label label-default'">
            {{ row.status === 1 ? 'Activa' : 'Inactiva' }}
          </span>
        </template>
        <template #cell-actions="{ row }">
          <ActionDropdown>
            <li><a href="#" @click.prevent="editAccount(row.id)"><i class="glyphicon glyphicon-edit"></i> Editar</a></li>
            <li class="divider"></li>
            <li><a href="#" @click.prevent="removeAccount(row.id)"><i class="glyphicon glyphicon-trash text-danger"></i> Eliminar</a></li>
          </ActionDropdown>
        </template>
      </DataTable>
    </div>

    <!-- Panel 2: Financial Categories (Ingresos y Gastos) -->
    <div class="panel panel-default">
      <DataTable
        title="Mis Categorías de Ingresos y Gastos"
        icon="glyphicon-tags text-info"
        :columns="categoryColumns"
        :store="finCategoryStore"
      >
        <template #actions>
          <button class="btn btn-info" @click="openAddCategory">
            <i class="glyphicon glyphicon-plus-sign"></i> Nueva Categoría
          </button>
        </template>

        <template #cell-category_name="{ row }">
          <span :style="{ display:'inline-block', width:'10px', height:'10px', borderRadius:'50%', background: row.color || '#5bc0de', marginRight:'6px' }"></span>
          <strong>{{ row.category_name }}</strong>
        </template>
        <template #cell-category_type="{ row }">
          <span :class="row.category_type === 'INCOME' ? 'label label-success' : 'label label-danger'">
            {{ row.category_type === 'INCOME' ? 'Ingreso' : 'Gasto' }}
          </span>
        </template>
        <template #cell-status="{ row }">
          <span :class="row.status === 1 ? 'label label-success' : 'label label-default'">
            {{ row.status === 1 ? 'Activa' : 'Inactiva' }}
          </span>
        </template>
        <template #cell-actions="{ row }">
          <ActionDropdown>
            <li><a href="#" @click.prevent="editFinCategory(row.id)"><i class="glyphicon glyphicon-edit"></i> Editar</a></li>
            <li class="divider"></li>
            <li><a href="#" @click.prevent="removeFinCategory(row.id)"><i class="glyphicon glyphicon-trash text-danger"></i> Eliminar</a></li>
          </ActionDropdown>
        </template>
      </DataTable>
    </div>

    <!-- Add Account Modal -->
    <AppModal v-model="showAddAccount" title="Nueva Cuenta" icon="glyphicon-plus" @closed="resetAccountForm">
      <template #body>
        <div v-if="accountFormError" class="alert alert-danger">{{ accountFormError }}</div>
        <AccountForm ref="addAccountFormRef" />
      </template>
      <template #footer>
        <button class="btn btn-default" @click="showAddAccount = false">Cancelar</button>
        <button class="btn btn-primary" :disabled="savingAccount" @click="submitAddAccount">
          <i class="glyphicon glyphicon-floppy-disk"></i>
          {{ savingAccount ? 'Guardando…' : 'Guardar' }}
        </button>
      </template>
    </AppModal>

    <!-- Edit Account Modal -->
    <AppModal v-model="showEditAccount" title="Editar Cuenta" icon="glyphicon-edit" @closed="resetAccountForm">
      <template #body>
        <div v-if="accountFormError" class="alert alert-danger">{{ accountFormError }}</div>
        <AccountForm ref="editAccountFormRef" :edit-id="editAccountId" />
      </template>
      <template #footer>
        <button class="btn btn-default" @click="showEditAccount = false">Cancelar</button>
        <button class="btn btn-primary" :disabled="savingAccount" @click="submitEditAccount">
          <i class="glyphicon glyphicon-floppy-disk"></i>
          {{ savingAccount ? 'Guardando…' : 'Actualizar' }}
        </button>
      </template>
    </AppModal>

    <!-- Add Category Modal -->
    <AppModal v-model="showAddCategory" title="Nueva Categoría" icon="glyphicon-tag" header-color="#31b0d5" @closed="resetCategoryForm">
      <template #body>
        <div v-if="categoryFormError" class="alert alert-danger">{{ categoryFormError }}</div>
        <FinCategoryForm ref="addCategoryFormRef" />
      </template>
      <template #footer>
        <button class="btn btn-default" @click="showAddCategory = false">Cancelar</button>
        <button class="btn btn-info" :disabled="savingCategory" @click="submitAddCategory">
          <i class="glyphicon glyphicon-floppy-disk"></i>
          {{ savingCategory ? 'Guardando…' : 'Guardar' }}
        </button>
      </template>
    </AppModal>

    <!-- Edit Category Modal -->
    <AppModal v-model="showEditCategory" title="Editar Categoría" icon="glyphicon-edit" header-color="#31b0d5" @closed="resetCategoryForm">
      <template #body>
        <div v-if="categoryFormError" class="alert alert-danger">{{ categoryFormError }}</div>
        <FinCategoryForm ref="editCategoryFormRef" :edit-id="editCategoryId" />
      </template>
      <template #footer>
        <button class="btn btn-default" @click="showEditCategory = false">Cancelar</button>
        <button class="btn btn-info" :disabled="savingCategory" @click="submitEditCategory">
          <i class="glyphicon glyphicon-floppy-disk"></i>
          {{ savingCategory ? 'Guardando…' : 'Actualizar' }}
        </button>
      </template>
    </AppModal>
  </div>
</template>

<script setup>
import { ref, inject, nextTick, onMounted } from 'vue'
import { useAccountStore } from '@/stores/useAccountStore'
import { useFinCategoryStore } from '@/stores/useFinCategoryStore'
import { useDashboardStore } from '@/stores/useDashboardStore'
import { useReportStore } from '@/stores/useReportStore'
import { useFormOptionsStore } from '@/stores/useFormOptionsStore'
import DataTable       from '@/components/DataTable.vue'
import ActionDropdown  from '@/components/ActionDropdown.vue'
import AppModal        from '@/components/AppModal.vue'
import AccountForm     from '@/components/forms/AccountForm.vue'
import FinCategoryForm from '@/components/forms/FinCategoryForm.vue'
import { fmtMoney }      from '@/utils/format'
import { confirmDelete } from '@/utils/confirmDelete'
import * as api from '@/api'

const toast            = inject('toast')
const accountStore     = useAccountStore()
const finCategoryStore = useFinCategoryStore()
const dashboardStore   = useDashboardStore()
const reportStore      = useReportStore()
const optionsStore     = useFormOptionsStore()

const accountColumns = [
  { key: '_index',          label: '#' },
  { key: 'account_name',    label: 'Cuenta' },
  { key: 'account_type',    label: 'Tipo' },
  { key: 'account_number',  label: 'N° Cuenta' },
  { key: 'currency',        label: 'Moneda' },
  { key: 'initial_balance', label: 'Saldo Inicial' },
  { key: 'current_balance', label: 'Saldo Actual' },
  { key: 'status',          label: 'Estado' },
  { key: 'actions',         label: 'Acción' },
]

const categoryColumns = [
  { key: '_index',        label: '#' },
  { key: 'category_name', label: 'Nombre de Categoría' },
  { key: 'category_type', label: 'Tipo' },
  { key: 'status',        label: 'Estado' },
  { key: 'actions',       label: 'Acción' },
]

function accountTypeMeta(type) {
  const map = {
    BANK:           { cls: 'primary', label: 'Banco' },
    DIGITAL_WALLET: { cls: 'info',    label: 'Billetera Digital' },
    CASH:           { cls: 'success', label: 'Efectivo' },
    SAVINGS:        { cls: 'warning', label: 'Ahorros' },
    OTHER:          { cls: 'default', label: 'Otro' },
  }
  return map[type] || { cls: 'default', label: type || 'Otro' }
}

// Account Modal State
const showAddAccount     = ref(false)
const showEditAccount    = ref(false)
const savingAccount      = ref(false)
const accountFormError   = ref('')
const editAccountId      = ref(null)
const addAccountFormRef  = ref(null)
const editAccountFormRef = ref(null)

// Category Modal State
const showAddCategory     = ref(false)
const showEditCategory    = ref(false)
const savingCategory      = ref(false)
const categoryFormError   = ref('')
const editCategoryId      = ref(null)
const addCategoryFormRef  = ref(null)
const editCategoryFormRef = ref(null)

function openAddAccount()   { resetAccountForm(); showAddAccount.value = true }
function resetAccountForm() { accountFormError.value = '' }

function openAddCategory()   { resetCategoryForm(); showAddCategory.value = true }
function resetCategoryForm() { categoryFormError.value = '' }

async function onAccountMutated() {
  dashboardStore.invalidate()
  reportStore.invalidate()
  await Promise.all([accountStore.reload(), optionsStore.reload()])
}

async function onCategoryMutated() {
  await Promise.all([finCategoryStore.reload(), optionsStore.reload()])
}

async function submitAddAccount() {
  savingAccount.value = true; accountFormError.value = ''
  try {
    const fd = addAccountFormRef.value?.getFormData()
    if (!fd) { accountFormError.value = 'Complete los campos requeridos.'; return }
    const { data } = await api.createAccount(fd)
    if (data.success) { showAddAccount.value = false; await onAccountMutated(); toast.success(data.messages) }
    else accountFormError.value = data.messages
  } catch { accountFormError.value = 'Error de comunicación.' }
  finally { savingAccount.value = false }
}

async function submitEditAccount() {
  savingAccount.value = true; accountFormError.value = ''
  try {
    const fd = editAccountFormRef.value?.getFormData()
    if (!fd) { accountFormError.value = 'Complete los campos requeridos.'; return }
    const { data } = await api.editAccount(fd)
    if (data.success) { showEditAccount.value = false; await onAccountMutated(); toast.success(data.messages) }
    else accountFormError.value = data.messages
  } catch { accountFormError.value = 'Error de comunicación.' }
  finally { savingAccount.value = false }
}

async function submitAddCategory() {
  savingCategory.value = true; categoryFormError.value = ''
  try {
    const fd = addCategoryFormRef.value?.getFormData()
    if (!fd) { categoryFormError.value = 'Complete los campos requeridos.'; return }
    const { data } = await api.createFinCategory(fd)
    if (data.success) { showAddCategory.value = false; await onCategoryMutated(); toast.success(data.messages) }
    else categoryFormError.value = data.messages
  } catch { categoryFormError.value = 'Error de comunicación.' }
  finally { savingCategory.value = false }
}

async function submitEditCategory() {
  savingCategory.value = true; categoryFormError.value = ''
  try {
    const fd = editCategoryFormRef.value?.getFormData()
    if (!fd) { categoryFormError.value = 'Complete los campos requeridos.'; return }
    const { data } = await api.editFinCategory(fd)
    if (data.success) { showEditCategory.value = false; await onCategoryMutated(); toast.success(data.messages) }
    else categoryFormError.value = data.messages
  } catch { categoryFormError.value = 'Error de comunicación.' }
  finally { savingCategory.value = false }
}

async function editAccount(id) {
  const { data } = await api.getAccountData(id)
  if (data.success) {
    resetAccountForm()
    editAccountId.value = id
    showEditAccount.value = true
    await nextTick()
    editAccountFormRef.value?.setData(data.data)
  } else {
    toast.danger(data.messages || 'No se pudo cargar la cuenta.')
  }
}

async function removeAccount(id) {
  if (!(await confirmDelete('Los movimientos asociados quedarán sin cuenta.', '¿Eliminar esta cuenta?'))) return
  const { data } = await api.removeAccount(id)
  if (data.success) { await onAccountMutated(); toast.success(data.messages) }
  else toast.danger(data.messages)
}

async function editFinCategory(id) {
  const { data } = await api.getFinCategoryData(id)
  if (data.success) {
    resetCategoryForm()
    editCategoryId.value = id
    showEditCategory.value = true
    await nextTick()
    editCategoryFormRef.value?.setData(data.data)
  } else {
    toast.danger(data.messages || 'No se pudo cargar la categoría.')
  }
}

async function removeFinCategory(id) {
  if (!(await confirmDelete('Los movimientos existentes conservarán su historial.', '¿Eliminar esta categoría?'))) return
  const { data } = await api.removeFinCategory(id)
  if (data.success) { await onCategoryMutated(); toast.success(data.messages) }
  else toast.danger(data.messages)
}

onMounted(async () => {
  await Promise.all([accountStore.load(), finCategoryStore.load()])
})
</script>
