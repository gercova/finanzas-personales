<!--
  Bulk import modal — mirrors the PHP importModal.php but fully in Vue.
-->
<template>
  <AppModal v-model="show" :title="'Importar ' + moduleLabel" icon="glyphicon-upload"
            :header-color="moduleColor" size="lg" @closed="reset">

    <template #body>
      <!-- Steps -->
      <div style="display:flex;gap:0;margin-bottom:20px;border:1px solid #ddd;border-radius:4px;overflow:hidden;">
        <div :style="stepStyle(1)" style="flex:1;padding:10px;text-align:center;font-size:13px;font-weight:600;">
          <i class="glyphicon glyphicon-cloud-upload"></i> 1. Subir archivo
        </div>
        <div :style="stepStyle(2)" style="flex:1;padding:10px;text-align:center;font-size:13px;font-weight:600;">
          <i class="glyphicon glyphicon-list-alt"></i> 2. Resultado
        </div>
      </div>

      <!-- Step 1 -->
      <div v-if="step === 1">
        <div class="alert alert-info" style="font-size:13px;">
          <strong><i class="glyphicon glyphicon-info-sign"></i> Antes de importar:</strong>
          <ol style="margin:8px 0 0 0;padding-left:18px;">
            <li>Descargue la plantilla y llénela con sus datos.</li>
            <li>Acepta <strong>.xlsx, .xls o .csv</strong> (máx. 5 MB, máx. 500 filas).</li>
            <li>No modifique los encabezados.</li>
            <li>Los campos marcados <strong>*</strong> son obligatorios.</li>
            <li>Cuenta, Categoría y Proyecto deben coincidir exactamente con los ya registrados.</li>
          </ol>
        </div>

        <div class="text-center" style="margin-bottom:16px;">
          <a :href="templateHref" class="btn btn-default btn-sm" target="_blank">
            <i class="glyphicon glyphicon-download-alt"></i> Descargar Plantilla (.xlsx)
          </a>
        </div>

        <!-- Drop zone -->
        <div class="form-group">
          <label><i class="glyphicon glyphicon-file"></i> Seleccionar archivo Excel o CSV</label>
          <div class="import-dropzone"
              :class="{ 'dz-active': dragging, 'dz-error': !!fileError, 'dz-ok': fileOk }"
              @click="triggerFileInput"
              @dragover.prevent="dragging = true"
              @dragleave="dragging = false"
              @drop.prevent="onDrop"
            >
            <i class="glyphicon glyphicon-cloud-upload"
               style="font-size:36px;color:#aaa;display:block;margin-bottom:8px;"></i>
            <span v-if="!selectedFile" style="color:#777;font-size:14px;">
              Arrastre el archivo aquí o <strong>haga clic para seleccionar</strong>
            </span>
            <span v-else style="color:#333;font-size:14px;font-weight:600;">
              {{ selectedFile.name }}
            </span>
            <!-- Hidden input — bound via Composition-API ref, not $refs -->
            <input
              ref="fileInputRef"
              type="file"
              accept=".xlsx,.xls,.csv"
              style="position:absolute;width:1px;height:1px;opacity:0;overflow:hidden;"
              @change="onFileChange"
            />
          </div>

          <div v-if="fileError" class="text-danger" style="margin-top:4px;font-size:12px;">
            {{ fileError }}
          </div>
          <div v-if="selectedFile && !fileError" class="text-success" style="margin-top:4px;font-size:12px;">
            <i class="glyphicon glyphicon-ok-sign"></i>
            {{ selectedFile.name }} ({{ fileSizeLabel }})
          </div>
        </div>

        <!-- Progress -->
        <div v-if="uploading" style="margin-top:12px;">
          <div class="progress">
            <div class="progress-bar progress-bar-striped active"
                 role="progressbar" style="width:100%;min-width:40px;">
              Procesando…
            </div>
          </div>
        </div>
      </div>

      <!-- Step 2: results -->
      <div v-else>
        <div :class="['alert', result.success ? 'alert-success' : 'alert-danger']"
             style="font-size:14px;">
          <strong>
            <i :class="'glyphicon ' + (result.success ? 'glyphicon-ok-sign' : 'glyphicon-remove-sign')"></i>
            {{ result.success ? 'Importación completada' : 'Importación fallida' }}
          </strong><br>
          {{ result.messages }}
        </div>

        <div v-if="result.success && result.inserted > 0"
             class="well well-sm text-center" style="font-size:15px;">
          <span class="text-success" style="font-weight:700;font-size:22px;">{{ result.inserted }}</span>
          registro(s) importado(s) correctamente.
        </div>

        <div v-if="result.errors && result.errors.length">
          <h5 style="font-weight:600;margin-top:16px;">
            <i class="glyphicon glyphicon-warning-sign text-warning"></i>
            Filas con errores (no importadas)
          </h5>
          <div class="table-responsive" style="max-height:260px;overflow-y:auto;">
            <table class="table table-condensed table-bordered" style="font-size:12px;">
              <thead style="background:#f5f5f5;">
                <tr>
                  <SortableTh label="Fila" sort-key="row" :active-key="errSortKey" :sort-dir="errSortDir" :th-style="{ width: '75px' }" @sort="toggleErrSort" />
                  <SortableTh label="Campo" sort-key="field" :active-key="errSortKey" :sort-dir="errSortDir" :th-style="{ width: '130px' }" @sort="toggleErrSort" />
                  <SortableTh label="Error" sort-key="message" :active-key="errSortKey" :sort-dir="errSortDir" @sort="toggleErrSort" />
                </tr>
              </thead>
              <tbody>
                <tr v-for="(e, i) in sortedErrors" :key="i">
                  <td class="text-center"><strong>{{ e.row }}</strong></td>
                  <td>{{ e.field }}</td>
                  <td>{{ e.message }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </template>

    <template #footer>
      <template v-if="step === 1">
        <button class="btn btn-default" @click="show = false">Cancelar</button>
        <button class="btn btn-primary"
                :disabled="!selectedFile || !!fileError || uploading"
                @click="submit">
          <i class="glyphicon glyphicon-upload"></i> Importar
        </button>
      </template>
      <template v-else>
        <button class="btn btn-default" @click="reset">
          <i class="glyphicon glyphicon-repeat"></i> Importar otro archivo
        </button>
        <button class="btn btn-primary" @click="show = false">
          <i class="glyphicon glyphicon-ok"></i> Cerrar
        </button>
      </template>
    </template>
  </AppModal>
</template>

<script setup>
import { ref, computed } from 'vue'
import AppModal from './AppModal.vue'
import SortableTh from './SortableTh.vue'
import { useTableSort } from '@/utils/sort'
import * as api from '@/api'

const props = defineProps({
  module: { type: String, required: true }
})
const emit = defineEmits(['imported'])

const {
  sortKey: errSortKey,
  sortDir: errSortDir,
  toggleSort: toggleErrSort,
  sortRows: sortErrRows
} = useTableSort('row', 'asc')

// Composition-API ref for the hidden file input (replaces Options-API $refs)
const fileInputRef = ref(null)

const show         = ref(false)
const step         = ref(1)
const dragging     = ref(false)
const selectedFile = ref(null)
const fileError    = ref('')
const uploading    = ref(false)
const result       = ref({ success: false, messages: '', inserted: 0, errors: [] })
const sortedErrors = computed(() => sortErrRows(result.value?.errors || []))

const MAX_MB = 5

const moduleLabels = {
  income:  'Ingresos',
  expense: 'Gastos',
  loan:    'Préstamos',
  debt:    'Préstamos Recibidos',
  project: 'Proyectos',
  asset:   'Activos Valiosos'
}
const moduleColors = {
  income:  '#27AE60',
  expense: '#C0392B',
  loan:    '#E74C3C',
  debt:    '#C0392B',
  project: '#2980B9',
  asset:   '#8E44AD'
}

const moduleLabel  = computed(() => moduleLabels[props.module] || 'Registros')
const moduleColor  = computed(() => moduleColors[props.module] || '#34495E')
const templateHref = computed(() => api.templateUrl(props.module))

const fileSizeLabel = computed(() => {
  if (!selectedFile.value) return ''
  const s = selectedFile.value.size
  return s < 1024 * 1024
    ? (s / 1024).toFixed(1) + ' KB'
    : (s / (1024 * 1024)).toFixed(2) + ' MB'
})
const fileOk = computed(() => !!selectedFile.value && !fileError.value)

function stepStyle(n) {
  const active = step.value === n
  return { background: active ? '#34495e' : '#ecf0f1', color: active ? '#fff' : '#999' }
}

function triggerFileInput() {
  fileInputRef.value?.click()
}

function validateFile(file) {
  if (!file) return
  const ext = file.name.split('.').pop().toLowerCase()
  if (!['xlsx', 'xls', 'csv'].includes(ext)) {
    fileError.value = 'Formato no permitido. Use .xlsx, .xls o .csv.'
    return
  }
  if (file.size > MAX_MB * 1024 * 1024) {
    fileError.value = `El archivo supera el máximo de ${MAX_MB} MB.`
    return
  }
  fileError.value = ''
  selectedFile.value = file
}

function onFileChange(e) { validateFile(e.target.files[0]) }
function onDrop(e)        { dragging.value = false; validateFile(e.dataTransfer.files[0]) }

async function submit() {
  if (!selectedFile.value || fileError.value) return
  uploading.value = true
  try {
    const fd = new FormData()
    fd.append('module', props.module)
    fd.append('file', selectedFile.value)
    const { data } = await api.bulkImport(fd)
    result.value = data
    step.value = 2
    if (data.success && data.inserted > 0) emit('imported')
  } catch {
    result.value = {
      success: false,
      messages: 'Error de comunicación con el servidor.',
      inserted: 0,
      errors: []
    }
    step.value = 2
  } finally {
    uploading.value = false
  }
}

function reset() {
  step.value     = 1
  selectedFile.value = null
  fileError.value    = ''
  uploading.value    = false
  result.value       = { success: false, messages: '', inserted: 0, errors: [] }
  // Use the Composition-API ref — $refs does not exist in <script setup>
  if (fileInputRef.value) fileInputRef.value.value = ''
}

function open() { reset(); show.value = true }

defineExpose({ open })
</script>

<style scoped>
.import-dropzone {
  position: relative;
  border: 2px dashed #adb5bd;
  border-radius: 6px;
  padding: 30px 20px;
  text-align: center;
  cursor: pointer;
  background: #fafafa;
  transition: border-color .2s, background .2s;
}
.import-dropzone.dz-active { border-color: #3498db; background: #ebf5fb; }
.import-dropzone.dz-ok     { border-color: #27ae60; background: #f0fff4; }
.import-dropzone.dz-error  { border-color: #e74c3c; background: #fdf2f2; }
</style>
