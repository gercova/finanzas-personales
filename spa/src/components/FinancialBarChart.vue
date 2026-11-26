<template>
  <div class="panel panel-default" style="box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 25px;">
    <!-- Panel Header: Title + Year Selector (Matching Reference Image) -->
    <div class="panel-heading" style="background:#fff; border-bottom:1px solid #eee; padding:12px 15px;">
      <div class="row" style="align-items:center; display:flex; flex-wrap:wrap;">
        <div class="col-xs-12 col-sm-8">
          <h4 style="margin:0; font-size:16px; font-weight:700; color:#333;">
            <i class="glyphicon glyphicon-stats text-primary" style="margin-right:6px;"></i>
            Reporte Financiero Mensual
          </h4>
          <small class="text-muted">
            Flujo consolidado de ingresos, gastos, préstamos, prést. recibidos y proyectos por mes
          </small>
        </div>
        <div class="col-xs-12 col-sm-4 text-right" style="margin-top:6px; margin-sm-top:0;">
          <div style="display:inline-flex; align-items:center; gap:8px;">
            <label style="margin:0; font-size:12px; font-weight:600; color:#666;">Año:</label>
            <select
              v-model="selectedYear"
              class="form-control input-sm"
              style="width:95px; font-weight:700; border-radius:4px;"
              @change="fetchChartData"
            >
              <option v-for="y in availableYears" :key="y" :value="y">{{ y }}</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <div class="panel-body" style="padding:15px;">
      <!-- Filter Bar: Month & Categories (Requested by User) -->
      <div style="background:#f9f9f9; border:1px solid #e5e5e5; border-radius:4px; padding:10px 14px; margin-bottom:15px;">
        <div class="row">
          <!-- Month Filter -->
          <div class="col-xs-12 col-md-4" style="margin-bottom:8px; margin-md-bottom:0;">
            <div style="display:flex; align-items:center; gap:8px;">
              <span style="font-size:12.5px; font-weight:600; color:#555; white-space:nowrap;">
                <i class="glyphicon glyphicon-calendar"></i> Mes:
              </span>
              <select
                v-model="filterMonth"
                class="form-control input-sm"
                @change="onMonthFilterChange"
              >
                <option value="all">Todos los meses (Ene - Dic)</option>
                <option v-for="(mName, mIdx) in monthFullNames" :key="mIdx" :value="mIdx + 1">
                  {{ mName }}
                </option>
              </select>
            </div>
          </div>

          <!-- Category Filter Checkboxes / Toggles -->
          <div class="col-xs-12 col-md-8">
            <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap; justify-content:flex-end;">
              <span style="font-size:12.5px; font-weight:600; color:#555;">
                <i class="glyphicon glyphicon-filter"></i> Categorías:
              </span>
              <label class="checkbox-inline" style="margin-left:0; font-size:12px; cursor:pointer;">
                <input type="checkbox" v-model="visibleCategories.income" @change="updateSeriesVisibility">
                <span class="badge" style="background:#3498db; padding:3px 7px;">Ingresos</span>
              </label>
              <label class="checkbox-inline" style="margin-left:0; font-size:12px; cursor:pointer;">
                <input type="checkbox" v-model="visibleCategories.expense" @change="updateSeriesVisibility">
                <span class="badge" style="background:#5b2c6f; padding:3px 7px;">Gastos</span>
              </label>
              <label class="checkbox-inline" style="margin-left:0; font-size:12px; cursor:pointer;">
                <input type="checkbox" v-model="visibleCategories.loan" @change="updateSeriesVisibility">
                <span class="badge" style="background:#2ecc71; padding:3px 7px;">Préstamos</span>
              </label>
              <label class="checkbox-inline" style="margin-left:0; font-size:12px; cursor:pointer;">
                <input type="checkbox" v-model="visibleCategories.debt" @change="updateSeriesVisibility">
                <span class="badge" style="background:#e74c3c; padding:3px 7px;">Prést. Recibidos</span>
              </label>
              <label class="checkbox-inline" style="margin-left:0; font-size:12px; cursor:pointer;">
                <input type="checkbox" v-model="visibleCategories.project" @change="updateSeriesVisibility">
                <span class="badge" style="background:#e67e22; padding:3px 7px;">Proyectos</span>
              </label>
            </div>
          </div>
        </div>
      </div>

      <!-- Hint to user for click-to-filter -->
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; font-size:11.5px; color:#888;">
        <span>
          <i class="glyphicon glyphicon-info-sign text-info"></i>
          Haga clic en cualquiera de las barras para ver el <strong>detalle granular</strong> de ese mes y actividad.
        </span>
        <span v-if="loading" class="text-primary">
          <i class="glyphicon glyphicon-refresh" style="animation:spin 1s linear infinite;"></i> Actualizando gráfico…
        </span>
      </div>

      <!-- Highcharts Container -->
      <div ref="chartContainer" style="width:100%; min-height:380px;"></div>

      <!-- ============================================================== -->
      <!-- GRANULAR DRILLDOWN PANEL (Applies on clicking any bar)         -->
      <!-- ============================================================== -->
      <div v-if="drilldownActive && drilldownData" ref="drilldownSection" style="margin-top:25px; border-top:2px dashed #ddd; padding-top:20px;">
        <!-- Filter Banner -->
        <div style="background:#edf2f7; border-left:4px solid #3182ce; padding:12px 16px; border-radius:4px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:15px;">
          <div>
            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
              <span style="font-size:14px; font-weight:700; color:#2d3748;">
                <i class="glyphicon glyphicon-zoom-in text-primary"></i> Filtro Granular Activo:
              </span>
              <span class="label" :style="{ background: categoryColor(drilldownData.category) }" style="font-size:12px; padding:4px 8px;">
                {{ drilldownData.category_label }}
              </span>
              <span class="label label-primary" style="font-size:12px; padding:4px 8px;">
                {{ drilldownData.month_name }} {{ drilldownData.year }}
              </span>
              <span class="text-muted" style="font-size:13px; font-weight:600;">
                (Total: <strong class="text-success">S/ {{ fmt(drilldownData.total_amount) }}</strong> en {{ drilldownData.total_items }} registro{{ drilldownData.total_items !== 1 ? 's' : '' }})
              </span>
            </div>
          </div>
          <button class="btn btn-default btn-sm" @click="clearDrilldown" style="font-weight:600;">
            <i class="glyphicon glyphicon-remove text-danger"></i> Limpiar filtro granular
          </button>
        </div>

        <!-- Breakdown by Subcategory / Activity Type -->
        <div class="row" style="margin-bottom:15px;">
          <div class="col-xs-12">
            <div class="panel panel-default" style="margin-bottom:15px;">
              <div class="panel-heading" style="background:#fafafa; font-size:13px; font-weight:600;">
                <i class="glyphicon glyphicon-list text-primary"></i> Desglose por Tipo de Actividad en {{ drilldownData.month_name }} {{ drilldownData.year }}
              </div>
              <div class="panel-body" style="padding:12px 15px;">
                <div v-if="!drilldownData.breakdown || drilldownData.breakdown.length === 0" class="text-center text-muted" style="padding:15px;">
                  No hay transacciones registradas para este periodo y categoría.
                </div>
                <div v-else class="row">
                  <div
                    v-for="(b, bIdx) in drilldownData.breakdown"
                    :key="bIdx"
                    class="col-xs-12 col-sm-6 col-md-4"
                    style="margin-bottom:10px;"
                  >
                    <div style="background:#fff; border:1px solid #eee; border-radius:4px; padding:8px 12px; box-shadow:0 1px 2px rgba(0,0,0,0.03);">
                      <div style="display:flex; justify-content:space-between; font-size:12.5px; margin-bottom:4px;">
                        <span style="font-weight:600; color:#333; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" :title="b.name">
                          {{ b.name }}
                        </span>
                        <strong class="text-primary">S/ {{ fmt(b.total) }}</strong>
                      </div>
                      <div class="progress" style="height:6px; margin-bottom:3px;">
                        <div
                          class="progress-bar progress-bar-info"
                          :style="{ width: b.percentage + '%' }"
                        ></div>
                      </div>
                      <div style="display:flex; justify-content:space-between; font-size:10.5px; color:#888;">
                        <span>{{ b.count }} registro{{ b.count !== 1 ? 's' : '' }}</span>
                        <span>{{ b.percentage }}%</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Detailed Transaction Table -->
        <div class="panel panel-default">
          <div class="panel-heading" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
            <div style="font-weight:600; font-size:13px;">
              <i class="glyphicon glyphicon-th-list text-primary"></i>
              Detalle de Movimientos ({{ drilldownFilteredItems.length }})
            </div>
            <div>
              <input
                v-model="drilldownSearch"
                type="search"
                class="form-control input-sm"
                placeholder="Buscar en detalle…"
                style="width:200px;"
              >
            </div>
          </div>
          <div class="panel-body" style="padding:0;">
            <div class="table-responsive">
              <table class="table table-hover table-striped" style="margin-bottom:0; font-size:12.5px;">
                <thead>
                  <tr style="background:#f5f5f5;">
                    <th style="width:105px;">Fecha</th>
                    <th>Concepto / Descripción</th>
                    <th>Categoría / Origen</th>
                    <th>Cuenta / Cliente</th>
                    <th class="text-right" style="width:130px;">Monto</th>
                    <th style="width:110px;">Estado</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="drilldownPagedItems.length === 0">
                    <td colspan="6" class="text-center text-muted" style="padding:25px;">
                      No se encontraron movimientos coincidentes.
                    </td>
                  </tr>
                  <tr v-for="it in drilldownPagedItems" :key="it.id">
                    <td>
                      <span class="label label-default" style="font-size:11px;">
                        {{ formatDate(it.date) }}
                      </span>
                    </td>
                    <td>
                      <strong style="color:#333;">{{ it.concept }}</strong>
                      <div v-if="it.notes" style="font-size:11px; color:#888;">
                        {{ it.notes }}
                      </div>
                    </td>
                    <td>
                      <span class="label label-info" style="font-size:11px;">
                        {{ it.category_name || '-' }}
                      </span>
                      <div v-if="it.document_info" style="font-size:10px; color:#888; margin-top:2px;">
                        {{ it.document_info }}
                      </div>
                    </td>
                    <td>
                      <i class="glyphicon glyphicon-credit-card text-muted" style="font-size:11px;"></i>
                      {{ it.account_name || '-' }}
                    </td>
                    <td class="text-right" style="font-weight:700; font-size:13px;" :style="{ color: categoryColor(drilldownData.category) }">
                      S/ {{ fmt(it.amount) }}
                    </td>
                    <td>
                      <span
                        class="label"
                        :class="statusBadgeClass(it.status)"
                        style="font-size:10px; padding:2px 6px;"
                      >
                        {{ it.status || 'REGISTRADO' }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Drilldown Pagination -->
            <div v-if="drilldownTotalPages > 1" style="padding:10px 15px; display:flex; justify-content:space-between; align-items:center; border-top:1px solid #eee;">
              <small class="text-muted">
                Página {{ drilldownPage }} de {{ drilldownTotalPages }}
              </small>
              <ul class="pagination pagination-sm" style="margin:0;">
                <li :class="{ disabled: drilldownPage === 1 }">
                  <a href="#" @click.prevent="drilldownPage > 1 && (drilldownPage--)">&laquo;</a>
                </li>
                <li v-for="p in drilldownTotalPages" :key="p" :class="{ active: p === drilldownPage }">
                  <a href="#" @click.prevent="drilldownPage = p">{{ p }}</a>
                </li>
                <li :class="{ disabled: drilldownPage === drilldownTotalPages }">
                  <a href="#" @click.prevent="drilldownPage < drilldownTotalPages && (drilldownPage++)">&raquo;</a>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, nextTick } from 'vue'
