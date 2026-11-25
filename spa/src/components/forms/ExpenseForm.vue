<template>
  <div>
    <div class="row">
      <div class="col-sm-8">
        <div class="form-group">
          <label>Concepto <span class="text-danger">*</span></label>
          <input v-model="f.concept" type="text" class="form-control" placeholder="Ej: Alquiler oficina" required>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="form-group">
          <label>Monto (S/) <span class="text-danger">*</span></label>
          <input v-model="f.amount" type="number" step="0.01" min="0" class="form-control" placeholder="0.00" required>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-sm-6">
        <div class="form-group">
          <label>Cuenta Origen <span class="text-danger">*</span></label>
          <select v-model="f.account_id" class="form-control" required>
            <option value="">-- Seleccione cuenta --</option>
            <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.account_name }} ({{ a.account_type }})</option>
          </select>
        </div>
      </div>
      <div class="col-sm-6">
        <div class="form-group">
          <label>Categoría</label>
          <select v-model="f.category_id" class="form-control">
            <option value="">-- Seleccione categoría --</option>
            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.category_name }}</option>
          </select>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-sm-6">
        <div class="form-group">
          <label>Proyecto (opcional)</label>
          <select v-model="f.project_id" class="form-control">
            <option value="">-- Sin proyecto --</option>
            <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.project_name }}</option>
          </select>
        </div>
      </div>
      <div class="col-sm-3">
        <div class="form-group">
          <label>Fecha <span class="text-danger">*</span></label>
          <input v-model="f.expense_date" type="date" class="form-control" required>
        </div>
      </div>
      <div class="col-sm-3">
        <div class="form-group">
          <label>Estado</label>
          <select v-model="f.status" class="form-control">
            <option value="PAGADO">PAGADO</option>
            <option value="PENDIENTE">PENDIENTE</option>
          </select>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-sm-6">
        <div class="form-group">
          <label>Comprobante (foto/imagen)</label>
          <input ref="fileRef" type="file" name="receipt_image" accept="image/*" class="form-control" @change="onFile">
          <div v-if="previewSrc" style="margin-top:8px;">
            <img :src="previewSrc" style="max-height:120px;border-radius:4px;border:1px solid #ddd;">
          </div>
          <div v-if="currentImage && !previewSrc" style="margin-top:4px;font-size:12px;" class="text-muted">
            Comprobante actual: <a :href="'/' + currentImage" target="_blank">ver</a>
          </div>
        </div>
      </div>
      <div class="col-sm-6">
        <div class="form-group">
          <label>Notas</label>
          <textarea v-model="f.notes" class="form-control" rows="3" placeholder="Observaciones adicionales..."></textarea>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'

defineProps({
  accounts:   { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
  projects:   { type: Array, default: () => [] },
  editId:     { type: Number, default: null },
})

const today = new Date().toISOString().slice(0, 10)
const fileRef    = ref(null)
const previewSrc = ref(null)
const currentImage = ref(null)

const f = reactive({
  expense_id: '', concept: '', amount: '', account_id: '',
  category_id: '', project_id: '', expense_date: today,
  status: 'PAGADO', notes: ''
})

function onFile(e) {
  const file = e.target.files[0]
  if (!file) { previewSrc.value = null; return }
  const reader = new FileReader()
  reader.onload = ev => { previewSrc.value = ev.target.result }
  reader.readAsDataURL(file)
}

function setData(d) {
  currentImage.value = d.receipt_image || null
  previewSrc.value = null
  Object.assign(f, {
    expense_id:   d.id || d.expense_id || '',
    concept:      d.concept || '',
    amount:       d.amount || '',
    account_id:   d.account_id || '',
    category_id:  d.category_id || '',
    project_id:   d.project_id || '',
    expense_date: d.expense_date || today,
    status:       d.status || 'PAGADO',
    notes:        d.notes || '',
  })
}

function getFormData() {
  if (!f.concept || !f.amount || !f.account_id || !f.expense_date) return null
  const fd = new FormData()
  Object.entries(f).forEach(([k, v]) => v !== '' && v !== null && fd.append(k, v))
  if (fileRef.value?.files[0]) fd.append('receipt_image', fileRef.value.files[0])
  return fd
}

defineExpose({ getFormData, setData })
</script>
