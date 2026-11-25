<template>
  <div>
    <div class="row">
      <div class="col-sm-6">
        <div class="form-group">
          <label>Nombre del Prestatario <span class="text-danger">*</span></label>
          <input v-model="f.borrower_name" type="text" class="form-control"
                 placeholder="Ej: Juan Pérez" required>
        </div>
      </div>
      <div class="col-sm-6">
        <div class="form-group">
          <label>Concepto <span class="text-danger">*</span></label>
          <input v-model="f.concept" type="text" class="form-control"
                 placeholder="Ej: Préstamo personal" required>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-sm-6">
        <div class="form-group">
          <label>Cuenta de Origen <span class="text-danger">*</span></label>
          <select v-model="f.account_id" class="form-control" required>
            <option value="">-- Seleccione cuenta --</option>
            <option v-for="a in accounts" :key="a.id" :value="a.id">
              {{ a.account_name }} ({{ a.account_type }})
            </option>
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
          <input v-model="f.principal_amount" type="number" step="0.01" min="0.01"
                 class="form-control" placeholder="0.00" required @input="calcTotal">
        </div>
      </div>
      <div class="col-sm-4">
        <div class="form-group">
          <label>Tipo de Interés</label>
          <div>
            <label class="radio-inline">
              <input type="radio" v-model="f.interest_type" value="NONE" @change="onInterestTypeChange">
              Sin Interés
            </label>
            <label class="radio-inline">
              <input type="radio" v-model="f.interest_type" value="PERCENTAGE" @change="onInterestTypeChange">
              %
            </label>
            <label class="radio-inline">
              <input type="radio" v-model="f.interest_type" value="FIXED" @change="onInterestTypeChange">
              Fijo S/
            </label>
          </div>
        </div>
      </div>
      <div class="col-sm-4" v-show="f.interest_type !== 'NONE'">
        <div class="form-group">
          <label>{{ f.interest_type === 'PERCENTAGE' ? 'Porcentaje (%)' : 'Monto Fijo (S/)' }}</label>
          <input v-model="f.interest_value" type="number" step="0.01" min="0"
                 class="form-control" placeholder="0.00" @input="calcTotal">
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-sm-6">
        <div class="form-group">
          <label>Total a Cobrar (S/)</label>
          <input :value="totalReceivable" type="number" class="form-control"
                 readonly style="background:#f9f9f9;">
        </div>
      </div>
      <!-- Show status selector only on edit -->
      <div class="col-sm-6" v-if="f.loan_id">
        <div class="form-group">
          <label>Estado</label>
          <select v-model="f.status" class="form-control">
            <option value="PENDIENTE">Pendiente</option>
            <option value="PARCIAL">Parcial</option>
            <option value="PAGADO">Pagado</option>
          </select>
        </div>
      </div>
    </div>
    <div class="form-group">
      <label>Notas</label>
      <textarea v-model="f.notes" class="form-control" rows="2"
                placeholder="Observaciones del préstamo..."></textarea>
    </div>
  </div>
</template>

<script setup>
import { reactive, computed } from 'vue'

defineProps({
  accounts: { type: Array, default: () => [] },
  editId:   { type: Number, default: null }
})

const today = new Date().toISOString().slice(0, 10)

const f = reactive({
  loan_id: '',
  borrower_name: '', concept: '', account_id: '',
  loan_date: today, due_date: '', principal_amount: '',
  interest_type: 'NONE', interest_value: '',
  total_receivable: '', status: 'PENDIENTE', notes: ''
})

const totalReceivable = computed(() => {
  const p = parseFloat(f.principal_amount) || 0
  const v = parseFloat(f.interest_value)   || 0
  if (f.interest_type === 'PERCENTAGE') return (p + p * v / 100).toFixed(2)
  if (f.interest_type === 'FIXED')      return (p + v).toFixed(2)
  return p.toFixed(2)
})

function onInterestTypeChange() {
  if (f.interest_type === 'NONE') {
    f.interest_value = ''
  }
  calcTotal()
}

function calcTotal() { f.total_receivable = totalReceivable.value }

function setData(d) {
  if (!d) return
  const intMap = {
    NONE: 'NONE', PERCENTAGE: 'PERCENTAGE', FIXED: 'FIXED',
    NINGUNO: 'NONE', PORCENTAJE: 'PERCENTAGE', FIJO: 'FIXED'
  }
  Object.assign(f, {
    loan_id:          d.id || d.loan_id      || '',
    borrower_name:    d.borrower_name        || '',
    concept:          d.concept              || '',
    account_id:       d.account_id ? String(d.account_id) : '',
    loan_date:        d.loan_date            || today,
    due_date:         d.due_date             || '',
    principal_amount: d.principal_amount     ?? '',
    interest_type:    intMap[d.interest_type] ?? 'NONE',
    interest_value:   d.interest_value       ?? '',
    status:           d.status               || 'PENDIENTE',
    notes:            d.notes               || '',
  })
}

function getFormData() {
  const principal = parseFloat(f.principal_amount)
  if (!f.borrower_name.trim() || !f.concept.trim() || !f.account_id || isNaN(principal) || principal <= 0 || !f.loan_date) {
    return null
  }
  const fd = new FormData()
  if (f.loan_id) fd.append('loan_id', f.loan_id)
  fd.append('borrower_name',    f.borrower_name.trim())
  fd.append('concept',          f.concept.trim())
  fd.append('account_id',       f.account_id)
  fd.append('loan_date',        f.loan_date)
  fd.append('due_date',         f.due_date || '')
  fd.append('principal_amount', principal.toFixed(2))
  fd.append('interest_type',    f.interest_type)
  if (f.interest_type !== 'NONE' && f.interest_value !== '') {
    fd.append('interest_value', f.interest_value)
  }
  fd.append('total_receivable', totalReceivable.value)
  if (f.loan_id) fd.append('status', f.status)
  fd.append('notes',            f.notes || '')
  return fd
}

defineExpose({ getFormData, setData })
</script>
