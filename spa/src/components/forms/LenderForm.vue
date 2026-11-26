<template>
  <form @submit.prevent class="form-horizontal">
    <!-- Tipo de Acreedor / Entidad -->
    <div class="form-group">
      <label class="col-sm-3 control-label">Tipo <span class="text-danger">*</span></label>
      <div class="col-sm-9">
        <select v-model="form.lender_type" class="form-control" @change="onLenderTypeChange">
          <option value="PERSONA">Persona Natural</option>
          <option value="EMPRESA">Empresa / Persona Jurídica</option>
          <option value="ENTIDAD_FINANCIERA">Entidad Financiera / Banco</option>
        </select>
      </div>
    </div>

    <!-- Tipo y Número de Documento + Búsqueda Decolecta -->
    <div class="form-group">
      <label class="col-sm-3 control-label">Documento <span class="text-danger">*</span></label>
      <div class="col-sm-3">
        <select v-model="form.document_type" class="form-control">
          <option value="DNI">DNI</option>
          <option value="RUC">RUC</option>
          <option value="CE">C.E.</option>
          <option value="PASAPORTE">Pasaporte</option>
          <option value="OTRO">Otro</option>
        </select>
      </div>
      <div class="col-sm-6">
        <div class="input-group">
          <input
            v-model="form.document_number"
            type="text"
            class="form-control"
            :maxlength="form.document_type === 'DNI' ? 8 : (form.document_type === 'RUC' ? 11 : 20)"
            :placeholder="docPlaceholder"
            required
            @keyup.enter="searchDocument"
          />
          <span class="input-group-btn">
            <button
              type="button"
              class="btn btn-info"
              :disabled="searching || !canLookupDecolecta"
              title="Consultar en RENIEC / SUNAT (Decolecta)"
              @click="searchDocument"
            >
              <i class="glyphicon" :class="searching ? 'glyphicon-refresh' : 'glyphicon-search'"></i>
              {{ searching ? 'Buscando…' : 'Buscar' }}
            </button>
          </span>
        </div>
        <small v-if="lookupMsg" :class="lookupOk ? 'text-success' : 'text-danger'" style="display:block; margin-top:4px;">
          {{ lookupMsg }}
        </small>
      </div>
    </div>

    <!-- Nombre o Razón Social -->
    <div class="form-group">
      <label class="col-sm-3 control-label">
        {{ form.lender_type === 'PERSONA' ? 'Nombre Completo' : 'Razón Social / Entidad' }}
        <span class="text-danger">*</span>
      </label>
      <div class="col-sm-9">
        <input
          v-model="form.full_name"
          type="text"
          class="form-control"
          :placeholder="namePlaceholder"
          required
        />
      </div>
    </div>

    <!-- Teléfono y Correo -->
    <div class="form-group">
      <label class="col-sm-3 control-label">Teléfono</label>
      <div class="col-sm-4">
        <input v-model="form.phone" type="text" class="form-control" placeholder="Opcional" />
      </div>
      <label class="col-sm-1 control-label" style="padding-left:0;">Email</label>
      <div class="col-sm-4">
        <input v-model="form.email" type="email" class="form-control" placeholder="Opcional" />
      </div>
    </div>

    <!-- Dirección -->
    <div class="form-group">
      <label class="col-sm-3 control-label">Dirección / Agencia</label>
      <div class="col-sm-9">
        <input v-model="form.address" type="text" class="form-control" placeholder="Opcional" />
      </div>
    </div>

    <!-- Notas -->
    <div class="form-group">
      <label class="col-sm-3 control-label">Notas</label>
      <div class="col-sm-9">
        <textarea v-model="form.notes" class="form-control" rows="2" placeholder="Observaciones, sectorista, N° cuenta referencia…"></textarea>
      </div>
    </div>

    <!-- Estado (Solo en edición) -->
    <div v-if="editId" class="form-group">
      <label class="col-sm-3 control-label">Estado</label>
      <div class="col-sm-9">
        <select v-model.number="form.status" class="form-control">
          <option :value="1">Disponible</option>
          <option :value="2">No disponible</option>
        </select>
      </div>
    </div>
  </form>
