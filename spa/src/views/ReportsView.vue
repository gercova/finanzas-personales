<template>
  <div>
    <ol class="breadcrumb">
      <li><a href="#" @click.prevent="$router.push('/dashboard')">Inicio</a></li>
      <li class="active">Reporte Financiero</li>
    </ol>

    <!-- Top Action Bar -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; flex-wrap:wrap; gap:8px;">
      <h3 style="margin:0; font-size:20px; font-weight:700; color:#333;">
        <i class="glyphicon glyphicon-stats text-info" style="margin-right:6px;"></i> Resumen Financiero Ejecutivo
      </h3>
      <div style="display:inline-flex; gap:6px;">
        <button class="btn btn-sm btn-default" @click="refreshAll" :disabled="loading">
          <i class="glyphicon glyphicon-refresh" :class="{ 'spin-icon': loading }"></i> Actualizar
        </button>
        <button class="btn btn-sm btn-info" @click="printReport">
          <i class="glyphicon glyphicon-print"></i> Imprimir Reporte
        </button>
      </div>
    </div>

    <!-- Error notice -->
    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <!-- Loading Skeleton -->
    <div v-if="loading && !kpi.balance_total" class="text-center" style="padding:40px 0;">
      <i class="glyphicon glyphicon-refresh" style="font-size:32px; animation:spin 1s linear infinite; color:#aaa;"></i>
      <p class="text-muted" style="margin-top:10px;">Calculando métricas financieras…</p>
    </div>

    <div v-else>
      <!-- Cards Row 1: Core Financial Totals -->
      <div class="row" style="margin-bottom: 15px;">
        <!-- Saldo Disponible -->
        <div class="col-xs-12 col-sm-6 col-md-3">
          <div class="panel" style="border-top: 4px solid #27ae60; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div class="panel-body text-center" style="padding: 18px 12px;">
              <div style="font-size: 12px; color: #777; margin-bottom: 4px; text-transform: uppercase; letter-spacing:0.5px;">
                <i class="glyphicon glyphicon-piggy-bank"></i> Saldo Disponible
              </div>
              <h3 style="margin:0; font-weight:700; color:#27ae60; font-size:22px;">
                S/ {{ fmt(kpi.saldo_disponible) }}
              </h3>
              <small class="text-muted">suma de cuentas activas</small>
            </div>
          </div>
        </div>

        <!-- Total Gastos Año -->
        <div class="col-xs-12 col-sm-6 col-md-3">
          <div class="panel" style="border-top: 4px solid #e74c3c; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div class="panel-body text-center" style="padding: 18px 12px;">
              <div style="font-size: 12px; color: #777; margin-bottom: 4px; text-transform: uppercase; letter-spacing:0.5px;">
                <i class="glyphicon glyphicon-arrow-down"></i> Total Gastos {{ currentYear }}
              </div>
              <h3 style="margin:0; font-weight:700; color:#e74c3c; font-size:22px;">
                S/ {{ fmt(kpi.gasto_anio) }}
              </h3>
              <small class="text-muted">mes actual: S/ {{ fmt(kpi.gasto_mes) }}</small>
            </div>
          </div>
        </div>

        <!-- Total Préstamos Pendientes -->
        <div class="col-xs-12 col-sm-6 col-md-3">
          <div class="panel" style="border-top: 4px solid #f39c12; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div class="panel-body text-center" style="padding: 18px 12px;">
              <div style="font-size: 12px; color: #777; margin-bottom: 4px; text-transform: uppercase; letter-spacing:0.5px;">
                <i class="glyphicon glyphicon-share-alt"></i> Préstamos Pendientes
              </div>
              <h3 style="margin:0; font-weight:700; color:#f39c12; font-size:22px;">
                S/ {{ fmt(kpi.prestamos_pendientes) }}
              </h3>
              <small class="text-muted">por cobrar</small>
            </div>
          </div>
        </div>

        <!-- Balance Total -->
        <div class="col-xs-12 col-sm-6 col-md-3">
          <div class="panel" style="border-top: 4px solid #2980b9; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div class="panel-body text-center" style="padding: 18px 12px;">
              <div style="font-size: 12px; color: #777; margin-bottom: 4px; text-transform: uppercase; letter-spacing:0.5px;">
                <i class="glyphicon glyphicon-equalizer"></i> Balance Total
              </div>
              <h3 style="margin:0; font-weight:700; color:#2980b9; font-size:22px;">
                S/ {{ fmt(kpi.balance_total) }}
              </h3>
              <small class="text-muted">saldo + préstamos</small>
            </div>
          </div>
        </div>
      </div>

      <!-- Cards Row 2: Assets & Wealth Net Worth -->
      <div class="row" style="margin-bottom: 20px;">
        <!-- Total Activos -->
        <div class="col-xs-12 col-sm-6 col-md-3">
          <div class="panel" style="border-top: 4px solid #8e44ad; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div class="panel-body text-center" style="padding: 18px 12px;">
              <div style="font-size: 12px; color: #777; margin-bottom: 4px; text-transform: uppercase; letter-spacing:0.5px;">
                <i class="glyphicon glyphicon-certificate"></i> Total Activos
              </div>
              <h3 style="margin:0; font-weight:700; color:#8e44ad; font-size:22px;">
                S/ {{ fmt(kpi.total_activos) }}
              </h3>
              <small class="text-muted">valor estimado de posesiones</small>
            </div>
          </div>
        </div>

        <!-- Patrimonio Neto -->
        <div class="col-xs-12 col-sm-6 col-md-3">
          <div class="panel" style="border-top: 4px solid #16a085; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div class="panel-body text-center" style="padding: 18px 12px;">
              <div style="font-size: 12px; color: #777; margin-bottom: 4px; text-transform: uppercase; letter-spacing:0.5px;">
                <i class="glyphicon glyphicon-briefcase"></i> Patrimonio Neto
              </div>
              <h3 style="margin:0; font-weight:700; color:#16a085; font-size:22px;">
                S/ {{ fmt(kpi.patrimonio_neto) }}
              </h3>
              <small class="text-muted">balance + activos</small>
            </div>
          </div>
        </div>

        <!-- Ingresos del Año -->
        <div class="col-xs-12 col-sm-6 col-md-3">
          <div class="panel" style="border-top: 4px solid #27ae60; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div class="panel-body text-center" style="padding: 18px 12px;">
              <div style="font-size: 12px; color: #777; margin-bottom: 4px; text-transform: uppercase; letter-spacing:0.5px;">
                <i class="glyphicon glyphicon-arrow-up"></i> Ingresos {{ currentYear }}
              </div>
              <h3 style="margin:0; font-weight:700; color:#27ae60; font-size:22px;">
                S/ {{ fmt(kpi.ingresos_anio) }}
              </h3>
              <small class="text-muted">total percibido en el año</small>
            </div>
          </div>
        </div>

        <!-- Ahorro Neto del Año -->
        <div class="col-xs-12 col-sm-6 col-md-3">
          <div class="panel" style="border-top: 4px solid #2980b9; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div class="panel-body text-center" style="padding: 18px 12px;">
              <div style="font-size: 12px; color: #777; margin-bottom: 4px; text-transform: uppercase; letter-spacing:0.5px;">
                <i class="glyphicon glyphicon-scale"></i> Ahorro Neto {{ currentYear }}
              </div>
              <h3
                style="margin:0; font-weight:700; font-size:22px;"
                :style="{ color: kpi.ahorro_neto_anio >= 0 ? '#27ae60' : '#e74c3c' }"
              >
                S/ {{ fmt(kpi.ahorro_neto_anio) }}
              </h3>
              <small class="text-muted">ingresos - gastos</small>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Highcharts Bar Chart (Ingresos, Gastos, Préstamos y Proyectos) -->
      <FinancialBarChart ref="barChartRef" />

      <!-- Detail Breakdowns Row -->
      <div class="row">
        <!-- Monthly Expenses Trend (Last 6 Months) -->
        <div class="col-xs-12 col-md-6">
          <div class="panel panel-default">
            <div class="panel-heading">
              <i class="glyphicon glyphicon-calendar text-danger"></i>
              <strong style="margin-left:6px;">Evolución de Gastos (Últimos 6 Meses)</strong>
            </div>
            <div class="panel-body" style="padding:15px;">
              <div v-if="monthlyExpenses.length === 0" class="text-center text-muted" style="padding:20px;">
                No hay historial de gastos registrado.
              </div>
              <div v-else>
                <div v-for="m in monthlyExpenses" :key="m.mes" style="margin-bottom: 12px;">
                  <div style="display:flex; justify-content:space-between; font-size:12.5px; margin-bottom:3px;">
                    <strong>{{ m.mes }}</strong>
                    <span class="text-danger font-weight-bold">S/ {{ fmt(m.total) }}</span>
                  </div>
                  <div class="progress" style="height: 10px; margin-bottom: 0;">
                    <div
                      class="progress-bar progress-bar-danger"
                      role="progressbar"
                      :style="{ width: calcBarWidth(m.total, maxMonthlyExpense) + '%' }"
                    ></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Top 5 Expense Categories -->
        <div class="col-xs-12 col-md-6">
          <div class="panel panel-default">
            <div class="panel-heading">
              <i class="glyphicon glyphicon-tags text-primary"></i>
              <strong style="margin-left:6px;">Top Categorías de Gasto ({{ currentYear }})</strong>
            </div>
            <div class="panel-body" style="padding:15px;">
              <div v-if="topCategories.length === 0" class="text-center text-muted" style="padding:20px;">
                No hay gastos categorizados en este año.
              </div>
              <div v-else>
                <div v-for="(cat, idx) in topCategories" :key="idx" style="margin-bottom: 12px;">
                  <div style="display:flex; justify-content:space-between; font-size:12.5px; margin-bottom:3px;">
                    <span>
                      <strong style="margin-right:6px;">{{ idx + 1 }}.</strong>
                      {{ cat.category_name || 'Sin Categoría' }}
                    </span>
                    <span class="text-primary font-weight-bold">S/ {{ fmt(cat.total) }}</span>
                  </div>
                  <div class="progress" style="height: 10px; margin-bottom: 0;">
                    <div
                      class="progress-bar progress-bar-info"
                      role="progressbar"
                      :style="{ width: calcBarWidth(cat.total, maxCategoryExpense) + '%' }"
                    ></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Accounts Distribution Table -->
      <div class="panel panel-default">
        <div class="panel-heading">
          <i class="glyphicon glyphicon-credit-card text-success"></i>
          <strong style="margin-left:6px;">Distribución de Saldos por Cuenta</strong>
        </div>
        <div class="panel-body" style="padding:0;">
          <div class="table-responsive">
            <table class="table table-hover table-striped" style="margin-bottom:0;">
              <thead>
                <tr>
                  <SortableTh label="Nombre de Cuenta" sort-key="account_name" :active-key="accSortKey" :sort-dir="accSortDir" @sort="toggleAccSort" />
                  <SortableTh label="Tipo" sort-key="account_type" :active-key="accSortKey" :sort-dir="accSortDir" @sort="toggleAccSort" />
                  <SortableTh label="Moneda" sort-key="currency" :active-key="accSortKey" :sort-dir="accSortDir" @sort="toggleAccSort" />
                  <SortableTh label="Saldo Actual" sort-key="current_balance" :active-key="accSortKey" :sort-dir="accSortDir" align="right" @sort="toggleAccSort" />
                  <SortableTh label="Participación" sort-key="share" :active-key="accSortKey" :sort-dir="accSortDir" :th-style="{ width: '25%' }" @sort="toggleAccSort" />
                </tr>
              </thead>
              <tbody>
                <tr v-if="sortedAccounts.length === 0">
                  <td colspan="5" class="text-center text-muted" style="padding:20px;">No hay cuentas activas.</td>
                </tr>
                <tr v-for="acc in sortedAccounts" :key="acc.account_name">
                  <td><strong>{{ acc.account_name }}</strong></td>
                  <td><span class="label label-default">{{ acc.account_type }}</span></td>
                  <td>{{ acc.currency || 'S/' }}</td>
                  <td class="text-right font-weight-bold" :style="{ color: parseFloat(acc.current_balance) >= 0 ? '#27ae60' : '#e74c3c' }">
                    S/ {{ fmt(acc.current_balance) }}
                  </td>
                  <td style="vertical-align:middle;">
                    <div class="progress" style="height: 8px; margin-bottom: 0;">
                      <div
                        class="progress-bar progress-bar-success"
                        :style="{ width: calcAccountShare(acc.current_balance) + '%' }"
                      ></div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useReportStore } from '@/stores/useReportStore'
