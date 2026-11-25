<template>
  <div>
    <ol class="breadcrumb">
      <li><a href="#" @click.prevent="$router.push('/dashboard')">Inicio</a></li>
      <li class="active">Gastos</li>
    </ol>

    <div id="quota-alert-container"></div>

    <div class="panel panel-default">
      <DataTable
        title="Gestión de Gastos"
        icon="glyphicon-arrow-down text-danger"
        :headers="['#','Concepto','Cuenta','Categoría','Fecha','Monto','Comprobante','Estado','Acción']"
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
      </DataTable>
    </div>

    <!-- Add -->
    <AppModal v-model="showAdd" title="Registrar Gasto" icon="glyphicon-plus-sign"
              header-color="#c0392b" size="lg" @closed="resetForm">
      <template #body>
        <div v-if="formError" class="alert alert-danger">{{ formError }}</div>
        <ExpenseForm ref="addFormRef"
          :accounts="accounts" :categories="categories" :projects="projects" />
      </template>
      <template #footer>
        <button class="btn btn-default" @click="showAdd = false">Cancelar</button>
        <button class="btn btn-danger" :disabled="saving" @click="submitAdd">
          <i class="glyphicon glyphicon-floppy-disk"></i>
          {{ saving ? 'Guardando…' : 'Guardar' }}
        </button>
      </template>
    </AppModal>

    <!-- Edit -->
    <AppModal v-model="showEdit" title="Editar Gasto" icon="glyphicon-edit"
              header-color="#2980b9" size="lg" @closed="resetForm">
      <template #body>
        <div v-if="formError" class="alert alert-danger">{{ formError }}</div>
        <ExpenseForm ref="editFormRef"
          :accounts="accounts" :categories="categories" :projects="projects"
          :edit-id="editId" />
      </template>
      <template #footer>
        <button class="btn btn-default" @click="showEdit = false">Cancelar</button>
        <button class="btn btn-warning" :disabled="saving" @click="submitEdit">
          <i class="glyphicon glyphicon-floppy-disk"></i>
          {{ saving ? 'Guardando…' : 'Actualizar' }}
        </button>
      </template>
    </AppModal>

    <ImportModal ref="importRef" module="expense" @imported="store.reload()" />
  </div>
</template>

<script setup>
import { ref, inject, onMounted, onUnmounted } from 'vue'
import { useExpenseStore } from '@/stores/useExpenseStore'
import DataTable   from '@/components/DataTable.vue'
import AppModal    from '@/components/AppModal.vue'
import ImportModal from '@/components/ImportModal.vue'
import ExpenseForm from '@/components/forms/ExpenseForm.vue'
import * as api    from '@/api'

const toast    = inject('toast')
const store    = useExpenseStore()
const showAdd  = ref(false)
const showEdit = ref(false)
const saving   = ref(false)
const formError = ref('')
const editId    = ref(null)
const importRef   = ref(null)
const addFormRef  = ref(null)
const editFormRef = ref(null)

const accounts   = ref(window.expenseAccounts   || [])
const categories = ref(window.expenseCategories || [])
const projects   = ref(window.expenseProjects   || [])

function openAdd()   { resetForm(); showAdd.value = true }
function resetForm() { formError.value = '' }

async function submitAdd() {
  saving.value = true; formError.value = ''
  try {
    const fd = addFormRef.value?.getFormData()
    if (!fd) { formError.value = 'Complete los campos requeridos.'; return }
    const { data } = await api.createExpense(fd)
    if (data.success) { showAdd.value = false; await store.reload(); toast.success(data.messages) }
    else formError.value = data.messages
  } catch { formError.value = 'Error de comunicación.' }
  finally { saving.value = false }
}

async function submitEdit() {
  saving.value = true; formError.value = ''
  try {
    const fd = editFormRef.value?.getFormData()
    if (!fd) { formError.value = 'Complete los campos requeridos.'; return }
    const { data } = await api.editExpense(fd)
    if (data.success) { showEdit.value = false; await store.reload(); toast.success(data.messages) }
    else formError.value = data.messages
  } catch { formError.value = 'Error de comunicación.' }
  finally { saving.value = false }
}

async function handleTableAction(e) {
  const el = e.target.closest('a')
  if (!el) return
  if (el.classList.contains('edit-expense')) {
    e.preventDefault()
    const id = parseInt(el.dataset.id)
    const { data } = await api.getExpenseData(id)
    if (data.success) {
      editId.value = id
      showEdit.value = true
      setTimeout(() => editFormRef.value?.setData(data.data), 50)
    }
  }
  if (el.classList.contains('remove-expense')) {
    e.preventDefault()
    if (!confirm('¿Eliminar este gasto?')) return
    const { data } = await api.removeExpense(parseInt(el.dataset.id))
    if (data.success) { await store.reload(); toast.success(data.messages) }
    else toast.danger(data.messages)
  }
}

onMounted(async () => {
  await store.load()
  document.addEventListener('click', handleTableAction)
})

onUnmounted(() => {
  document.removeEventListener('click', handleTableAction)
})
</script>
