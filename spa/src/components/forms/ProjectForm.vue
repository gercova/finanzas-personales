<template>
  <div>
    <div class="row">
      <div class="col-sm-7">
        <div class="form-group">
          <label>Nombre del Proyecto <span class="text-danger">*</span></label>
          <input v-model="f.project_name" type="text" class="form-control"
                 placeholder="Ej: Software ERP IESTP..." required>
        </div>
      </div>
      <div class="col-sm-5">
        <div class="form-group">
          <label>Cliente</label>
          <input v-model="f.client_name" type="text" class="form-control"
                 placeholder="Nombre del cliente...">
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-sm-4">
        <div class="form-group">
          <label>Presupuesto S/. <span class="text-danger">*</span></label>
          <input v-model="f.budget" type="number" step="0.01" min="0.01"
                 class="form-control" placeholder="0.00" required>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="form-group">
          <label>Fecha de Inicio</label>
          <input v-model="f.start_date" type="date" class="form-control">
        </div>
      </div>
      <div class="col-sm-4">
        <div class="form-group">
          <label>Fecha de Fin</label>
          <input v-model="f.end_date" type="date" class="form-control" :min="f.start_date || undefined">
        </div>
      </div>
    </div>
    <div class="form-group">
      <label>Estado</label>
      <select v-model="f.status" class="form-control">
        <option value="ACTIVE">Activo</option>
        <option value="ON_HOLD">En Espera</option>
        <option value="COMPLETED">Completado</option>
      </select>
    </div>
    <div class="form-group">
      <label>Descripción</label>
      <textarea v-model="f.description" class="form-control" rows="3"
                placeholder="Descripción del proyecto..."></textarea>
    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue'

defineProps({ editId: { type: [Number, String], default: null } })

const f = reactive({
  project_id: '',
  project_name: '', client_name: '', budget: '',
  start_date: '', end_date: '', status: 'ACTIVE', description: ''
})

function reset() {
  f.project_id   = ''
  f.project_name = ''
  f.client_name  = ''
  f.budget       = ''
  f.start_date   = ''
  f.end_date     = ''
  f.status       = 'ACTIVE'
  f.description  = ''
}

function setData(d) {
  if (!d) return
  Object.assign(f, {
    project_id:   d.project_id   || '',
    project_name: d.project_name || '',
    client_name:  d.client_name  || '',
    budget:       d.budget       ?? '',
    start_date:   d.start_date   || '',
    end_date:     d.end_date     || '',
    status:       d.status       || 'ACTIVE',
    description:  d.description  || '',
  })
}

function getValidationError() {
  const b = parseFloat(f.budget)
  if (!f.project_name.trim() || isNaN(b) || b <= 0) {
    return 'Complete el nombre del proyecto y un presupuesto mayor a 0.'
  }
  if (f.start_date && f.end_date && f.end_date < f.start_date) {
    return 'La fecha de fin no puede ser anterior a la fecha de inicio.'
  }
  return ''
}

function getFormData() {
  if (getValidationError()) return null

  const b      = parseFloat(f.budget)
  const fd     = new FormData()
  const isEdit = !!f.project_id

  if (isEdit) {
    fd.append('edit_project_id',   f.project_id)
    fd.append('edit_project_name', f.project_name.trim())
    fd.append('edit_client_name',  f.client_name.trim())
    fd.append('edit_budget',       b.toFixed(2))
    fd.append('edit_start_date',   f.start_date || '')
    fd.append('edit_end_date',     f.end_date || '')
    fd.append('edit_status',       f.status)
    fd.append('edit_description',  f.description.trim())
  } else {
    fd.append('project_name', f.project_name.trim())
    fd.append('client_name',  f.client_name.trim())
    fd.append('budget',       b.toFixed(2))
    fd.append('start_date',   f.start_date || '')
    fd.append('end_date',     f.end_date || '')
    fd.append('status',       f.status)
    fd.append('description',  f.description.trim())
  }

  return fd
}

defineExpose({ getFormData, getValidationError, setData, reset })
</script>
