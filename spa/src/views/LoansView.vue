<template>
  <div>
    <ol class="breadcrumb">
      <li><a href="#" @click.prevent="$router.push('/dashboard')">Inicio</a></li>
      <li v-if="currentTab === 'loans'" class="active">Préstamos</li>
      <template v-else>
        <li><a href="#" @click.prevent="switchTab('loans')">Préstamos</a></li>
        <li class="active">Prestatarios</li>
      </template>
    </ol>

    <!-- Navigation Tabs between Loans and Borrowers -->
    <ul class="nav nav-tabs" style="margin-bottom:20px;">
      <li :class="{ active: currentTab === 'loans' }">
        <a href="#" @click.prevent="switchTab('loans')">
          <i class="glyphicon glyphicon-retweet text-warning"></i> Gestión de Préstamos
        </a>
      </li>
      <li :class="{ active: currentTab === 'borrowers' }">
        <a href="#" @click.prevent="switchTab('borrowers')">
          <i class="glyphicon glyphicon-user text-primary"></i> Prestatarios
        </a>
      </li>
    </ul>

    <!-- ── TAB 1: GESTIÓN DE PRÉSTAMOS ───────────────────────────────── -->
    <div v-show="currentTab === 'loans'">
      <!-- Stat cards matching loans.php -->
      <div class="row" style="margin-bottom:20px;">
        <div class="col-xs-12 col-sm-4">
          <div class="panel panel-danger">
            <div class="panel-heading"><i class="glyphicon glyphicon-share-alt"></i> Total Prestado</div>
            <div class="panel-body text-center">
              <h3 style="margin:0;font-weight:700;" class="text-danger">S/ {{ fmt(loanStats.prestado) }}</h3>
              <small class="text-muted">préstamos activos</small>
            </div>
          </div>
        </div>
        <div class="col-xs-12 col-sm-4">
          <div class="panel panel-success">
            <div class="panel-heading"><i class="glyphicon glyphicon-ok-sign"></i> Total Cobrado</div>
            <div class="panel-body text-center">
              <h3 style="margin:0;font-weight:700;" class="text-success">S/ {{ fmt(loanStats.cobrado) }}</h3>
              <small class="text-muted">recuperado</small>
            </div>
          </div>
        </div>
        <div class="col-xs-12 col-sm-4">
          <div class="panel panel-warning">
            <div class="panel-heading"><i class="glyphicon glyphicon-time"></i> Total Pendiente</div>
            <div class="panel-body text-center">
              <h3 style="margin:0;font-weight:700;" class="text-warning">S/ {{ fmt(loanStats.pendiente) }}</h3>
              <small class="text-muted">por cobrar</small>
            </div>
          </div>
        </div>
      </div>

      <div style="margin-bottom:12px;">
        <span class="label" style="background:#e74c3c;padding:4px 10px;">PENDIENTE</span>&nbsp;
        <span class="label" style="background:#f39c12;padding:4px 10px;">PARCIAL</span>&nbsp;
        <span class="label" style="background:#8e44ad;padding:4px 10px;">PAGADO</span>
      </div>

      <div class="panel panel-default">
        <DataTable
          title="Gestión de Préstamos"
          icon="glyphicon-share-alt text-danger"
          :headers="['#','Prestatario','Concepto','Cuenta','Fecha Préstamo','Vencimiento','Capital','Interés','Total a Cobrar','Cobrado','Pendiente','Estado','Acción']"
          :store="store"
        >
          <template #actions>
            <button class="btn btn-default btn-sm" @click="importRef.open()">
              <i class="glyphicon glyphicon-upload"></i> Importar
            </button>
            <button class="btn btn-danger" @click="openAdd">
              <i class="glyphicon glyphicon-plus-sign"></i> Registrar Préstamo
            </button>
          </template>
        </DataTable>
      </div>
    </div>

    <!-- ── TAB 2: LISTADO DE PRESTATARIOS (Matching Reference Image) ──── -->
    <div v-show="currentTab === 'borrowers'">
      <div class="panel panel-default">
        <DataTable
          title="Listado de prestatarios"
          icon="glyphicon-edit"
          :headers="['#','Documento','Nombre / Razón Social','Teléfono','Dirección','Estado','Opciones']"
          :store="borrowerStore"
        >
          <template #actions>
            <button class="btn btn-default" @click="openAddBorrower">
              <i class="glyphicon glyphicon-plus-sign"></i> Agregar prestatario
            </button>
          </template>
        </DataTable>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODALS: LOANS                                                  -->
    <!-- ============================================================== -->

    <!-- Add Loan Modal -->
    <AppModal v-model="showAdd" title="Registrar Préstamo" icon="glyphicon-plus-sign"
              header-color="#c0392b" size="lg" @closed="resetForm">
      <template #body>
        <div v-if="formError" class="alert alert-danger">{{ formError }}</div>
        <LoanForm
          ref="addFormRef"
          :accounts="accounts"
          :borrowers="borrowers"
          @request-new-borrower="openAddBorrowerFromLoan"
        />
      </template>
      <template #footer>
        <button class="btn btn-default" @click="showAdd = false">Cancelar</button>
        <button class="btn btn-danger" :disabled="saving" @click="submitAdd">
          <i class="glyphicon glyphicon-floppy-disk"></i>
          {{ saving ? 'Guardando…' : 'Guardar' }}
        </button>
      </template>
    </AppModal>

    <!-- Edit Loan Modal -->
    <AppModal v-model="showEdit" title="Editar Préstamo" icon="glyphicon-edit"
              header-color="#2980b9" size="lg" @closed="resetForm">
      <template #body>
        <div v-if="formError" class="alert alert-danger">{{ formError }}</div>
        <LoanForm
          ref="editFormRef"
          :accounts="accounts"
          :borrowers="borrowers"
          :edit-id="editId"
          @request-new-borrower="openAddBorrowerFromLoan"
        />
      </template>
      <template #footer>
        <button class="btn btn-default" @click="showEdit = false">Cancelar</button>
        <button class="btn btn-primary" :disabled="saving" @click="submitEdit">
          <i class="glyphicon glyphicon-floppy-disk"></i>
          {{ saving ? 'Guardando…' : 'Actualizar' }}
        </button>
      </template>
    </AppModal>

    <!-- Payment Modal -->
    <AppModal v-model="showPay" title="Registrar Pago de Préstamo" icon="glyphicon-usd"
              header-color="#27ae60" @closed="resetForm">
      <template #body>
        <div v-if="formError" class="alert alert-danger">{{ formError }}</div>
        <div v-if="payInfo" class="alert alert-info" style="padding:8px 12px;font-size:13px;">
          Prestatario: <strong>{{ payInfo.borrower }}</strong>
          — Pendiente: <strong>S/ {{ payInfo.pending }}</strong>
        </div>
        <PaymentForm ref="payFormRef" :accounts="accounts" />
      </template>
      <template #footer>
        <button class="btn btn-default" @click="showPay = false">Cancelar</button>
        <button class="btn btn-success" :disabled="saving" @click="submitPayment">
          <i class="glyphicon glyphicon-ok"></i>
          {{ saving ? 'Guardando…' : 'Registrar Pago' }}
        </button>
      </template>
    </AppModal>

    <!-- ============================================================== -->
    <!-- MODALS: BORROWERS (PRESTATARIOS)                               -->
    <!-- ============================================================== -->

    <!-- Add Borrower Modal -->
    <AppModal v-model="showAddBorrower" title="Agregar prestatario" icon="glyphicon-plus-sign"
              header-color="#34495e" size="md" @closed="resetBorrowerForm">
      <template #body>
        <div v-if="borrowerFormError" class="alert alert-danger">{{ borrowerFormError }}</div>
        <BorrowerForm ref="addBorrowerRef" />
      </template>
      <template #footer>
        <button class="btn btn-default" @click="showAddBorrower = false">Cancelar</button>
        <button class="btn btn-primary" :disabled="savingBorrower" @click="submitAddBorrower">
          <i class="glyphicon glyphicon-floppy-disk"></i>
          {{ savingBorrower ? 'Guardando…' : 'Guardar' }}
        </button>
      </template>
    </AppModal>

    <!-- Edit Borrower Modal -->
    <AppModal v-model="showEditBorrower" title="Editar prestatario" icon="glyphicon-edit"
              header-color="#2980b9" size="md" @closed="resetBorrowerForm">
      <template #body>
        <div v-if="borrowerFormError" class="alert alert-danger">{{ borrowerFormError }}</div>
        <BorrowerForm ref="editBorrowerRef" :edit-id="editBorrowerId" />
      </template>
      <template #footer>
        <button class="btn btn-default" @click="showEditBorrower = false">Cancelar</button>
        <button class="btn btn-primary" :disabled="savingBorrower" @click="submitEditBorrower">
          <i class="glyphicon glyphicon-floppy-disk"></i>
          {{ savingBorrower ? 'Guardando…' : 'Actualizar' }}
        </button>
      </template>
    </AppModal>

    <ImportModal ref="importRef" module="loan" @imported="onMutated" />
  </div>