import Highcharts from 'highcharts'
import exportingInit from 'highcharts/modules/exporting.js'
import * as api from '@/api'

// Initialize Highcharts exporting plugin safely
if (typeof exportingInit === 'function') {
  try {
    exportingInit(Highcharts)
  } catch (e) {
    console.warn('Highcharts exporting initialization notice:', e)
  }
}

const chartContainer  = ref(null)
const drilldownSection = ref(null)
let chartInstance     = null

const loading         = ref(false)
const selectedYear    = ref(new Date().getFullYear())
const availableYears  = ref([new Date().getFullYear()])
const filterMonth     = ref('all') // 'all' or 1..12

const visibleCategories = reactive({
  income:  true,
  expense: true,
  loan:    true,
  debt:    true,
  project: true
})

// Stored raw series from server
const rawSeriesData = ref([])
const rawCategories = ref([])

// Granular Drilldown state
const drilldownActive = ref(false)
const drilldownData   = ref(null)
const drilldownSearch = ref('')
const drilldownPage   = ref(1)
const drilldownPageSize = 10

const monthFullNames = [
  'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
  'Julio', 'Agosto', 'Setiembre', 'Octubre', 'Noviembre', 'Diciembre'
]

function categoryColor(cat) {
  switch (cat) {
    case 'income':  return '#3498db'
    case 'expense': return '#5b2c6f'
    case 'loan':    return '#2ecc71'
    case 'debt':    return '#e74c3c'
    case 'project': return '#e67e22'
    default:        return '#333333'
  }
}

