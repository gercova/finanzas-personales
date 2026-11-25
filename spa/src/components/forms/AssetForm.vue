<template>
  <div>
    <div class="row">
      <div class="col-sm-8">
        <div class="form-group">
          <label>Nombre del Activo <span class="text-danger">*</span></label>
          <input v-model="f.asset_name" type="text" class="form-control"
                 placeholder="Ej: Toyota Yaris 2022" required>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="form-group">
          <label>Categoría <span class="text-danger">*</span></label>
          <select v-model="f.category" class="form-control" required>
            <option value="">-- Seleccionar --</option>
            <option value="VEHICULO">🚗 Vehículo</option>
            <option value="INMUEBLE">🏠 Inmueble</option>
            <option value="DOCUMENTO_FIDUCIARIO">📄 Documento Fiduciario</option>
            <option value="EQUIPO_TECNOLOGICO">💻 Equipo Tecnológico</option>
            <option value="OTRO">📦 Otro</option>
          </select>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-sm-4">
        <div class="form-group">
          <label>Valor Estimado S/. <span class="text-danger">*</span></label>
          <input v-model="f.estimated_value" type="number" step="0.01" min="0.01"
                 class="form-control" placeholder="0.00" required>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="form-group">
          <label>Precio de Compra S/.</label>
          <input v-model="f.purchase_price" type="number" step="0.01" min="0"
                 class="form-control" placeholder="0.00">
        </div>
      </div>
      <div class="col-sm-4">
        <div class="form-group">
          <label>Fecha de Adquisición</label>
          <input v-model="f.purchase_date" type="date" class="form-control">
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-sm-6">
        <div class="form-group">
          <label>N° Documento / Título</label>
          <input v-model="f.document_number" type="text" class="form-control"
                 placeholder="Placa, Partida, Serie...">
        </div>
      </div>
      <div class="col-sm-6">
        <div class="form-group">
          <label>Ubicación / Custodia</label>
          <input v-model="f.location_or_custody" type="text" class="form-control"
                 placeholder="Dirección, banco custodiante...">
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-sm-4">
        <div class="form-group">
          <label>Estado</label>
          <select v-model="f.status" class="form-control">
            <option value="ACTIVO">Activo</option>
            <option value="EN_GARANTIA">En Garantía</option>
            <option value="VENDIDO">Vendido</option>
            <option value="DADO_DE_BAJA">Dado de Baja</option>
          </select>
        </div>
      </div>
      <div class="col-sm-8">
        <div class="form-group">
          <label>Fotografía del Activo</label>
          <input ref="fileRef" type="file" class="form-control"
                 accept=".jpg,.jpeg,.png,.gif" @change="onFile">
          <div v-if="previewSrc" style="margin-top:6px;">
            <img :src="previewSrc" alt="Vista previa"
                 style="max-height:80px;border-radius:4px;">
          </div>
          <div v-else-if="currentImage" style="margin-top:4px;font-size:12px;" class="text-muted">
            Imagen actual: <a :href="baseUrl + currentImage" target="_blank">ver</a>
          </div>
        </div>
      </div>
    </div>
    <div class="form-group">
      <label>Notas</label>
      <textarea v-model="f.notes" class="form-control" rows="2"
                placeholder="Observaciones adicionales..."></textarea>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { getBaseURL } from '@/api/http'

defineProps({ editId: { type: Number, default: null } })

const baseUrl      = getBaseURL()
const fileRef      = ref(null)
const previewSrc   = ref(null)
const currentImage = ref(null)

const f = reactive({
  asset_id: '',
  asset_name: '', category: '', estimated_value: '', purchase_price: '',
  purchase_date: '', document_number: '', location_or_custody: '',
  status: 'ACTIVO', notes: ''
})

function onFile(e) {
  const file = e.target.files[0]
  if (!file) { previewSrc.value = null; return }
  const reader = new FileReader()
  reader.onload = ev => { previewSrc.value = ev.target.result }
  reader.readAsDataURL(file)
}

function setData(d) {
  if (!d) return
  currentImage.value = d.image_path || null
  previewSrc.value   = null
  if (fileRef.value) fileRef.value.value = ''
  Object.assign(f, {
    asset_id:            d.asset_id            || '',
    asset_name:          d.asset_name          || '',
    category:            d.category            || '',
    estimated_value:     d.estimated_value     ?? '',
    purchase_price:      d.purchase_price      ?? '',
    purchase_date:       d.purchase_date       || '',
    document_number:     d.document_number     || '',
    location_or_custody: d.location_or_custody || '',
    status:              d.status              || 'ACTIVO',
    notes:               d.notes               || '',
  })
}

function getFormData() {
  const est = parseFloat(f.estimated_value)
  if (!f.asset_name.trim() || !f.category || isNaN(est) || est <= 0) return null

  const fd     = new FormData()
  const isEdit = !!f.asset_id

  if (isEdit) {
    fd.append('edit_asset_id',            f.asset_id)
    fd.append('edit_asset_name',          f.asset_name.trim())
    fd.append('edit_category',            f.category)
    fd.append('edit_estimated_value',     est.toFixed(2))
    fd.append('edit_purchase_price',      f.purchase_price || '0')
    fd.append('edit_purchase_date',       f.purchase_date || '')
    fd.append('edit_document_number',     f.document_number || '')
    fd.append('edit_location_or_custody', f.location_or_custody || '')
    fd.append('edit_status',              f.status)
    fd.append('edit_notes',               f.notes || '')
    if (fileRef.value?.files[0]) fd.append('edit_asset_image', fileRef.value.files[0])
  } else {
    fd.append('asset_name',          f.asset_name.trim())
    fd.append('category',            f.category)
    fd.append('estimated_value',     est.toFixed(2))
    fd.append('purchase_price',      f.purchase_price || '0')
    fd.append('purchase_date',       f.purchase_date || '')
    fd.append('document_number',     f.document_number || '')
    fd.append('location_or_custody', f.location_or_custody || '')
    fd.append('status',              f.status)
    fd.append('notes',               f.notes || '')
    if (fileRef.value?.files[0]) fd.append('asset_image', fileRef.value.files[0])
  }

  return fd
}

defineExpose({ getFormData, setData })
</script>
