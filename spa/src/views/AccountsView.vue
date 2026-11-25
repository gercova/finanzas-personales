<template>
  <div>
    <ol class="breadcrumb">
      <li><a href="#" @click.prevent="$router.push('/dashboard')">Inicio</a></li>
      <li class="active">Cuentas y Categorías</li>
    </ol>

    <div class="panel panel-default">
      <DataTable
        title="Gestión de Cuentas"
        icon="glyphicon-credit-card text-primary"
        :headers="['#','Cuenta','Tipo','N° Cuenta','Moneda','Saldo Inicial','Saldo Actual','Estado','Acción']"
        :store="store"
      >
        <template #actions>
          <button class="btn btn-primary" @click="openAdd">
            <i class="glyphicon glyphicon-plus-sign"></i> Nueva Cuenta
          </button>
        </template>
      </DataTable>
    </div>

    <!-- Add -->
    <AppModal v-model="showAdd" title="Nueva Cuenta" icon="glyphicon-plus" @closed="resetForm">
      <template #body>
        <div v-if="formError" class="alert alert-danger">{{ formError }}</div>
        <AccountForm ref="addFormRef" />
      </template>
      <template #footer>
        <button class="btn btn-default" @click="showAdd = false">Cancelar</button>
        <button class="btn btn-primary" :disabled="saving" @click="submitAdd">
          <i class="glyphicon glyphicon-floppy-disk"></i> {{ saving ? 'Guardando…' : 'Guardar' }}
        </button>
      </template>
    </AppModal>

    <!-- Edit -->
    <AppModal v-model="showEdit" title="Editar Cuenta" icon="glyphicon-edit" @closed="resetForm">
      <template #body>
        <div v-if="formError" class="alert alert-danger">{{ formError }}</div>
        <AccountForm ref="editFormRef" :edit-id="editId" />
      </template>
      <template #footer>
        <button class="btn btn-default" @click="showEdit = false">Cancelar</button>
        <button class="btn btn-primary" :disabled="saving" @click="submitEdit">
          <i class="glyphicon glyphicon-floppy-disk"></i> {{ saving ? 'Guardando…' : 'Actualizar' }}
        </button>
      </template>
    </AppModal>
  </div>
</template>

<script setup>
import { ref, inject, onMounted } from 'vue'
import { useAccountStore } from '@/stores/useAccountStore'
import DataTable   from '@/components/DataTable.vue'
import AppModal    from '@/components/AppModal.vue'
import AccountForm from '@/components/forms/AccountForm.vue'
import * as api    from '@/api'

const toast    = inject('toast')
const store    = useAccountStore()
const showAdd  = ref(false)
const showEdit = ref(false)
const saving   = ref(false)
const formError = ref('')
const editId   = ref(null)
const addFormRef  = ref(null)
const editFormRef = ref(null)

onMounted(async () => {
  await store.load()
  document.addEventListener('click', handleTableAction)
})

function openAdd() { resetForm(); showAdd.value = true }
function resetForm() { formError.value = '' }

async function submitAdd() {
  saving.value = true; formError.value = ''
  try {
    const fd = addFormRef.value?.getFormData()
    if (!fd) { formError.value = 'Complete los campos requeridos.'; return }
    const { data } = await api.createAccount(fd)
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
    const { data } = await api.editAccount(fd)
    if (data.success) { showEdit.value = false; await store.reload(); toast.success(data.messages) }
    else formError.value = data.messages
  } catch { formError.value = 'Error de comunicación.' }
  finally { saving.value = false }
}

async function handleTableAction(e) {
  const el = e.target.closest('a')
  if (!el) return
  if (el.classList.contains('edit-account')) {
    e.preventDefault()
    const id = parseInt(el.dataset.id)
    const { data } = await api.getAccountData(id)
    if (data.success) { editId.value = id; showEdit.value = true; setTimeout(() => editFormRef.value?.setData(data.data), 50) }
  }
  if (el.classList.contains('remove-account')) {
    e.preventDefault()
    if (!confirm('¿Eliminar esta cuenta? Los movimientos asociados quedarán sin cuenta.')) return
    const { data } = await api.removeAccount(parseInt(el.dataset.id))
    if (data.success) { await store.reload(); toast.success(data.messages) }
    else toast.danger(data.messages)
  }
}
</script>
