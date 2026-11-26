<template>
  <div>
    <div class="row">
      <div class="col-sm-4">
        <div class="form-group">
          <label>Tipo de Documento <span class="text-danger">*</span></label>
          <select v-model="f.document_type" class="form-control" @change="onDocTypeChange">
            <option value="DNI">DNI (8 dígitos)</option>
            <option value="RUC">RUC (11 dígitos)</option>
            <option value="CE">Carnet de Extranjería</option>
            <option value="PASAPORTE">Pasaporte</option>
            <option value="OTRO">Otro Documento</option>
          </select>
        </div>
      </div>
      <div class="col-sm-8">
        <div class="form-group">
          <label>Número de Documento <span class="text-danger">*</span></label>
          <div class="input-group">
            <input
              v-model="f.document_number"
              type="text"
              class="form-control"
              :placeholder="docPlaceholder"
              :maxlength="docMaxLength"
              @keydown.enter.prevent="searchDocument"
              required
            >
            <span class="input-group-btn">
              <button
                class="btn btn-primary"
                type="button"
                :disabled="searching || !canSearch"
                @click="searchDocument"
                title="Consultar en RENIEC / SUNAT mediante Decolecta API"
              >
                <i v-if="searching" class="glyphicon glyphicon-refresh" style="display:inline-block;animation:spin 1s linear infinite;"></i>
                <i v-else class="glyphicon glyphicon-search"></i>
                <span class="hidden-xs"> {{ searching ? 'Consultando…' : 'Buscar' }}</span>
              </button>
            </span>
          </div>
          <small class="text-muted" v-if="f.document_type === 'DNI' || f.document_type === 'RUC'">
            Consulta automática en RENIEC / SUNAT con Decolecta API.
          </small>
        </div>
      </div>
    </div>

    <!-- Feedback message from Decolecta API -->
    <div v-if="apiSuccessMessage" class="alert alert-success" style="padding:6px 12px;font-size:12.5px;margin-bottom:12px;">
      <i class="glyphicon glyphicon-ok-sign"></i> {{ apiSuccessMessage }}
    </div>
    <div v-if="apiErrorMessage" class="alert alert-warning" style="padding:6px 12px;font-size:12.5px;margin-bottom:12px;">
      <i class="glyphicon glyphicon-exclamation-sign"></i> {{ apiErrorMessage }}
    </div>

    <div class="row">
      <div class="col-sm-8">
        <div class="form-group">
          <label>Nombre Completo / Razón Social <span class="text-danger">*</span></label>
          <input
            v-model="f.full_name"
            type="text"
            class="form-control"
            placeholder="Ej: Juan Pérez Quispe o Empresa SAC"
            required
          >
        </div>
      </div>
      <div class="col-sm-4">
        <div class="form-group">
          <label>Estado</label>
          <select v-model="f.status" class="form-control">
            <option :value="1">Disponible</option>
            <option :value="2">No disponible</option>
          </select>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-sm-6">
        <div class="form-group">
          <label>Teléfono / Celular</label>
          <input
            v-model="f.phone"
            type="text"
            class="form-control"
            placeholder="Ej: 987654321"
          >
        </div>
      </div>
      <div class="col-sm-6">
        <div class="form-group">
          <label>Correo Electrónico</label>
          <input
            v-model="f.email"
            type="email"
            class="form-control"
            placeholder="correo@ejemplo.com"
          >
        </div>
      </div>
    </div>

    <div class="form-group">
      <label>Dirección</label>
      <input
        v-model="f.address"
        type="text"
        class="form-control"
        placeholder="Dirección o domicilio"
      >
    </div>

    <div class="form-group">
      <label>Notas / Observaciones</label>
      <textarea
        v-model="f.notes"
        class="form-control"
        rows="2"
        placeholder="Información adicional sobre el prestatario..."
      ></textarea>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, computed } from 'vue'
import * as api from '@/api'

const props = defineProps({
  editId: { type: Number, default: null }
})

const searching = ref(false)
const apiSuccessMessage = ref('')
const apiErrorMessage = ref('')

const f = reactive({
  id: '',
  document_type: 'DNI',
  document_number: '',
  full_name: '',
  phone: '',
  email: '',
  address: '',
  notes: '',
  status: 1
})

const docPlaceholder = computed(() => {
  if (f.document_type === 'DNI') return '8 dígitos'
  if (f.document_type === 'RUC') return '11 dígitos'
  return 'Número de documento'
})

const docMaxLength = computed(() => {
  if (f.document_type === 'DNI') return 8
  if (f.document_type === 'RUC') return 11
  return 20
})

const canSearch = computed(() => {
  const num = f.document_number.trim()
  if (f.document_type === 'DNI') return num.length === 8
  if (f.document_type === 'RUC') return num.length === 11
  return false
})

function onDocTypeChange() {
  apiSuccessMessage.value = ''
  apiErrorMessage.value = ''
}

async function searchDocument() {
  const type = f.document_type
  const number = f.document_number.trim()

  if (type !== 'DNI' && type !== 'RUC') {
    apiErrorMessage.value = 'La consulta automática solo está disponible para DNI y RUC.'
    return
  }

  if (type === 'DNI' && number.length !== 8) {
    apiErrorMessage.value = 'El DNI debe tener 8 dígitos numéricos.'
    return
  }
  if (type === 'RUC' && number.length !== 11) {
    apiErrorMessage.value = 'El RUC debe tener 11 dígitos numéricos.'
    return
  }

  searching.value = true
  apiSuccessMessage.value = ''
  apiErrorMessage.value = ''

  try {
    const { data } = await api.lookupDecolecta(type, number)
    if (data.success && data.data) {
      if (data.data.full_name) {
        f.full_name = data.data.full_name
      }
      if (data.data.address && !f.address) {
        f.address = data.data.address
      }
      apiSuccessMessage.value = `Datos encontrados: ${data.data.full_name}`
    } else {
      apiErrorMessage.value = data.messages || 'No se encontró información para el documento ingresado.'
    }
  } catch (err) {
    apiErrorMessage.value = 'Error al consultar el servicio de Decolecta.'
  } finally {
    searching.value = false
  }
}

function setData(d) {
  if (!d) return
  apiSuccessMessage.value = ''
  apiErrorMessage.value = ''
  Object.assign(f, {
    id:              d.id || d.borrower_id || '',
    document_type:   d.document_type || 'DNI',
    document_number: d.document_number || '',
    full_name:       d.full_name || '',
    phone:           d.phone || '',
    email:           d.email || '',
    address:         d.address || '',
    notes:           d.notes || '',
    status:          d.status !== undefined ? Number(d.status) : 1
  })
}

function reset() {
  apiSuccessMessage.value = ''
  apiErrorMessage.value = ''
  Object.assign(f, {
    id: '',
    document_type: 'DNI',
    document_number: '',
    full_name: '',
    phone: '',
    email: '',
    address: '',
    notes: '',
    status: 1
  })
}

function getFormData() {
  if (!f.document_number.trim() || !f.full_name.trim()) {
    return null
  }
  const fd = new FormData()
  if (f.id) fd.append('id', f.id)
  fd.append('document_type',   f.document_type)
  fd.append('document_number', f.document_number.trim())
  fd.append('full_name',       f.full_name.trim())
  fd.append('phone',           f.phone.trim())
  fd.append('email',           f.email.trim())
  fd.append('address',         f.address.trim())
  fd.append('notes',           f.notes.trim())
  fd.append('status',          f.status)
  return fd
}

defineExpose({ getFormData, setData, reset, searchDocument })
</script>

<style scoped>
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