</template>

<script setup>
import { ref, computed, inject, nextTick, onMounted, onUnmounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useLoanStore } from '@/stores/useLoanStore'
import { useBorrowerStore } from '@/stores/useBorrowerStore'
import { useDashboardStore } from '@/stores/useDashboardStore'
import { useReportStore } from '@/stores/useReportStore'
import { useAccountStore } from '@/stores/useAccountStore'
import { useFormOptionsStore } from '@/stores/useFormOptionsStore'
import DataTable    from '@/components/DataTable.vue'
import AppModal     from '@/components/AppModal.vue'
import ImportModal  from '@/components/ImportModal.vue'
import LoanForm     from '@/components/forms/LoanForm.vue'
import PaymentForm  from '@/components/forms/PaymentForm.vue'
import BorrowerForm from '@/components/forms/BorrowerForm.vue'
import * as api     from '@/api'

const route          = useRoute()
const router         = useRouter()
const toast          = inject('toast')
const store          = useLoanStore()
const borrowerStore  = useBorrowerStore()
const dashboardStore = useDashboardStore()
const reportStore    = useReportStore()
const accountStore   = useAccountStore()
const optionsStore   = useFormOptionsStore()

// Tab navigation state
const currentTab = ref(
  route.query.tab === 'borrowers' || route.meta.tab === 'borrowers' ? 'borrowers' : 'loans'
)