</template>

<script setup>
import { reactive, ref, computed } from 'vue'
import * as api from '@/api'

const props = defineProps({
  editId: { type: [Number, String], default: null }
})

const searching = ref(false)
const lookupMsg = ref('')
const lookupOk  = ref(false)

const form = reactive({
  lender_type:     'PERSONA',
  document_type:   'DNI',
  document_number: '',
  full_name:       '',
  phone:           '',
  email:           '',
  address:         '',
  notes:           '',
  status:          1,
})

const canLookupDecolecta = computed(() =>
  form.document_type === 'DNI' || form.document_type === 'RUC'
)

const docPlaceholder = computed(() => {
  if (form.document_type === 'DNI') return '8 dígitos'
  if (form.document_type === 'RUC') return '11 dígitos (ej. Banco/Empresa)'
  return 'N° de documento / código'
})

const namePlaceholder = computed(() => {
  if (form.lender_type === 'ENTIDAD_FINANCIERA') return 'Ej. BCP, BBVA, Interbank, Caja Arequipa…'
  if (form.lender_type === 'EMPRESA') return 'Razón social de la empresa'
  return 'Nombres y apellidos completos'
})

function onLenderTypeChange() {
  if (!props.editId && !form.document_number) {
    form.document_type = form.lender_type === 'PERSONA' ? 'DNI' : 'RUC'
  }
}

async function searchDocument() {
  const num = form.document_number.trim()
  if (!num || !canLookupDecolecta.value) return
  searching.value = true
  lookupMsg.value = ''
  try {
    const { data } = await api.lookupDecolecta(form.document_type, num)
    if (data?.success && data.data) {
      const info = data.data
      if (info.full_name) form.full_name = info.full_name
      if (info.address)   form.address   = info.address
      lookupOk.value  = true
      lookupMsg.value = 'Datos encontrados correctamente.'
    } else {
      lookupOk.value  = false
      lookupMsg.value = data?.messages || 'No se encontraron datos para el documento.'
    }
  } catch (e) {
    lookupOk.value  = false
    lookupMsg.value = e.response?.data?.messages || 'Error consultando el servicio Decolecta.'
  } finally {
    searching.value = false
  }
}

function reset() {
  form.lender_type     = 'PERSONA'
  form.document_type   = 'DNI'
  form.document_number = ''
  form.full_name       = ''
  form.phone           = ''
  form.email           = ''
  form.address         = ''
  form.notes           = ''
  form.status          = 1
  lookupMsg.value      = ''
  lookupOk.value       = false
}

function setData(d) {
  form.lender_type     = d.lender_type || 'PERSONA'
  form.document_type   = d.document_type || 'DNI'
  form.document_number = d.document_number || ''
  form.full_name       = d.full_name || ''
  form.phone           = d.phone || ''
  form.email           = d.email || ''
  form.address         = d.address || ''
  form.notes           = d.notes || ''
  form.status          = Number(d.status) || 1
  lookupMsg.value      = ''
}

function getFormData() {
  if (!form.document_number.trim() || !form.full_name.trim()) return null
  const fd = new URLSearchParams()
  if (props.editId) fd.append('id', String(props.editId))
  fd.append('lender_type', form.lender_type)
  fd.append('document_type', form.document_type)
  fd.append('document_number', form.document_number.trim())
  fd.append('full_name', form.full_name.trim())
  fd.append('phone', form.phone.trim())
  fd.append('email', form.email.trim())
  fd.append('address', form.address.trim())
  fd.append('notes', form.notes.trim())
  fd.append('status', String(form.status))
  return fd
}

defineExpose({ getFormData, setData, reset, searchDocument })
</script>