import FinancialBarChart from '@/components/FinancialBarChart.vue'
import SortableTh from '@/components/SortableTh.vue'
import { useTableSort } from '@/utils/sort'

const store = useReportStore()
const barChartRef = ref(null)

const kpi             = computed(() => store.kpi)
const accounts        = computed(() => store.accounts)
const {
  sortKey: accSortKey,
  sortDir: accSortDir,
  toggleSort: toggleAccSort,
  sortRows: sortAccRows
} = useTableSort('', 'asc', {
  current_balance: r => parseFloat(r.current_balance) || 0,
  share: r => calcAccountShare(r.current_balance),
})
const sortedAccounts  = computed(() => sortAccRows(accounts.value))
const monthlyExpenses = computed(() => store.monthlyExpenses)
const topCategories   = computed(() => store.topCategories)
const currentYear     = computed(() => store.currentYear)
const loading         = computed(() => store.loading)
const error           = computed(() => store.error)

const maxMonthlyExpense = computed(() => {
  if (!monthlyExpenses.value.length) return 1
  return Math.max(...monthlyExpenses.value.map(m => parseFloat(m.total) || 0), 1)
})

const maxCategoryExpense = computed(() => {
  if (!topCategories.value.length) return 1
  return Math.max(...topCategories.value.map(c => parseFloat(c.total) || 0), 1)
})

function calcBarWidth(val, max) {
  const v = parseFloat(val) || 0
  const m = parseFloat(max) || 1
  return Math.min(Math.round((v / m) * 100), 100)
}

function calcAccountShare(balance) {
  const b = parseFloat(balance) || 0
  const total = parseFloat(kpi.value.saldo_disponible) || 1
  if (total <= 0 || b <= 0) return 0
  return Math.min(Math.round((b / total) * 100), 100)
}

function fmt(n) {
  const num = parseFloat(n) || 0
  return num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')
}

function printReport() {
  window.print()
}

async function refreshAll() {
  await store.reload()
  barChartRef.value?.fetchChartData()
}

onMounted(() => {
  store.load()
})
</script>

<style scoped>
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
.spin-icon {
  animation: spin 1s linear infinite;
}
</style>
