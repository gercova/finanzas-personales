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
        :headers="['#','Cuenta','Tipo','N° Cuenta','Moneda','Saldo Inicial','Saldo Actual','Estado','Acción']"
        :store="accountStore"
      >
        <template #actions>
          <button class="btn btn-primary" @click="openAddAccount">
            <i class="glyphicon glyphicon-plus-sign"></i> Nueva Cuenta
          </button>
        </template>
      </DataTable>
    </div>

    <!-- Panel 2: Financial Categories (Ingresos y Gastos) -->
    <div class="panel panel-default">
      <DataTable
        title="Mis Categorías de Ingresos y Gastos"
        icon="glyphicon-tags text-info"
        :headers="['#','Nombre de Categoría','Tipo','Estado','Acción']"
        :store="finCategoryStore"
      >
        <template #actions>
          <button class="btn btn-info" @click="openAddCategory">
            <i class="glyphicon glyphicon-plus-sign"></i> Nueva Categoría
          </button>
        </template>
      </DataTable>
    </div>

    <!-- ===== ACCOUNT MODALS ===== -->
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

    <!-- ===== FINANCIAL CATEGORY MODALS ===== -->
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
import { ref, inject, onMounted, onUnmounted } from 'vue'
import { useAccountStore } from '@/stores/useAccountStore'
import { useFinCategoryStore } from '@/stores/useFinCategoryStore'
import DataTable from '@/components/DataTable.vue'
import AppModal  from '@/components/AppModal.vue'
import AccountForm from '@/components/forms/AccountForm.vue'
import FinCategoryForm from '@/components/forms/FinCategoryForm.vue'
import * as api from '@/api'

const toast            = inject('toast')
const accountStore     = useAccountStore()
const finCategoryStore = useFinCategoryStore()

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

async function submitAddAccount() {
  savingAccount.value = true
  accountFormError.value = ''
  try {
    const fd = addAccountFormRef.value?.getFormData()
    if (!fd) { accountFormError.value = 'Complete los campos requeridos.'; return }
    const { data } = await api.createAccount(fd)
    if (data.success) {
      showAddAccount.value = false
      await accountStore.reload()
      toast.success(data.messages)
    } else {
      accountFormError.value = data.messages
    }
  } catch {
    accountFormError.value = 'Error de comunicación.'
  } finally {
    savingAccount.value = false
  }
}

async function submitEditAccount() {
  savingAccount.value = true
  accountFormError.value = ''
  try {
    const fd = editAccountFormRef.value?.getFormData()
    if (!fd) { accountFormError.value = 'Complete los campos requeridos.'; return }
    const { data } = await api.editAccount(fd)
    if (data.success) {
      showEditAccount.value = false
      await accountStore.reload()
      toast.success(data.messages)
    } else {
      accountFormError.value = data.messages
    }
  } catch {
    accountFormError.value = 'Error de comunicación.'
  } finally {
    savingAccount.value = false
  }
}

async function submitAddCategory() {
  savingCategory.value = true
  categoryFormError.value = ''
  try {
    const fd = addCategoryFormRef.value?.getFormData()
    if (!fd) { categoryFormError.value = 'Complete los campos requeridos.'; return }
    const { data } = await api.createFinCategory(fd)
    if (data.success) {
      showAddCategory.value = false
      await finCategoryStore.reload()
      toast.success(data.messages)
    } else {
      categoryFormError.value = data.messages
    }
  } catch {
    categoryFormError.value = 'Error de comunicación.'
  } finally {
    savingCategory.value = false
  }
}

async function submitEditCategory() {
  savingCategory.value = true
  categoryFormError.value = ''
  try {
    const fd = editCategoryFormRef.value?.getFormData()
    if (!fd) { categoryFormError.value = 'Complete los campos requeridos.'; return }
    const { data } = await api.editFinCategory(fd)
    if (data.success) {
      showEditCategory.value = false
      await finCategoryStore.reload()
      toast.success(data.messages)
    } else {
      categoryFormError.value = data.messages
    }
  } catch {
    categoryFormError.value = 'Error de comunicación.'
  } finally {
    savingCategory.value = false
  }
}

async function handleTableAction(e) {
  const el = e.target.closest('a')
  if (!el) return

  // Accounts
  if (el.classList.contains('edit-account')) {
    e.preventDefault()
    const id = parseInt(el.dataset.id)
    const { data } = await api.getAccountData(id)
    if (data.success) {
      editAccountId.value = id
      showEditAccount.value = true
      setTimeout(() => editAccountFormRef.value?.setData(data.data), 50)
    }
  }
  if (el.classList.contains('remove-account')) {
    e.preventDefault()
    if (!confirm('¿Eliminar esta cuenta? Los movimientos asociados quedarán sin cuenta.')) return
    const { data } = await api.removeAccount(parseInt(el.dataset.id))
    if (data.success) {
      await accountStore.reload()
      toast.success(data.messages)
    } else {
      toast.danger(data.messages)
    }
  }

  // Financial Categories
  if (el.classList.contains('edit-fin-category')) {
    e.preventDefault()
    const id = parseInt(el.dataset.id)
    const { data } = await api.getFinCategoryData(id)
    if (data.success) {
      editCategoryId.value = id
      showEditCategory.value = true
      setTimeout(() => editCategoryFormRef.value?.setData(data.data), 50)
    }
  }
  if (el.classList.contains('remove-fin-category')) {
    e.preventDefault()
    if (!confirm('¿Eliminar esta categoría? Los movimientos existentes conservarán su historial.')) return
    const { data } = await api.removeFinCategory(parseInt(el.dataset.id))
    if (data.success) {
      await finCategoryStore.reload()
      toast.success(data.messages)
    } else {
      toast.danger(data.messages)
    }
  }
}

onMounted(async () => {
  await Promise.all([accountStore.load(), finCategoryStore.load()])
  document.addEventListener('click', handleTableAction)
})

onUnmounted(() => {
  document.removeEventListener('click', handleTableAction)
})
</script>
