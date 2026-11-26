<template>
  <form @submit.prevent class="form-horizontal">
    <!-- Acreedor / Entidad Registrada -->
    <div class="form-group">
      <label class="col-sm-3 control-label">Acreedor / Entidad <span class="text-danger">*</span></label>
      <div class="col-sm-9">
        <div class="input-group">
          <select v-model="form.lender_id" class="form-control" @change="onLenderSelected">
            <option value="">— Escribir manualmente / sin registro —</option>
            <option v-for="l in lenders" :key="l.id" :value="String(l.id)">
              {{ l.full_name }} ({{ lenderTypeShort(l.lender_type) }}{{ l.document_number ? ' · ' + l.document_number : '' }})
            </option>
          </select>
          <span class="input-group-btn">
            <button
              type="button"
              class="btn btn-primary"
              title="Registrar nuevo acreedor, empresa o banco"
              @click="$emit('request-new-lender')"
            >
              <i class="glyphicon glyphicon-plus"></i> Nuevo
            </button>
          </span>
        </div>
      </div>
    </div>

    <!-- Nombre y Tipo manual cuando no se elige del catálogo (o vista previa editable) -->
    <div class="form-group">
      <label class="col-sm-3 control-label">Tipo de Acreedor <span class="text-danger">*</span></label>
      <div class="col-sm-4">
        <select v-model="form.lender_type" class="form-control" :disabled="Boolean(form.lender_id)">
          <option value="PERSONA">Persona Natural</option>
          <option value="EMPRESA">Empresa</option>
          <option value="ENTIDAD_FINANCIERA">Banco / Entidad Fin.</option>
        </select>
      </div>
      <div class="col-sm-5">
        <input
          v-model="form.lender_name"
          type="text"
          class="form-control"
          placeholder="Nombre de persona, empresa o banco"
          :readonly="Boolean(form.lender_id)"
          required
        />
      </div>
    </div>

    <!-- Concepto -->
    <div class="form-group">
      <label class="col-sm-3 control-label">Concepto <span class="text-danger">*</span></label>
      <div class="col-sm-9">
        <input
          v-model="form.concept"
          type="text"
          class="form-control"
          placeholder="Ej. Crédito personal BCP, Préstamo de capital, Tarjeta en cuotas…"
          required
        />
      </div>
    </div>

    <!-- Cuenta Destino (donde ingresa el dinero recibido) -->
    <div class="form-group">
      <label class="col-sm-3 control-label">Cuenta Destino <span class="text-danger">*</span></label>
      <div class="col-sm-9">
        <select v-model="form.account_id" class="form-control" required>
          <option value="">Seleccione cuenta que recibe el dinero…</option>
          <option v-for="a in accounts" :key="a.id" :value="String(a.id)">
            {{ a.account_name }} (Saldo actual: S/ {{ fmtMoney(a.current_balance) }})
          </option>
        </select>
        <small class="text-muted">El capital recibido se sumará al saldo de esta cuenta.</small>
      </div>
    </div>

    <!-- Fechas -->
    <div class="form-group">
      <label class="col-sm-3 control-label">Fecha Préstamo <span class="text-danger">*</span></label>
      <div class="col-sm-4">
        <input v-model="form.debt_date" type="date" class="form-control" required />
      </div>
      <label class="col-sm-2 control-label">Vencimiento</label>
      <div class="col-sm-3">
        <input v-model="form.due_date" type="date" class="form-control" />
      </div>
    </div>

    <!-- Capital e Interés -->
    <div class="form-group">
      <label class="col-sm-3 control-label">Capital Recibido <span class="text-danger">*</span></label>
      <div class="col-sm-3">
        <div class="input-group">
          <span class="input-group-addon">S/</span>
          <input v-model.number="form.principal_amount" type="number" step="0.01" min="0.01" class="form-control" required />
        </div>
      </div>
      <label class="col-sm-2 control-label">Interés</label>
      <div class="col-sm-2">
        <select v-model="form.interest_type" class="form-control">
          <option value="NONE">Sin interés</option>
          <option value="PERCENTAGE">%</option>
          <option value="FIXED">Monto Fijo</option>
        </select>
      </div>
      <div class="col-sm-2">
        <input
          v-model.number="form.interest_value"
          type="number"
          step="0.01"
          min="0"
          class="form-control"
          :disabled="form.interest_type === 'NONE'"
          placeholder="0.00"
        />
      </div>
    </div>

    <!-- Total calculado -->
    <div class="form-group">
      <label class="col-sm-3 control-label">Total a Pagar</label>
      <div class="col-sm-9">
        <div class="well well-sm" style="margin-bottom:0; background:#fdf2f2; border-color:#f5c6cb; font-weight:700; color:#c0392b; font-size:15px;">
          S/ {{ fmtMoney(computedTotalPayable) }}
        </div>
      </div>
    </div>

    <!-- Estado (en edición) -->
    <div v-if="editId" class="form-group">
      <label class="col-sm-3 control-label">Estado</label>
      <div class="col-sm-9">
        <select v-model="form.status" class="form-control">
          <option value="PENDIENTE">PENDIENTE</option>
          <option value="PARCIAL">PARCIAL</option>
          <option value="PAGADO">PAGADO</option>
        </select>
      </div>
    </div>

    <!-- Notas -->
    <div class="form-group">
      <label class="col-sm-3 control-label">Notas</label>
      <div class="col-sm-9">
        <textarea v-model="form.notes" class="form-control" rows="2" placeholder="Cronograma de cuotas, TEA, condiciones…"></textarea>
      </div>
    </div>
  </form>
