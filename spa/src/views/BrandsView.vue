<template>
  <div>
    <ol class="breadcrumb">
      <li><a href="#" @click.prevent="$router.push('/dashboard')">Inicio</a></li>
      <li class="active">Fabricantes</li>
    </ol>

    <div class="panel panel-default">
      <DataTable
        title="Listado de fabricantes"
        icon="glyphicon-edit"
        :headers="['Nombre', 'Estado', 'Opciones']"
        :store="store"
      >
        <template #actions>
          <button class="btn btn-default button1" @click="openAdd">
            <i class="glyphicon glyphicon-plus-sign"></i> Agregar fabricante
          </button>
        </template>
      </DataTable>
    </div>

    <!-- Add Modal -->
    <AppModal v-model="showAdd" title="Agregar Fabricante" icon="glyphicon-plus-sign" @closed="resetForm">
      <template #body>
        <div v-if="formError" class="alert alert-danger">{{ formError }}</div>
        <form @submit.prevent="submitAdd" class="form-horizontal">
          <div class="form-group">
            <label class="col-sm-3 control-label">Nombre Fabricante: </label>
            <div class="col-sm-8">
              <input type="text" class="form-control" v-model="form.brandName" placeholder="Nombre Fabricante" required />
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-3 control-label">Estado: </label>
            <div class="col-sm-8">
              <select class="form-control" v-model="form.brandStatus">
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
    <AppModal v-model="showEdit" title="Editar Fabricante" icon="glyphicon-edit" @closed="resetForm">
      <template #body>
        <div v-if="formError" class="alert alert-danger">{{ formError }}</div>
        <form @submit.prevent="submitEdit" class="form-horizontal">
          <div class="form-group">
            <label class="col-sm-3 control-label">Nombre Fabricante: </label>
            <div class="col-sm-8">
              <input type="text" class="form-control" v-model="editForm.brandName" placeholder="Nombre Fabricante" required />
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-3 control-label">Estado: </label>
            <div class="col-sm-8">
              <select class="form-control" v-model="editForm.brandStatus">
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
import { useBrandStore } from '@/stores/useBrandStore'
import DataTable from '@/components/DataTable.vue'
import AppModal  from '@/components/AppModal.vue'
import * as api  from '@/api'

const toast  = inject('toast')
const store  = useBrandStore()
const showAdd  = ref(false)
const showEdit = ref(false)
const saving   = ref(false)
const formError = ref('')
const editId    = ref(null)

const form = reactive({ brandName: '', brandStatus: '1' })
const editForm = reactive({ brandName: '', brandStatus: '1' })

function openAdd()   { resetForm(); showAdd.value = true }
function resetForm() { formError.value = ''; form.brandName = ''; form.brandStatus = '1' }

async function submitAdd() {
  if (!form.brandName.trim()) { formError.value = 'El nombre es obligatorio.'; return }
  saving.value = true; formError.value = ''
  try {
    const fd = new FormData()
    fd.append('brandName', form.brandName.trim())
    fd.append('brandStatus', form.brandStatus)
    const { data } = await api.createBrand(fd)
    if (data.success) { showAdd.value = false; await store.reload(); toast.success(data.messages) }
    else formError.value = data.messages
  } catch { formError.value = 'Error de comunicación.' }
  finally { saving.value = false }
}

async function submitEdit() {
  if (!editForm.brandName.trim()) { formError.value = 'El nombre es obligatorio.'; return }
  saving.value = true; formError.value = ''
  try {
    const fd = new FormData()
    fd.append('brandId', editId.value)
    fd.append('brandName', editForm.brandName.trim())
    fd.append('brandStatus', editForm.brandStatus)
    const { data } = await api.editBrand(fd)
    if (data.success) { showEdit.value = false; await store.reload(); toast.success(data.messages) }
    else formError.value = data.messages
  } catch { formError.value = 'Error de comunicación.' }
  finally { saving.value = false }
}

async function handleTableAction(e) {
  const el = e.target.closest('a')
  if (!el) return
  const onclickAttr = el.getAttribute('onclick') || ''

  const editMatch = onclickAttr.match(/editBrands\((\d+)\)/)
  if (editMatch) {
    e.preventDefault()
    const id = parseInt(editMatch[1])
    const { data } = await api.fetchSelectedBrand(id)
    if (data) {
      editId.value = id
      editForm.brandName   = data.brand_name || ''
      editForm.brandStatus = String(data.brand_active || '1')
      showEdit.value = true
    }
  }

  const removeMatch = onclickAttr.match(/removeBrands\((\d+)\)/)
  if (removeMatch) {
    e.preventDefault()
    if (!confirm('¿Eliminar este fabricante?')) return
    const { data } = await api.removeBrand(parseInt(removeMatch[1]))
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
