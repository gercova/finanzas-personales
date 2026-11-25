<template>
  <div>
    <ol class="breadcrumb">
      <li><a href="#" @click.prevent="$router.push('/dashboard')">Inicio</a></li>
      <li class="active">Proyectos</li>
    </ol>

    <div class="panel panel-default">
      <DataTable
        title="Gestión de Proyectos"
        icon="glyphicon-briefcase text-primary"
        :headers="['#','Proyecto','Cliente','Presupuesto','Inicio','Fin','Estado','Total Ingresos','Total Gastos','Pronóstico','Acción']"
        :store="store"
      >
        <template #actions>
          <button class="btn btn-default btn-sm" @click="importRef.open()">
            <i class="glyphicon glyphicon-upload"></i> Importar
          </button>
          <button class="btn btn-primary" @click="openAdd">
            <i class="glyphicon glyphicon-plus-sign"></i> Nuevo Proyecto
          </button>
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

    <ImportModal ref="importRef" module="project" @imported="store.reload()" />
  </div>
</template>

<script setup>
import { ref, inject, onMounted, onUnmounted } from 'vue'
import { useProjectStore } from '@/stores/useProjectStore'
import DataTable   from '@/components/DataTable.vue'
import AppModal    from '@/components/AppModal.vue'
import ImportModal from '@/components/ImportModal.vue'
import ProjectForm from '@/components/forms/ProjectForm.vue'
import * as api    from '@/api'

const toast    = inject('toast')
const store    = useProjectStore()
const showAdd  = ref(false)
const showEdit = ref(false)
const saving   = ref(false)
const formError = ref('')
const editId    = ref(null)
const importRef   = ref(null)
const addFormRef  = ref(null)
const editFormRef = ref(null)

function openAdd()   { resetForm(); showAdd.value = true }
function resetForm() { formError.value = '' }

async function submitAdd() {
  saving.value = true; formError.value = ''
  try {
    const fd = addFormRef.value?.getFormData()
    if (!fd) { formError.value = 'Complete los campos requeridos.'; return }
    const { data } = await api.createProject(fd)
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
    const { data } = await api.editProject(fd)
    if (data.success) { showEdit.value = false; await store.reload(); toast.success(data.messages) }
    else formError.value = data.messages
  } catch { formError.value = 'Error de comunicación.' }
  finally { saving.value = false }
}

async function handleTableAction(e) {
  const el = e.target.closest('a')
  if (!el) return
  const onclickAttr = el.getAttribute('onclick') || ''

  const editMatch = onclickAttr.match(/editProject\((\d+)\)/)
  if (editMatch) {
    e.preventDefault()
    const id = parseInt(editMatch[1])
    const { data } = await api.getProjectData(id)
    if (data) {
      editId.value = id
      showEdit.value = true
      setTimeout(() => editFormRef.value?.setData(data), 50)
    }
  }

  const removeMatch = onclickAttr.match(/removeProject\((\d+)\)/)
  if (removeMatch) {
    e.preventDefault()
    if (!confirm('¿Eliminar este proyecto?')) return
    const { data } = await api.removeProject(parseInt(removeMatch[1]))
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
