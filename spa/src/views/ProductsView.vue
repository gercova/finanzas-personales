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
            <label class="col-sm-3 control-label">Imagen</label>
            <div class="col-sm-8">
              <input ref="addImageRef" type="file" accept="image/jpeg,image/png,image/gif" class="form-control" @change="onAddFile" />
              <small class="text-muted">Opcional: JPG, PNG o GIF (si se omite, se usará imagen por defecto).</small>
              <div v-if="addPreviewSrc" style="margin-top:8px;">
                <img :src="addPreviewSrc" style="max-height:100px;border-radius:4px;border:1px solid #ddd;" />
              </div>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-3 control-label">Nombre <span class="text-danger">*</span></label>
            <div class="col-sm-8">
              <input type="text" class="form-control" v-model="form.productName" placeholder="Nombre del producto" required />
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-3 control-label">Stock <span class="text-danger">*</span></label>
            <div class="col-sm-8">
              <input type="number" class="form-control" v-model="form.quantity" placeholder="Stock" min="0" required />
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-3 control-label">Precio <span class="text-danger">*</span></label>
            <div class="col-sm-8">
              <input type="number" step="0.01" class="form-control" v-model="form.rate" placeholder="Precio" min="0" required />
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-3 control-label">Fabricante <span class="text-danger">*</span></label>
            <div class="col-sm-8">
              <select class="form-control" v-model="form.brandName" required>
                <option value="">-- Selecciona --</option>
                <option v-for="b in brands" :key="b.id" :value="b.id">{{ b.brand_name }}</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-3 control-label">Categoría <span class="text-danger">*</span></label>
            <div class="col-sm-8">
              <select class="form-control" v-model="form.categoryName" required>
                <option value="">-- Selecciona --</option>
                <option v-for="c in posCategories" :key="c.id" :value="c.id">{{ c.categories_name }}</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-3 control-label">Estado <span class="text-danger">*</span></label>
            <div class="col-sm-8">
              <select class="form-control" v-model="form.productStatus" required>
                <option value="">-- Selecciona --</option>
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
            <label class="col-sm-3 control-label">Imagen</label>
            <div class="col-sm-8">
              <div v-if="editPreviewSrc" style="margin-bottom:8px;">
                <img :src="editPreviewSrc" style="max-height:100px;border-radius:4px;border:1px solid #ddd;" />
              </div>
              <div v-else-if="editCurrentImage" style="margin-bottom:8px;">
                <img :src="baseUrl + editCurrentImage.replace(/^\.\.\//, '')" style="max-height:100px;border-radius:4px;border:1px solid #ddd;" />
              </div>
              <input ref="editImageRef" type="file" accept="image/jpeg,image/png,image/gif" class="form-control" @change="onEditFile" />
              <small class="text-muted">Opcional: selecciona una nueva imagen para reemplazar la actual.</small>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-3 control-label">Nombre <span class="text-danger">*</span></label>
            <div class="col-sm-8">
              <input type="text" class="form-control" v-model="editForm.editProductName" placeholder="Nombre del producto" required />
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-3 control-label">Stock <span class="text-danger">*</span></label>
            <div class="col-sm-8">
              <input type="number" class="form-control" v-model="editForm.editQuantity" placeholder="Stock" min="0" required />
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-3 control-label">Precio <span class="text-danger">*</span></label>
            <div class="col-sm-8">
              <input type="number" step="0.01" class="form-control" v-model="editForm.editRate" placeholder="Precio" min="0" required />
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-3 control-label">Fabricante <span class="text-danger">*</span></label>
            <div class="col-sm-8">
              <select class="form-control" v-model="editForm.editBrandName" required>
                <option value="">-- Selecciona --</option>
                <option v-for="b in brands" :key="b.id" :value="b.id">{{ b.brand_name }}</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-3 control-label">Categoría <span class="text-danger">*</span></label>
            <div class="col-sm-8">
              <select class="form-control" v-model="editForm.editCategoryName" required>
                <option value="">-- Selecciona --</option>
                <option v-for="c in posCategories" :key="c.id" :value="c.id">{{ c.categories_name }}</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-3 control-label">Estado <span class="text-danger">*</span></label>
            <div class="col-sm-8">
              <select class="form-control" v-model="editForm.editProductStatus" required>
                <option value="">-- Selecciona --</option>
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
import { ref, reactive, computed, inject, onMounted, onUnmounted } from 'vue'
import { useProductStore } from '@/stores/useProductStore'
import { useFormOptionsStore } from '@/stores/useFormOptionsStore'
import { getBaseURL } from '@/api/http'
import DataTable from '@/components/DataTable.vue'
import AppModal  from '@/components/AppModal.vue'
import * as api  from '@/api'

const baseUrl      = getBaseURL()
const toast        = inject('toast')
const store        = useProductStore()
const optionsStore = useFormOptionsStore()