function statusBadgeClass(status) {
  if (!status) return 'label-default'
  const s = String(status).toUpperCase()
  if (s === 'PAGADO' || s === 'CONFIRMED' || s === 'COMPLETED') return 'label-success'
  if (s === 'PARCIAL' || s === 'ACTIVE') return 'label-warning'
  if (s === 'PENDIENTE' || s === 'PENDING') return 'label-danger'
  return 'label-default'
}

function formatDate(dStr) {
  if (!dStr) return '-'
  const parts = dStr.split('-')
  if (parts.length === 3) return `${parts[2]}/${parts[1]}/${parts[0]}`
  return dStr
}

function fmt(n) {
  const num = parseFloat(n) || 0
  return num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')
}

// Filtered and paginated drilldown items
const drilldownFilteredItems = computed(() => {
  if (!drilldownData.value?.items) return []
  const q = drilldownSearch.value.trim().toLowerCase()
  if (!q) return drilldownData.value.items
  return drilldownData.value.items.filter(it =>
    String(it.concept || '').toLowerCase().includes(q) ||
    String(it.category_name || '').toLowerCase().includes(q) ||
    String(it.account_name || '').toLowerCase().includes(q) ||
    String(it.status || '').toLowerCase().includes(q)
  )
})

const drilldownTotalPages = computed(() =>
  Math.max(1, Math.ceil(drilldownFilteredItems.value.length / drilldownPageSize))
)

