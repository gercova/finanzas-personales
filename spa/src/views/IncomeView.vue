<template>
  <div>
    <!-- Breadcrumb -->
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
            <h3 style="margin:0;font-weight:700;" class="text-success">S/ {{ fmt(stats.month) }}</h3>
            <small class="text-muted">{{ monthLabel }}</small>
          </div>
        </div>
      </div>
      <div class="col-xs-12 col-sm-4">
        <div class="panel panel-info">
          <div class="panel-heading"><i class="glyphicon glyphicon-stats"></i> Ingresos del Año</div>
          <div class="panel-body text-center">
            <h3 style="margin:0;font-weight:700;" class="text-info">S/ {{ fmt(stats.year) }}</h3>
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
        :headers="['#','Concepto','Cuenta','Categoría','Proyecto','Fecha','Monto','Estado','Acción']"
        :store="store"
      >
        <template #actions>
          <button class="btn btn-default btn-sm" @click="importRef.open()">
            <i class="glyphicon glyphicon-upload"></i> Importar
          </button>
          <button class="btn btn-success" @click="openAdd">
            <i class="glyphicon glyphicon-plus-sign"></i> Registrar Ingreso
          </button>
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
          <i class="glyphicon glyphicon-floppy-disk"></i> {{ saving ? 'Guardando…' : 'Guardar' }}
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
          <i class="glyphicon glyphicon-floppy-disk"></i> {{ saving ? 'Guardando…' : 'Actualizar' }}
        </button>
      </template>
    </AppModal>

    <!-- Import -->
    <ImportModal ref="importRef" module="income" @imported="store.reload()" />
  </div>
</template>

<script setup>
import { ref, computed, inject, onMounted } from 'vue'
import { useIncomeStore } from '@/stores/useIncomeStore'
import DataTable   from '@/components/DataTable.vue'
import AppModal    from '@/components/AppModal.vue'
import ImportModal from '@/components/ImportModal.vue'
import IncomeForm  from '@/components/forms/IncomeForm.vue'
import * as api    from '@/api'

const toast    = inject('toast')
const store    = useIncomeStore()
const showAdd  = ref(false)
const showEdit = ref(false)
const saving   = ref(false)
const formError = ref('')
const editId   = ref(null)
const importRef  = ref(null)
const addFormRef = ref(null)
const editFormRef = ref(null)

const accounts   = ref([])
const categories = ref([])
const projects   = ref([])

const today = new Date()
const monthLabel = today.toLocaleString('es', { month: 'long', year: 'numeric' })

// Derive stats from raw rows — raw HTML rows contain amounts in col index 6
const stats = computed(() => {
  const curMonth = today.toISOString().slice(0, 7)
  const curYear  = String(today.getFullYear())
  let month = 0, year = 0, recurring = 0
  for (const r of store.rows) {
    const dateStr = String(r[5] || '')     // 'dd/mm/YYYY'
    const amtStr  = String(r[6] || '').replace(/[^0-9.]/g, '')
    const amt     = parseFloat(amtStr) || 0
    const statusRaw = String(r[7] || '')
    if (statusRaw.includes('CANCELADO')) continue
    // Date col is dd/mm/YYYY
    const parts = dateStr.split('/')
    if (parts.length === 3) {
      const ym = `${parts[2]}-${parts[1]}`
      if (ym === curMonth) month += amt
      if (parts[2] === curYear) year += amt
    }
    // Recurring icon in concept col
    if (String(r[1] || '').includes('glyphicon-repeat')) recurring++
  }
  return { month, year, recurring }
})

function fmt(n) { return Number(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',') }

async function loadLists() {
  try {
    // Fetch accounts from inline data PHP set
    if (window.incomeAccounts)   accounts.value   = window.incomeAccounts
    if (window.incomeCategories) categories.value = window.incomeCategories
    if (window.incomeProjects)   projects.value   = window.incomeProjects
  } catch {}
}

onMounted(async () => {
  await Promise.all([store.load(), loadLists()])
  // Delegate Bootstrap dropdown clicks to the table (re-triggered by Vue's DOM updates)
  document.addEventListener('click', handleTableAction)
})

function openAdd() { resetForm(); showAdd.value = true }

function resetForm() { formError.value = '' }

async function submitAdd() {
  saving.value = true; formError.value = ''
  try {
    const fd = addFormRef.value?.getFormData()
    if (!fd) { formError.value = 'Complete los campos requeridos.'; return }
    const { data } = await api.createIncome(fd)
    if (data.success) {
      showAdd.value = false
      await store.reload()
      toast.success(data.messages)
    } else { formError.value = data.messages }
  } catch { formError.value = 'Error de comunicación.' }
  finally { saving.value = false }
}

async function submitEdit() {
  saving.value = true; formError.value = ''
  try {
    const fd = editFormRef.value?.getFormData()
    if (!fd) { formError.value = 'Complete los campos requeridos.'; return }
    const { data } = await api.editIncome(fd)
    if (data.success) {
      showEdit.value = false
      await store.reload()
      toast.success(data.messages)
    } else { formError.value = data.messages }
  } catch { formError.value = 'Error de comunicación.' }
  finally { saving.value = false }
}

async function handleTableAction(e) {
  const el = e.target.closest('a')
  if (!el) return

  if (el.classList.contains('edit-income')) {
    e.preventDefault()
    const id = parseInt(el.dataset.id)
    const { data } = await api.getIncomeData(id)
    if (data.success) {
      editId.value = id
      showEdit.value = true
      // Let Vue render then populate
      setTimeout(() => editFormRef.value?.setData(data.data), 50)
    }
  }

  if (el.classList.contains('remove-income')) {
    e.preventDefault()
    if (!confirm('¿Eliminar este ingreso? Esta acción no se puede deshacer.')) return
    const id = parseInt(el.dataset.id)
    const { data } = await api.removeIncome(id)
    if (data.success) { await store.reload(); toast.success(data.messages) }
    else toast.danger(data.messages)
  }
}
</script>
