<template>
  <div>
    <div class="row">
      <div class="col-sm-7">
        <div class="form-group">
          <label>Nombre del Proyecto <span class="text-danger">*</span></label>
          <input v-model="f.project_name" type="text" class="form-control" placeholder="Ej: Software ERP IESTP..." required>
        </div>
      </div>
      <div class="col-sm-5">
        <div class="form-group">
          <label>Cliente</label>
          <input v-model="f.client_name" type="text" class="form-control" placeholder="Nombre del cliente...">
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-sm-4">
        <div class="form-group">
          <label>Presupuesto S/. <span class="text-danger">*</span></label>
          <input v-model="f.budget" type="number" step="0.01" min="0" class="form-control" placeholder="0.00" required>
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
          <input v-model="f.end_date" type="date" class="form-control">
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
      <textarea v-model="f.description" class="form-control" rows="3" placeholder="Descripción del proyecto..."></textarea>
    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue'
defineProps({ editId: { type: Number, default: null } })

const f = reactive({ project_id:'', project_name:'', client_name:'', budget:'', start_date:'', end_date:'', status:'ACTIVE', description:'' })

function setData(d) {
  Object.assign(f, {
    project_id:   d.project_id || '',
    project_name: d.project_name || '',
    client_name:  d.client_name || '',
    budget:       d.budget || '',
    start_date:   d.start_date || '',
    end_date:     d.end_date || '',
    status:       d.status || 'ACTIVE',
    description:  d.description || '',
  })
}

function getFormData() {
  if (!f.project_name || !f.budget) return null
  const fd = new FormData()
  Object.entries(f).forEach(([k, v]) => v !== '' && v !== null && fd.append(k, v))
  return fd
}

defineExpose({ getFormData, setData })
</script>