const drilldownPagedItems = computed(() => {
  const start = (drilldownPage.value - 1) * drilldownPageSize
  return drilldownFilteredItems.value.slice(start, start + drilldownPageSize)
})

// ── Chart Rendering & Options ─────────────────────────────────────────
function renderChart() {
  if (!chartContainer.value) return

  // Filter series based on user toggles
  const activeSeries = rawSeriesData.value
    .filter(s => visibleCategories[s.key])
    .map(s => {
      // If a specific month is selected in the month filter, focus on it
      if (filterMonth.value !== 'all') {
        const mIdx = parseInt(filterMonth.value) - 1
        const singleMonthData = new Array(12).fill(0)
        singleMonthData[mIdx] = s.data[mIdx] || 0
        return {
          id:    s.id,
          key:   s.key,
          name:  s.name,
          color: s.color,
          data:  singleMonthData
        }
      }
      return {
        id:    s.id,
        key:   s.key,
        name:  s.name,
        color: s.color,
        data:  s.data
      }
    })

  const year = selectedYear.value

  const options = {
    chart: {
      type: 'column',
      backgroundColor: 'transparent',
      style: {
        fontFamily: '"Helvetica Neue", Helvetica, Arial, sans-serif'
      }
    },
    title: {
      text: `Producción de Ingresos, Gastos, Préstamos, Deudas y Proyectos en ${year}`,
      align: 'center',
      style: {
        fontSize: '17px',
        fontWeight: '700',
        color: '#2c3e50'
      }
    },
    subtitle: {
      text: 'Fuente: Sistema myFin - Finanzas Personales',
      align: 'center',
      style: {
        fontSize: '12px',
        color: '#7f8c8d'
      }
    },
    xAxis: {
      categories: rawCategories.value.length ? rawCategories.value : ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'],
      crosshair: true,
      labels: {
        style: {
          fontSize: '12px',
          fontWeight: '600',
          color: '#555'
        }
      }
    },
    yAxis: {
      min: 0,
      title: {
        text: 'Monto (S/)',
        style: {
          color: '#666',
          fontSize: '12px',
          fontWeight: '600'
        }
      },
      labels: {
        formatter: function () {
          return 'S/ ' + (this.value >= 1000 ? (this.value / 1000).toFixed(0) + 'k' : this.value)
        }
      },
      gridLineColor: '#f0f0f0'
    },
    legend: {
      align: 'center',
      verticalAlign: 'bottom',
      layout: 'horizontal',
      itemStyle: {
        fontSize: '12.5px',
        fontWeight: '600',
        color: '#444'
      },
      itemHoverStyle: {
        color: '#000'
      }
    },
    tooltip: {
      shared: true,
      useHTML: true,
      headerFormat: '<div style="font-size:12px;font-weight:bold;margin-bottom:4px;border-bottom:1px solid #ddd;padding-bottom:2px;">{point.key} ' + year + '</div><table>',
      pointFormat: '<tr><td style="color:{series.color};padding:2px 6px 2px 0;">● {series.name}: </td>' +
                   '<td style="text-align:right;font-weight:bold;padding:2px 0;">S/ {point.y:,.2f}</td></tr>',
      footerFormat: '</table><div style="font-size:10px;color:#888;margin-top:4px;">(Haga clic para ver detalle granular)</div>',
      backgroundColor: '#ffffff',
      borderColor: '#cccccc',
      borderRadius: 6,
      shadow: true
    },
    plotOptions: {
      column: {
        pointPadding: 0.15,
        borderWidth: 0,
        borderRadius: 3,
        cursor: 'pointer',
        point: {
          events: {
            // Click on any bar applies granular filter
            click: function () {
              const monthNum = this.index + 1
              const catKey = this.series.userOptions.key
              onBarClick(monthNum, catKey)
            }
          }
        }
      }
    },
    series: activeSeries,
    credits: {
      enabled: true,
      text: 'Highcharts.com',
      href: 'https://www.highcharts.com'
    },
    exporting: {
      enabled: true,
      buttons: {
        contextButton: {
          menuItems: ['viewFullscreen', 'separator', 'downloadPNG', 'downloadJPEG', 'downloadPDF', 'downloadSVG']
        }
      }
    }
  }

  if (chartInstance) {
    chartInstance.destroy()
  }
  chartInstance = Highcharts.chart(chartContainer.value, options)
}

