<template>
  <div>
    <div class="form-group">
      <label>Nombre de Categoría <span class="text-danger">*</span></label>
      <input
        type="text"
        class="form-control"
        placeholder="Ej: Salario, Alimentación, Freelance..."
        v-model="form.category_name"
        required
      />
    </div>

    <div class="form-group">
      <label>Tipo de Categoría <span class="text-danger">*</span></label>
      <select class="form-control" v-model="form.category_type">
        <option value="INCOME">Ingreso</option>
        <option value="EXPENSE">Gasto</option>
      </select>
    </div>

    <div class="row">
      <div class="col-xs-6">
        <div class="form-group">
          <label>Color Identificador</label>
          <input type="color" class="form-control" v-model="form.color" style="height:34px;padding:2px 4px;" />
        </div>
      </div>
      <div class="col-xs-6" v-if="editId">
        <div class="form-group">
          <label>Estado</label>
          <select class="form-control" v-model="form.status">
            <option value="1">Activa</option>
            <option value="0">Inactiva</option>
          </select>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue'

const props = defineProps({
  editId: { type: Number, default: null }
})

const form = reactive({
  category_name: '',
  category_type: 'EXPENSE',
  color: '#5bc0de',
  status: '1'
})

function setData(data) {
  if (!data) return
  form.category_name = data.category_name || ''
  form.category_type = data.category_type || 'EXPENSE'
  form.color         = data.color || '#5bc0de'
  form.status        = String(data.status ?? '1')
}

function getFormData() {
  if (!form.category_name.trim()) return null
  const fd = new FormData()
  if (props.editId) fd.append('category_id', props.editId)
  fd.append('category_name', form.category_name.trim())
  fd.append('category_type', form.category_type)
  fd.append('color', form.color)
  if (props.editId) fd.append('status', form.status)
  return fd
}

defineExpose({ setData, getFormData })
</script>
