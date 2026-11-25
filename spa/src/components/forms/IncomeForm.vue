<template>
  <div>
    <div class="row">
      <div class="col-sm-8">
        <div class="form-group">
          <label>Concepto <span class="text-danger">*</span></label>
          <input v-model="f.concept" type="text" class="form-control" placeholder="Ej: Mensualidad DRE" required>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="form-group">
          <label>Monto (S/) <span class="text-danger">*</span></label>
          <input v-model="f.amount" type="number" step="0.01" min="0.01" class="form-control" placeholder="0.00" required>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-sm-6">
        <div class="form-group">
          <label>Cuenta Destino <span class="text-danger">*</span></label>
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
      <div class="col-sm-6">
        <div class="form-group">
          <label>Fecha <span class="text-danger">*</span></label>
          <input v-model="f.income_date" type="date" class="form-control" required>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-sm-4">
        <div class="form-group">
          <label>Estado</label>
          <select v-model="f.status" class="form-control">
            <option value="CONFIRMED">Confirmado</option>
            <option value="PENDING">Pendiente</option>
            <option value="CANCELLED">Cancelado</option>
          </select>
        </div>
      </div>
      <div class="col-sm-4" style="padding-top:26px;">
        <label class="checkbox-inline">
          <input type="checkbox" v-model="f.is_recurring" :true-value="'1'" :false-value="'0'"> ¿Es recurrente?
        </label>
      </div>
    </div>
    <div class="form-group">
      <label>Notas</label>
      <textarea v-model="f.notes" class="form-control" rows="2" placeholder="Observaciones adicionales..."></textarea>
    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue'

defineProps({
  accounts:   { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
  projects:   { type: Array, default: () => [] },
  editId:     { type: Number, default: null },
})

const today = new Date().toISOString().slice(0, 10)

const f = reactive({
  income_id: '', concept: '', amount: '', account_id: '',
  category_id: '', project_id: '', income_date: today,
  status: 'CONFIRMED', is_recurring: '0', notes: ''
})

function setData(d) {
  if (!d) return
  const statusMap = {
    CONFIRMED: 'CONFIRMED', ACTIVO: 'CONFIRMED',
    PENDING: 'PENDING',     PENDIENTE: 'PENDING',
    CANCELLED: 'CANCELLED', CANCELADO: 'CANCELLED',
  }
  Object.assign(f, {
    income_id:    d.id || d.income_id || '',
    concept:      d.concept || '',
    amount:       d.amount ?? '',
    account_id:   d.account_id ? String(d.account_id) : '',
    category_id:  d.category_id ? String(d.category_id) : '',
    project_id:   d.project_id  ? String(d.project_id)  : '',
    income_date:  d.income_date || today,
    status:       statusMap[d.status] || 'CONFIRMED',
    is_recurring: d.is_recurring == 1 ? '1' : '0',
    notes:        d.notes || '',
  })
}

function getFormData() {
  const amt = parseFloat(f.amount)
  if (!f.concept.trim() || isNaN(amt) || amt <= 0 || !f.account_id || !f.income_date) return null
  const fd = new FormData()
  if (f.income_id) fd.append('income_id', f.income_id)
  fd.append('concept',      f.concept.trim())
  fd.append('amount',       amt.toFixed(2))
  fd.append('account_id',   f.account_id)
  fd.append('category_id',  f.category_id || '')
  fd.append('project_id',   f.project_id  || '')
  fd.append('income_date',  f.income_date)
  fd.append('status',       f.status)
  fd.append('is_recurring', f.is_recurring)
  fd.append('notes',        f.notes || '')
  return fd
}

function reset() {
  Object.assign(f, {
    income_id: '', concept: '', amount: '', account_id: '',
    category_id: '', project_id: '', income_date: today,
    status: 'CONFIRMED', is_recurring: '0', notes: ''
  })
}

defineExpose({ getFormData, setData, reset })
</script>