</template>

<script setup>
import { reactive, computed } from 'vue'
import { fmtMoney } from '@/utils/format'

const props = defineProps({
  accounts: { type: Array, default: () => [] },
  lenders:  { type: Array, default: () => [] },
  editId:   { type: [Number, String], default: null }
})

defineEmits(['request-new-lender'])

const todayISO = () => new Date().toLocaleDateString('en-CA')

const form = reactive({
  lender_id:        '',
  lender_name:      '',
  lender_type:      'PERSONA',
  concept:          '',
  account_id:       '',
  debt_date:        todayISO(),
  due_date:         '',
  principal_amount: '',
  interest_type:    'NONE',
  interest_value:   '',
  status:           'PENDIENTE',
  notes:            '',
})

function lenderTypeShort(type) {
  if (type === 'ENTIDAD_FINANCIERA') return 'Banco/Ent.'
  if (type === 'EMPRESA') return 'Empresa'
  return 'Persona'
}

function onLenderSelected() {
  if (!form.lender_id) return
  const found = props.lenders.find(l => String(l.id) === String(form.lender_id))
  if (found) {
    form.lender_name = found.full_name
    form.lender_type = found.lender_type || 'PERSONA'
  }
}

function selectLender(lender) {
  if (!lender) return
  form.lender_id   = String(lender.id)
  form.lender_name = lender.full_name || ''
  form.lender_type = lender.lender_type || 'PERSONA'
}

const computedTotalPayable = computed(() => {
  const cap = parseFloat(form.principal_amount) || 0
  const val = parseFloat(form.interest_value) || 0
  if (form.interest_type === 'PERCENTAGE' && val > 0) return cap + (cap * val / 100)
  if (form.interest_type === 'FIXED' && val > 0)      return cap + val
  return cap
})

function reset() {
  form.lender_id        = ''
  form.lender_name      = ''
  form.lender_type      = 'PERSONA'
  form.concept          = ''
  form.account_id       = ''
  form.debt_date        = todayISO()
  form.due_date         = ''
  form.principal_amount = ''
  form.interest_type    = 'NONE'
  form.interest_value   = ''
  form.status           = 'PENDIENTE'
  form.notes            = ''
}

function setData(d) {
  form.lender_id        = d.lender_id ? String(d.lender_id) : ''
  form.lender_name      = d.lender_name || ''
  form.lender_type      = d.lender_type || 'PERSONA'
  form.concept          = d.concept || ''
  form.account_id       = d.account_id ? String(d.account_id) : ''
  form.debt_date        = d.debt_date || todayISO()
  form.due_date         = d.due_date || ''
  form.principal_amount = d.principal_amount ? Number(d.principal_amount) : ''
  form.interest_type    = d.interest_type || 'NONE'
  form.interest_value   = d.interest_value ? Number(d.interest_value) : ''
  form.status           = d.status || 'PENDIENTE'
  form.notes            = d.notes || ''
}

function getFormData() {
  const cap = parseFloat(form.principal_amount) || 0
  if (!form.lender_name.trim() || !form.concept.trim() || !form.account_id || cap <= 0 || !form.debt_date) {
    return null
  }
  const fd = new URLSearchParams()
  if (props.editId) fd.append('debt_id', String(props.editId))
  if (form.lender_id) fd.append('lender_id', String(form.lender_id))
  fd.append('lender_name', form.lender_name.trim())
  fd.append('lender_type', form.lender_type)
  fd.append('concept', form.concept.trim())
  fd.append('account_id', String(form.account_id))
  fd.append('debt_date', form.debt_date)
  if (form.due_date) fd.append('due_date', form.due_date)
  fd.append('principal_amount', String(cap))
  fd.append('interest_type', form.interest_type)
  if (form.interest_type !== 'NONE' && form.interest_value !== '' && form.interest_value !== null) {
    fd.append('interest_value', String(form.interest_value))
  }
  if (props.editId) fd.append('status', form.status)
  fd.append('notes', form.notes.trim())
  return fd
}

defineExpose({ getFormData, setData, reset, selectLender })
</script>