watch(() => route.query.tab, (val) => {
  if (val === 'borrowers') currentTab.value = 'borrowers'
  else if (val === 'loans' || !val) currentTab.value = 'loans'
})

function switchTab(tab) {
  currentTab.value = tab
  router.replace({ query: { ...route.query, tab } })
  if (tab === 'borrowers') {
    borrowerStore.load()
  }
}

// Loan Modals state
const showAdd     = ref(false)
const showEdit    = ref(false)
const showPay     = ref(false)
const saving      = ref(false)
const formError   = ref('')
const editId      = ref(null)
const payInfo     = ref(null)
const importRef   = ref(null)
const addFormRef  = ref(null)
const editFormRef = ref(null)
const payFormRef  = ref(null)

// Borrower Modals state
const showAddBorrower   = ref(false)
const showEditBorrower  = ref(false)
const savingBorrower    = ref(false)
const borrowerFormError = ref('')
const editBorrowerId    = ref(null)
const addBorrowerRef    = ref(null)
const editBorrowerRef   = ref(null)
const openedFromLoan    = ref(false)

const accounts  = computed(() => optionsStore.accounts)
const borrowers = computed(() => optionsStore.borrowers)

const loanStats = computed(() => {
  let prestado = 0, cobrado = 0, pendiente = 0
  for (const r of store.rows) {
    const pStr = String(r[8] || '').replace(/[^0-9.]/g, '')
    const cStr = String(r[9] || '').replace(/[^0-9.]/g, '')
    const dStr = String(r[10] || '').replace(/[^0-9.]/g, '')
    prestado  += parseFloat(pStr) || 0
    cobrado   += parseFloat(cStr) || 0
    pendiente += parseFloat(dStr) || 0
  }
  return { prestado, cobrado, pendiente }
})

