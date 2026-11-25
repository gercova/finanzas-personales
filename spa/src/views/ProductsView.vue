<template>
  <div>
    <ol class="breadcrumb">
      <li><a href="#" @click.prevent="$router.push('/dashboard')">Inicio</a></li>
      <li class="active">Productos</li>
    </ol>

    <div class="panel panel-default">
      <DataTable
        title="Listado de productos"
        icon="glyphicon-edit"
        :headers="['Imagen', 'Nombre del producto', 'Precio', 'Stock', 'Fabricante', 'Categoría', 'Estado', 'Opciones']"
        :store="store"
      >
        <template #actions>
          <button class="btn btn-default button1" @click="openAdd">
            <i class="glyphicon glyphicon-plus-sign"></i> Agregar producto
          </button>
        </template>
      </DataTable>
    </div>

    <!-- Add Modal -->
    <AppModal v-model="showAdd" title="Agregar Producto" icon="glyphicon-plus-sign" size="lg" @closed="resetForm">
      <template #body>
        <div v-if="formError" class="alert alert-danger">{{ formError }}</div>
        <form @submit.prevent="submitAdd" class="form-horizontal">
          <div class="form-group">
            <label class="col-sm-3 control-label">Nombre del Producto: </label>
            <div class="col-sm-8">
              <input type="text" class="form-control" v-model="form.productName" placeholder="Nombre del Producto" required />
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-3 control-label">Cantidad / Stock: </label>
            <div class="col-sm-8">
              <input type="number" class="form-control" v-model="form.quantity" placeholder="Stock" min="0" required />
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-3 control-label">Precio: </label>
            <div class="col-sm-8">
              <input type="number" step="0.01" class="form-control" v-model="form.rate" placeholder="Precio" min="0" required />
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-3 control-label">Estado: </label>
            <div class="col-sm-8">
              <select class="form-control" v-model="form.productStatus">
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
    <AppModal v-model="showEdit" title="Editar Producto" icon="glyphicon-edit" size="lg" @closed="resetForm">
      <template #body>
        <div v-if="formError" class="alert alert-danger">{{ formError }}</div>
        <form @submit.prevent="submitEdit" class="form-horizontal">
          <div class="form-group">
            <label class="col-sm-3 control-label">Nombre del Producto: </label>
            <div class="col-sm-8">
              <input type="text" class="form-control" v-model="editForm.productName" placeholder="Nombre del Producto" required />
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-3 control-label">Cantidad / Stock: </label>
            <div class="col-sm-8">
              <input type="number" class="form-control" v-model="editForm.quantity" placeholder="Stock" min="0" required />
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-3 control-label">Precio: </label>
            <div class="col-sm-8">
              <input type="number" step="0.01" class="form-control" v-model="editForm.rate" placeholder="Precio" min="0" required />
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-3 control-label">Estado: </label>
            <div class="col-sm-8">
              <select class="form-control" v-model="editForm.productStatus">
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
import { useProductStore } from '@/stores/useProductStore'
import DataTable from '@/components/DataTable.vue'
import AppModal  from '@/components/AppModal.vue'
import * as api  from '@/api'

const toast  = inject('toast')
const store  = useProductStore()
const showAdd  = ref(false)
const showEdit = ref(false)
const saving   = ref(false)
const formError = ref('')
const editId    = ref(null)

const form = reactive({ productName: '', quantity: '0', rate: '0.00', productStatus: '1' })
const editForm = reactive({ productName: '', quantity: '0', rate: '0.00', productStatus: '1' })

function openAdd()   { resetForm(); showAdd.value = true }
function resetForm() {
  formError.value = ''
  form.productName = ''
  form.quantity = '0'
  form.rate = '0.00'
  form.productStatus = '1'
}

async function submitAdd() {
  if (!form.productName.trim()) { formError.value = 'El nombre es obligatorio.'; return }
  saving.value = true; formError.value = ''
  try {
    const fd = new FormData()
    fd.append('productName', form.productName.trim())
    fd.append('quantity', form.quantity)
    fd.append('rate', form.rate)
    fd.append('productStatus', form.productStatus)
    const { data } = await api.createProduct(fd)
    if (data.success) { showAdd.value = false; await store.reload(); toast.success(data.messages) }
    else formError.value = data.messages
  } catch { formError.value = 'Error de comunicación.' }
  finally { saving.value = false }
}

async function submitEdit() {
  if (!editForm.productName.trim()) { formError.value = 'El nombre es obligatorio.'; return }
  saving.value = true; formError.value = ''
  try {
    const fd = new FormData()
    fd.append('productId', editId.value)
    fd.append('productName', editForm.productName.trim())
    fd.append('quantity', editForm.quantity)
    fd.append('rate', editForm.rate)
    fd.append('productStatus', editForm.productStatus)
    const { data } = await api.editProduct(fd)
    if (data.success) { showEdit.value = false; await store.reload(); toast.success(data.messages) }
    else formError.value = data.messages
  } catch { formError.value = 'Error de comunicación.' }
  finally { saving.value = false }
}

async function handleTableAction(e) {
  const el = e.target.closest('a')
  if (!el) return
  const onclickAttr = el.getAttribute('onclick') || ''

  const editMatch = onclickAttr.match(/editProduct\((\d+)\)/)
  if (editMatch) {
    e.preventDefault()
    const id = parseInt(editMatch[1])
    const { data } = await api.fetchSelectedProduct(id)
    if (data) {
      editId.value = id
      editForm.productName   = data.product_name || ''
      editForm.quantity      = String(data.quantity || '0')
      editForm.rate          = String(data.rate || '0.00')
      editForm.productStatus = String(data.active || '1')
      showEdit.value = true
    }
  }

  const removeMatch = onclickAttr.match(/removeProduct\((\d+)\)/)
  if (removeMatch) {
    e.preventDefault()
    if (!confirm('¿Eliminar este producto?')) return
    const { data } = await api.removeProduct(parseInt(removeMatch[1]))
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
