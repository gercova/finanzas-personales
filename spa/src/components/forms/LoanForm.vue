<template>
  <div>
    <div class="row">
      <div class="col-sm-6">
        <div class="form-group">
          <label>Nombre del Prestatario <span class="text-danger">*</span></label>
          <input v-model="f.borrower_name" type="text" class="form-control" placeholder="Ej: Juan Pérez" required>
        </div>
      </div>
      <div class="col-sm-6">
        <div class="form-group">
          <label>Concepto <span class="text-danger">*</span></label>
          <input v-model="f.concept" type="text" class="form-control" placeholder="Ej: Préstamo personal" required>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-sm-6">
        <div class="form-group">
          <label>Cuenta de Origen <span class="text-danger">*</span></label>
          <select v-model="f.account_id" class="form-control" required>
            <option value="">-- Seleccione cuenta --</option>
            <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.account_name }} ({{ a.account_type }})</option>
          </select>
        </div>
      </div>
      <div class="col-sm-3">
        <div class="form-group">
          <label>Fecha de Préstamo <span class="text-danger">*</span></label>
          <input v-model="f.loan_date" type="date" class="form-control" required>
        </div>
      </div>
      <div class="col-sm-3">
        <div class="form-group">
          <label>Fecha de Vencimiento</label>
          <input v-model="f.due_date" type="date" class="form-control">
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-sm-4">
        <div class="form-group">
          <label>Capital Prestado (S/) <span class="text-danger">*</span></label>
          <input v-model="f.principal_amount" type="number" step="0.01" min="0" class="form-control" placeholder="0.00" required @input="calcTotal">
        </div>
      </div>
      <div class="col-sm-4">
        <div class="form-group">
          <label>Tipo de Interés</label>
          <div>
            <label class="radio-inline"><input type="radio" v-model="f.interest_type" value="NINGUNO" @change="calcTotal"> Sin Interés</label>
            <label class="radio-inline"><input type="radio" v-model="f.interest_type" value="PORCENTAJE" @change="calcTotal"> %</label>
            <label class="radio-inline"><input type="radio" v-model="f.interest_type" value="FIJO" @change="calcTotal"> Fijo S/</label>
          </div>
        </div>
      </div>
      <div class="col-sm-4" v-show="f.interest_type !== 'NINGUNO'">
        <div class="form-group">
          <label>{{ f.interest_type === 'PORCENTAJE' ? 'Porcentaje (%)' : 'Monto Fijo (S/)' }}</label>
          <input v-model="f.interest_value" type="number" step="0.01" min="0" class="form-control" placeholder="0.00" @input="calcTotal">
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-sm-6">
        <div class="form-group">
          <label>Total a Cobrar (S/)</label>
          <input :value="totalReceivable" type="number" class="form-control" readonly style="background:#f9f9f9;">
        </div>
      </div>
    </div>
    <div class="form-group">
      <label>Notas</label>
      <textarea v-model="f.notes" class="form-control" rows="2" placeholder="Observaciones del préstamo..."></textarea>
    </div>
  </div>
</template>

<script setup>
import { reactive, computed } from 'vue'

defineProps({ accounts: { type: Array, default: () => [] }, editId: { type: Number, default: null } })

const today = new Date().toISOString().slice(0, 10)
const f = reactive({
  loan_id: '', borrower_name: '', concept: '', account_id: '',
  loan_date: today, due_date: '', principal_amount: '',
  interest_type: 'NINGUNO', interest_value: '',
  total_receivable: '', notes: ''
})

const totalReceivable = computed(() => {
  const p = parseFloat(f.principal_amount) || 0
  const v = parseFloat(f.interest_value) || 0
  if (f.interest_type === 'PORCENTAJE') return (p + p * v / 100).toFixed(2)
  if (f.interest_type === 'FIJO')       return (p + v).toFixed(2)
  return p.toFixed(2)
})

function calcTotal() { f.total_receivable = totalReceivable.value }

function setData(d) {
  const intMap = { NONE: 'NINGUNO', PERCENTAGE: 'PORCENTAJE', FIXED: 'FIJO' }
  Object.assign(f, {
    loan_id:          d.id || d.loan_id || '',
    borrower_name:    d.borrower_name || '',
    concept:          d.concept || '',
    account_id:       d.account_id || '',
    loan_date:        d.loan_date || today,
    due_date:         d.due_date || '',
    principal_amount: d.principal_amount || '',
    interest_type:    intMap[d.interest_type] || 'NINGUNO',
    interest_value:   d.interest_value || '',
    notes:            d.notes || '',
  })
}

function getFormData() {
  if (!f.borrower_name || !f.concept || !f.account_id || !f.principal_amount || !f.loan_date) return null
  const fd = new FormData()
  Object.entries(f).forEach(([k, v]) => v !== '' && v !== null && fd.append(k, v))
  fd.set('total_receivable', totalReceivable.value)
  return fd
}

defineExpose({ getFormData, setData })
</script>