function fmt(n) {
  const num = parseFloat(n) || 0
  return num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')
}

function openAdd()   { resetForm(); showAdd.value = true }
function resetForm() { formError.value = '' }

function openAddBorrower() {
  borrowerFormError.value = ''
  showAddBorrower.value = true
  nextTick(() => {
    addBorrowerRef.value?.reset()
  })
}

function openAddBorrowerFromLoan() {
  openedFromLoan.value = true
  openAddBorrower()
}

function resetBorrowerForm() {
  borrowerFormError.value = ''
}

async function onMutated() {
  dashboardStore.invalidate()
  reportStore.invalidate()
  accountStore.invalidate()
  await Promise.all([store.reload(), optionsStore.reload()])
}

async function onBorrowerMutated() {
  await Promise.all([
    borrowerStore.reload(),
    optionsStore.reload()
  ])
}

// ── Submit Loan Handlers ──────────────────────────────────────────────
async function submitAdd() {
  saving.value = true; formError.value = ''
  try {
    const fd = addFormRef.value?.getFormData()
    if (!fd) { formError.value = 'Complete los campos requeridos y verifique que el capital sea mayor a 0.'; return }
    const { data } = await api.createLoan(fd)
    if (data.success) {
      showAdd.value = false
      await onMutated()
      toast.success(data.messages)
    } else {
      formError.value = data.messages
    }
  } catch {
    formError.value = 'Error de comunicación.'
  } finally {
    saving.value = false
  }
}

async function submitEdit() {
  saving.value = true; formError.value = ''
  try {
    const fd = editFormRef.value?.getFormData()
    if (!fd) { formError.value = 'Complete los campos requeridos y verifique que el capital sea mayor a 0.'; return }
    const { data } = await api.editLoan(fd)
    if (data.success) {
      showEdit.value = false
      await onMutated()
      toast.success(data.messages)
    } else {
      formError.value = data.messages
    }
  } catch {
    formError.value = 'Error de comunicación.'
  } finally {
    saving.value = false
  }
}

async function submitPayment() {
  saving.value = true; formError.value = ''
  try {
    const fd = payFormRef.value?.getFormData()
    if (!fd) { formError.value = 'Seleccione la cuenta destino e ingrese un monto mayor a 0.'; return }
    const { data } = await api.addLoanPayment(fd)
    if (data.success) {
      showPay.value = false
      await onMutated()
      toast.success(data.messages)
    } else {
      formError.value = data.messages
    }
  } catch {
    formError.value = 'Error de comunicación.'
  } finally {
    saving.value = false
  }
}

// ── Submit Borrower Handlers ──────────────────────────────────────────
async function submitAddBorrower() {
  savingBorrower.value = true
  borrowerFormError.value = ''
  try {
    const fd = addBorrowerRef.value?.getFormData()
    if (!fd) {
      borrowerFormError.value = 'Ingrese el documento y el nombre completo del prestatario.'
      return
    }
    const { data } = await api.createBorrower(fd)
    if (data.success) {
      showAddBorrower.value = false
      await onBorrowerMutated()
      toast.success(data.messages)

      // If opened from loan form, select this new borrower in LoanForm
      if (openedFromLoan.value) {
        const newBorrower = {
          id: data.borrower_id,
          full_name: data.full_name
        }
        if (showAdd.value) {
          addFormRef.value?.selectBorrower(newBorrower)
        } else if (showEdit.value) {
          editFormRef.value?.selectBorrower(newBorrower)
        }
        openedFromLoan.value = false
      }
    } else {
      borrowerFormError.value = data.messages
    }
  } catch {
    borrowerFormError.value = 'Error de comunicación al guardar el prestatario.'
  } finally {
    savingBorrower.value = false
  }
}

