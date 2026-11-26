<template>
  <form @submit.prevent>
    <div class="form-group">
      <label>Cuenta Origen del Pago <span class="text-danger">*</span></label>
      <select v-model="form.source_account_id" class="form-control" required>
        <option value="">Seleccione cuenta desde donde se paga la cuota…</option>
        <option v-for="a in accounts" :key="a.id" :value="String(a.id)">
          {{ a.account_name }} (S/ {{ fmtMoney(a.current_balance) }})
        </option>
      </select>
      <small class="text-muted">El monto pagado se descontará del saldo de esta cuenta.</small>
    </div>

    <div class="row">
      <div class="col-sm-6">
        <div class="form-group">
          <label>Fecha de Pago <span class="text-danger">*</span></label>
          <input v-model="form.payment_date" type="date" class="form-control" required />
        </div>
      </div>
      <div class="col-sm-6">
        <div class="form-group">
          <label>Monto Pagado (S/) <span class="text-danger">*</span></label>
          <input v-model.number="form.amount" type="number" step="0.01" min="0.01" class="form-control" required />
        </div>
      </div>
    </div>

    <div class="form-group">
      <label>Comprobante / Voucher (opcional)</label>
      <input ref="fileInputRef" type="file" accept="image/*" class="form-control" @change="onFileChange" />
    </div>

    <div class="form-group" style="margin-bottom:0;">
      <label>Notas</label>
      <textarea v-model="form.notes" class="form-control" rows="2" placeholder="N° de cuota, operación bancaria…"></textarea>
    </div>
  </form>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { fmtMoney } from '@/utils/format'

defineProps({
  accounts: { type: Array, default: () => [] }
})

const fileInputRef = ref(null)
const selectedFile = ref(null)

const todayISO = () => new Date().toLocaleDateString('en-CA')

const form = reactive({
  debt_id:           0,
  source_account_id: '',
  payment_date:      todayISO(),
  amount:            '',
  notes:             '',
})

function onFileChange(e) {
  selectedFile.value = e.target.files?.[0] || null
}

function setDebtId(id, suggestedAmount = '') {
  form.debt_id           = Number(id) || 0
  form.source_account_id = ''
  form.payment_date      = todayISO()
  form.amount            = suggestedAmount ? Number(suggestedAmount) : ''
  form.notes             = ''
  selectedFile.value     = null
  if (fileInputRef.value) fileInputRef.value.value = ''
}

function getFormData() {
  const amt = parseFloat(form.amount) || 0
  if (!form.debt_id || !form.source_account_id || amt <= 0 || !form.payment_date) {
    return null
  }
  const fd = new FormData()
  fd.append('debt_id', String(form.debt_id))
  fd.append('source_account_id', String(form.source_account_id))
  fd.append('payment_date', form.payment_date)
  fd.append('amount', String(amt))
  fd.append('notes', form.notes.trim())
  if (selectedFile.value) {
    fd.append('receipt_image', selectedFile.value)
  }
  return fd
}

defineExpose({ getFormData, setDebtId })
</script>
