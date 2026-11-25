<template>
  <div>
    <input type="hidden" :value="loanId">
    <div class="form-group">
      <label>Cuenta Destino <span class="text-danger">*</span></label>
      <select v-model="f.destination_account_id" class="form-control" required>
        <option value="">-- Seleccione cuenta --</option>
        <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.account_name }} ({{ a.account_type }})</option>
      </select>
    </div>
    <div class="row">
      <div class="col-sm-6">
        <div class="form-group">
          <label>Monto del Pago (S/) <span class="text-danger">*</span></label>
          <input v-model="f.amount" type="number" step="0.01" min="0.01" class="form-control" placeholder="0.00" required>
        </div>
      </div>
      <div class="col-sm-6">
        <div class="form-group">
          <label>Fecha de Pago <span class="text-danger">*</span></label>
          <input v-model="f.payment_date" type="date" class="form-control" required>
        </div>
      </div>
    </div>
    <div class="form-group">
      <label>Comprobante (opcional)</label>
      <input ref="fileRef" type="file" accept="image/*" class="form-control">
    </div>
    <div class="form-group">
      <label>Notas</label>
      <textarea v-model="f.notes" class="form-control" rows="2" placeholder="Observaciones del pago..."></textarea>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
defineProps({ accounts: { type: Array, default: () => [] } })

const today   = new Date().toISOString().slice(0, 10)
const loanId  = ref(null)
const fileRef = ref(null)
const f = reactive({ destination_account_id: '', amount: '', payment_date: today, notes: '' })

function setLoanId(id) {
  loanId.value = id
}

function getFormData() {
  const amt = parseFloat(f.amount)
  if (!loanId.value || !f.destination_account_id || isNaN(amt) || amt <= 0 || !f.payment_date) return null
  const fd = new FormData()
  fd.append('loan_id', loanId.value)
  fd.append('destination_account_id', f.destination_account_id)
  fd.append('amount', amt.toFixed(2))
  fd.append('payment_date', f.payment_date)
  fd.append('notes', f.notes || '')
  if (fileRef.value?.files[0]) fd.append('receipt_image', fileRef.value.files[0])
  return fd
}

defineExpose({ getFormData, setLoanId })
</script>