const brands        = computed(() => optionsStore.brands)
const posCategories = computed(() => optionsStore.posCategories)

const showAdd  = ref(false)
const showEdit = ref(false)
const saving   = ref(false)
const formError = ref('')
const editId    = ref(null)

const addImageRef      = ref(null)
const editImageRef     = ref(null)
const addPreviewSrc    = ref(null)
const editPreviewSrc   = ref(null)
const editCurrentImage = ref('')

const form = reactive({
  productName: '', quantity: '0', rate: '0.00',
  brandName: '', categoryName: '', productStatus: '1'
})

const editForm = reactive({
  editProductName: '', editQuantity: '0', editRate: '0.00',
  editBrandName: '', editCategoryName: '', editProductStatus: '1'
})

function onAddFile(e) {
  const file = e.target.files[0]
  if (!file) { addPreviewSrc.value = null; return }
  const reader = new FileReader()
  reader.onload = ev => { addPreviewSrc.value = ev.target.result }
  reader.readAsDataURL(file)
}

function onEditFile(e) {
  const file = e.target.files[0]
  if (!file) { editPreviewSrc.value = null; return }
  const reader = new FileReader()
  reader.onload = ev => { editPreviewSrc.value = ev.target.result }
  reader.readAsDataURL(file)
}

function openAdd()   { resetForm(); showAdd.value = true }
function resetForm() {
  formError.value    = ''
  form.productName   = ''
  form.quantity      = '0'
  form.rate          = '0.00'
  form.brandName     = ''
  form.categoryName  = ''
  form.productStatus = '1'
  addPreviewSrc.value  = null
  editPreviewSrc.value = null
  if (addImageRef.value)  addImageRef.value.value  = ''
  if (editImageRef.value) editImageRef.value.value = ''
}

async function submitAdd() {
  if (!form.productName.trim() || !form.brandName || !form.categoryName || !form.productStatus) {
    formError.value = 'Todos los campos del producto son requeridos.'
    return
  }
  const imgFile = addImageRef.value?.files[0]

  saving.value = true; formError.value = ''
  try {
    const fd = new FormData()
    if (imgFile) fd.append('productImage', imgFile)
    fd.append('productName', form.productName.trim())
    fd.append('quantity', form.quantity)
    fd.append('rate', form.rate)
    fd.append('brandName', form.brandName)
    fd.append('categoryName', form.categoryName)
    fd.append('productStatus', form.productStatus)

    const { data } = await api.createProduct(fd)
    if (data.success) { showAdd.value = false; await store.reload(); toast.success(data.messages) }
    else formError.value = data.messages
  } catch { formError.value = 'Error de comunicación.' }
  finally { saving.value = false }
}

async function submitEdit() {
  if (!editId.value || !editForm.editProductName.trim() || !editForm.editBrandName || !editForm.editCategoryName || !editForm.editProductStatus) {
    formError.value = 'Todos los campos son requeridos.'
    return
  }
  saving.value = true; formError.value = ''
  try {
    const fd = new FormData()
    fd.append('productId', editId.value)
    fd.append('editProductName', editForm.editProductName.trim())
    fd.append('editQuantity', editForm.editQuantity)
    fd.append('editRate', editForm.editRate)
    fd.append('editBrandName', editForm.editBrandName)
    fd.append('editCategoryName', editForm.editCategoryName)
    fd.append('editProductStatus', editForm.editProductStatus)

    const { data } = await api.editProduct(fd)
    if (!data.success) {
      formError.value = data.messages
      return
    }

    // If a new image was picked, update it too
    const newImg = editImageRef.value?.files[0]
    if (newImg) {
      const imgFd = new FormData()
      imgFd.append('productId', editId.value)
      imgFd.append('editProductImage', newImg)
      const imgRes = await api.editProductImage(imgFd)
      if (!imgRes.data?.success) {
        formError.value = imgRes.data?.messages || 'Producto actualizado, pero error al subir nueva imagen.'
        await store.reload()
        return
      }
    }

    showEdit.value = false
    await store.reload()
    toast.success(data.messages)
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
      resetForm()
      editId.value                 = id
      editCurrentImage.value       = data.product_image || ''
      editForm.editProductName     = data.product_name || ''
      editForm.editQuantity        = String(data.quantity ?? '0')
      editForm.editRate            = String(data.rate ?? '0.00')
      editForm.editBrandName       = data.brand_id ? String(data.brand_id) : ''
      editForm.editCategoryName    = data.categories_id ? String(data.categories_id) : ''
      editForm.editProductStatus   = String(data.active ?? '1')
      showEdit.value               = true
    } else {
      toast.danger('No se pudo cargar el producto.')
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
  await Promise.all([store.load(), optionsStore.load()])
  document.addEventListener('click', handleTableAction)
})

onUnmounted(() => {
  document.removeEventListener('click', handleTableAction)
})
</script>
