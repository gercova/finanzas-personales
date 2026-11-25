<template>
  <div>
    <div class="row">
      <div class="col-sm-7">
        <div class="form-group">
          <label>Nombre de la Cuenta <span class="text-danger">*</span></label>
          <input v-model="f.account_name" type="text" class="form-control" placeholder="Ej: BCP Principal" required>
        </div>
      </div>
      <div class="col-sm-5">
        <div class="form-group">
          <label>Tipo de Cuenta <span class="text-danger">*</span></label>
          <select v-model="f.account_type" class="form-control" required>
            <option value="">-- Seleccionar --</option>
            <option value="BANK">Banco</option>
            <option value="DIGITAL_WALLET">Billetera Digital</option>
            <option value="CASH">Efectivo</option>
            <option value="SAVINGS">Ahorros</option>
            <option value="OTHER">Otro</option>
          </select>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-sm-6">
        <div class="form-group">
          <label>N° de Cuenta (opcional)</label>
          <input v-model="f.account_number" type="text" class="form-control" placeholder="Número de cuenta bancaria">
        </div>
      </div>
      <div class="col-sm-3">
        <div class="form-group">
          <label>Moneda</label>
          <select v-model="f.currency" class="form-control">
            <option value="PEN">PEN (S/)</option>
            <option value="USD">USD ($)</option>
            <option value="EUR">EUR (€)</option>
          </select>
        </div>
      </div>
      <div class="col-sm-3">
        <div class="form-group">
          <label>Color</label>
          <input v-model="f.color" type="color" class="form-control" style="height:36px;padding:2px 4px;">
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-sm-6">
        <div class="form-group">
          <label>Saldo Inicial (S/.) <span class="text-danger">*</span></label>
          <input v-model="f.initial_balance" type="number" step="0.01" class="form-control" placeholder="0.00" required>
        </div>
      </div>
      <div class="col-sm-6">
        <div class="form-group">
          <label>Estado</label>
          <select v-model="f.status" class="form-control">
            <option value="1">Activa</option>
            <option value="0">Inactiva</option>
          </select>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue'
defineProps({ editId: { type: Number, default: null } })

const f = reactive({ account_id:'', account_name:'', account_type:'', account_number:'', currency:'PEN', color:'#2e6da4', initial_balance:'0', status:'1' })

function setData(d) {
  Object.assign(f, {
    account_id:      d.id || d.account_id || '',
    account_name:    d.account_name || '',
    account_type:    d.account_type || '',
    account_number:  d.account_number || '',
    currency:        d.currency || 'PEN',
    color:           d.color || '#2e6da4',
    initial_balance: d.initial_balance || '0',
    status:          String(d.status ?? '1'),
  })
}

function getFormData() {
  if (!f.account_name || !f.account_type) return null
  const fd = new FormData()
  Object.entries(f).forEach(([k, v]) => v !== '' && v !== null && fd.append(k, v))
  return fd
}

defineExpose({ getFormData, setData })
</script>
