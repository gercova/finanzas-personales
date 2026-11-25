<template>
  <div>
    <ol class="breadcrumb">
      <li><a href="#" @click.prevent="$router.push('/dashboard')">Inicio</a></li>
      <li class="active">Categorías POS</li>
    </ol>

    <div class="panel panel-default">
      <DataTable
        title="Listado de categorías"
        icon="glyphicon-edit"
        :headers="['Categoría', 'Estado', 'Opciones']"
        :store="store"
      >
        <template #actions>
          <button class="btn btn-default button1" @click="openAdd">
            <i class="glyphicon glyphicon-plus-sign"></i> Agregar categoría
          </button>
        </template>
      </DataTable>
    </div>

    <!-- Add Modal -->
    <AppModal v-model="showAdd" title="Agregar Categoría" icon="glyphicon-plus-sign" @closed="resetForm">
      <template #body>
        <div v-if="formError" class="alert alert-danger">{{ formError }}</div>
        <form @submit.prevent="submitAdd" class="form-horizontal">
          <div class="form-group">
            <label class="col-sm-4 control-label">Nombre Categoría: </label>
            <div class="col-sm-7">
              <input type="text" class="form-control" v-model="form.categoriesName" placeholder="Nombre Categoría" required />
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-4 control-label">Estado: </label>
            <div class="col-sm-7">
              <select class="form-control" v-model="form.categoriesStatus">
                <option value="1">Disponible</option>
                <option value="2">No disponible</option>
              </select>
            </div>
          </div>
        </form>
      </template>
      <template #footer>
        <button class="btn btn-default" @click="showAdd = false">Cerrar</button>
        <button class="btn btn-primary" :disabled="saving" @click="submitAdd">
          <i class="glyphicon glyphicon-ok-sign"></i> {{ saving ? 'Guardando…' : 'Guardar' }}
        </button>
      </template>
    </AppModal>

    <!-- Edit Modal -->
    <AppModal v-model="showEdit" title="Editar Categoría" icon="glyphicon-edit" @closed="resetForm">
      <template #body>
        <div v-if="formError" class="alert alert-danger">{{ formError }}</div>
        <form @submit.prevent="submitEdit" class="form-horizontal">
          <div class="form-group">
            <label class="col-sm-4 control-label">Nombre Categoría: </label>
            <div class="col-sm-7">
              <input type="text" class="form-control" v-model="editForm.categoriesName" placeholder="Nombre Categoría" required />
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-4 control-label">Estado: </label>
            <div class="col-sm-7">
              <select class="form-control" v-model="editForm.categoriesStatus">
                <option value="1">Disponible</option>
                <option value="2">No disponible</option>
              </select>
            </div>
          </div>
        </form>
      </template>
      <template #footer>
        <button class="btn btn-default" @click="showEdit = false">Cerrar</button>
        <button class="btn btn-success" :disabled="saving" @click="submitEdit">
          <i class="glyphicon glyphicon-ok-sign"></i> {{ saving ? 'Guardando…' : 'Guardar Cambios' }}
        </button>
      </template>
    </AppModal>
  </div>
</template>

<script setup>
import { ref, reactive, inject, onMounted, onUnmounted } from 'vue'
import { usePosCategoryStore } from '@/stores/usePosCategoryStore'
import DataTable from '@/components/DataTable.vue'
import AppModal  from '@/components/AppModal.vue'
import * as api  from '@/api'

const toast  = inject('toast')
const store  = usePosCategoryStore()
const showAdd  = ref(false)
const showEdit = ref(false)
const saving   = ref(false)
const formError = ref('')
const editId    = ref(null)

const form = reactive({ categoriesName: '', categoriesStatus: '1' })
const editForm = reactive({ categoriesName: '', categoriesStatus: '1' })

function openAdd()   { resetForm(); showAdd.value = true }
function resetForm() { formError.value = ''; form.categoriesName = ''; form.categoriesStatus = '1' }

async function submitAdd() {
  if (!form.categoriesName.trim()) { formError.value = 'El nombre es obligatorio.'; return }
  saving.value = true; formError.value = ''
  try {
    const fd = new FormData()
    fd.append('categoriesName', form.categoriesName.trim())
    fd.append('categoriesStatus', form.categoriesStatus)
    const { data } = await api.createCategories(fd)
    if (data.success) { showAdd.value = false; await store.reload(); toast.success(data.messages) }
    else formError.value = data.messages
  } catch { formError.value = 'Error de comunicación.' }
  finally { saving.value = false }
}

async function submitEdit() {
  if (!editForm.categoriesName.trim()) { formError.value = 'El nombre es obligatorio.'; return }
  saving.value = true; formError.value = ''
  try {
    const fd = new FormData()
    fd.append('categoriesId', editId.value)
    fd.append('categoriesName', editForm.categoriesName.trim())
    fd.append('categoriesStatus', editForm.categoriesStatus)
    const { data } = await api.editCategories(fd)
    if (data.success) { showEdit.value = false; await store.reload(); toast.success(data.messages) }
    else formError.value = data.messages
  } catch { formError.value = 'Error de comunicación.' }
  finally { saving.value = false }
}

async function handleTableAction(e) {
  const el = e.target.closest('a')
  if (!el) return
  const onclickAttr = el.getAttribute('onclick') || ''

  const editMatch = onclickAttr.match(/editCategories\((\d+)\)/)
  if (editMatch) {
    e.preventDefault()
    const id = parseInt(editMatch[1])
    editId.value = id
    // Get row text from the clicked row
    const rowEl = el.closest('tr')
    if (rowEl) {
      editForm.categoriesName = rowEl.children[0]?.textContent?.trim() || ''
    }
    showEdit.value = true
  }

  const removeMatch = onclickAttr.match(/removeCategories\((\d+)\)/)
  if (removeMatch) {
    e.preventDefault()
    if (!confirm('¿Eliminar esta categoría?')) return
    const { data } = await api.removeCategories(parseInt(removeMatch[1]))
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