function updateSeriesVisibility() {
  renderChart()
}

function onMonthFilterChange() {
  renderChart()
  if (filterMonth.value !== 'all') {
    // If a month is selected, also query granular data for currently active or default category
    const activeCat = drilldownData.value?.category || 'expense'
    fetchDrilldown(parseInt(filterMonth.value), activeCat)
  } else if (!drilldownActive.value) {
    drilldownData.value = null
  }
}

// ── Bar Click: Apply Granular Filter ──────────────────────────────────
async function onBarClick(monthNum, categoryKey) {
  drilldownActive.value = true
  drilldownSearch.value = ''
  drilldownPage.value   = 1

  await fetchDrilldown(monthNum, categoryKey)

  // Smooth scroll to drilldown details section
  await nextTick()
  if (drilldownSection.value) {
    drilldownSection.value.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
}

async function fetchDrilldown(monthNum, categoryKey) {
  try {
    const { data } = await api.getReportsChartData({
      year: selectedYear.value,
      month: monthNum,
      category: categoryKey,
      action: 'drilldown',
      drilldown: 1
    })
    if (data?.success && data.drilldown) {
      drilldownData.value = data.drilldown
    }
  } catch (e) {
    console.error('Error al cargar detalle granular:', e)
  }
}

function clearDrilldown() {
  drilldownActive.value = false
  drilldownData.value   = null
  filterMonth.value     = 'all'
  renderChart()
}

// ── Fetch Main Chart Data from Backend ────────────────────────────────
async function fetchChartData() {
  loading.value = true
  try {
    const { data } = await api.getReportsChartData({
      year: selectedYear.value,
      action: 'chart'
    })
    if (data?.success) {
      if (data.available_years?.length) {
        availableYears.value = data.available_years
      }
      rawCategories.value = data.categories || []
      rawSeriesData.value = data.series || []
      renderChart()

      // If drilldown was open, refresh it for the new year
      if (drilldownActive.value && drilldownData.value) {
        await fetchDrilldown(drilldownData.value.month, drilldownData.value.category)
      }
    }
  } catch (err) {
    console.error('Error cargando datos del gráfico:', err)
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await fetchChartData()
  window.addEventListener('resize', onWindowResize)
})

onUnmounted(() => {
  window.removeEventListener('resize', onWindowResize)
  if (chartInstance) {
    chartInstance.destroy()
    chartInstance = null
  }
})

function onWindowResize() {
  if (chartInstance) {
    chartInstance.reflow()
  }
}

defineExpose({ fetchChartData, clearDrilldown })
</script>

<style scoped>
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