async function submitEditBorrower() {
  savingBorrower.value = true
  borrowerFormError.value = ''
  try {
    const fd = editBorrowerRef.value?.getFormData()
    if (!fd) {
      borrowerFormError.value = 'Ingrese el documento y el nombre completo del prestatario.'
      return
    }
    const { data } = await api.editBorrower(fd)
    if (data.success) {
      showEditBorrower.value = false
      await Promise.all([onBorrowerMutated(), store.reload()])
      toast.success(data.messages)
    } else {
      borrowerFormError.value = data.messages
    }
  } catch {
    borrowerFormError.value = 'Error de comunicación al actualizar el prestatario.'
  } finally {
    savingBorrower.value = false
  }
}

// ── Table Actions Delegation ──────────────────────────────────────────
async function handleTableAction(e) {
  const el = e.target.closest('a')
  if (!el) return

  // Loans: Edit
  if (el.classList.contains('edit-loan')) {
    e.preventDefault()
    const id = parseInt(el.dataset.id)
    const { data } = await api.getLoanData(id)
    if (data.success) {
      resetForm()
      editId.value = id
      showEdit.value = true
      await nextTick()
      editFormRef.value?.setData(data.data)
    } else {
      toast.danger(data.messages || 'No se pudo cargar el préstamo.')
    }
  }

  // Loans: Remove
  if (el.classList.contains('remove-loan')) {
    e.preventDefault()
    if (!confirm('¿Eliminar este préstamo? Esta acción no se puede deshacer.')) return
    const { data } = await api.removeLoan(parseInt(el.dataset.id))
    if (data.success) { await onMutated(); toast.success(data.messages) }
    else toast.danger(data.messages)
  }

  // Loans: Change Status
  if (el.classList.contains('change-status-loan')) {
    e.preventDefault()
    const id = parseInt(el.dataset.id)
    const status = el.dataset.status
    const { data } = await api.updateLoanStatus(id, status)
    if (data.success) { await onMutated(); toast.success(data.messages) }
    else toast.danger(data.messages)
  }

  // Loans: Pay
  if (el.classList.contains('pay-loan')) {
    e.preventDefault()
    const id = parseInt(el.dataset.id)
    const { data } = await api.getLoanData(id)
    if (data.success) {
      resetForm()
      editId.value = id
      payInfo.value = {
        borrower: data.data.borrower_name,
        pending:  data.data.balance_pending,
      }
      showPay.value = true
      await nextTick()
      payFormRef.value?.setLoanId(id)
    } else {
      toast.danger(data.messages || 'No se pudo cargar el préstamo.')
    }
  }

  // Borrowers: Edit
  if (el.classList.contains('edit-borrower')) {
    e.preventDefault()
    const id = parseInt(el.dataset.id)
    const { data } = await api.getBorrowerData(id)
    if (data.success) {
      resetBorrowerForm()
      editBorrowerId.value = id
      showEditBorrower.value = true
      await nextTick()
      editBorrowerRef.value?.setData(data.data)
    } else {
      toast.danger(data.messages || 'No se pudo cargar el prestatario.')
    }
  }

  // Borrowers: Remove
  if (el.classList.contains('remove-borrower')) {
    e.preventDefault()
    if (!confirm('¿Eliminar este prestatario? Esta acción no se puede deshacer.')) return
    const { data } = await api.removeBorrower(parseInt(el.dataset.id))
    if (data.success) {
      await onBorrowerMutated()
      toast.success(data.messages)
    } else {
      toast.danger(data.messages)
    }
  }
}

onMounted(async () => {
  await Promise.all([
    store.load(),
    borrowerStore.load(),
    optionsStore.load()
  ])
  document.addEventListener('click', handleTableAction)
})

onUnmounted(() => {
  document.removeEventListener('click', handleTableAction)
})
</script>
