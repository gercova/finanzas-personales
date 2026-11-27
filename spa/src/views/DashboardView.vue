<template>
  <div>
    <!-- Quota alert banner -->
    <div v-if="kpi.show_alert" :class="'alert alert-' + kpi.quota_alert_class + ' alert-dismissible'" role="alert" style="margin-bottom:15px;">
      <button type="button" class="close" @click="dismissAlert" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
      <strong><i class="glyphicon glyphicon-warning-sign"></i> Atención:</strong>
      Has alcanzado el <strong>{{ kpi.quota_pct }}%</strong> de tu límite de gasto mensual configurado (<strong>S/ {{ fmt(kpi.monthly_limit) }}</strong>).
      Gasto acumulado este mes: <strong>S/ {{ fmt(kpi.total_gastos_mes) }}</strong>.
    </div>

    <!-- Top Stat Cards (identical layout and gradients from dashboard.php) -->
    <div class="row" style="margin-bottom: 20px;">
      <!-- Saldo Disponible -->
      <div class="col-xs-12 col-sm-6 col-md-3">
        <div class="stat-card stat-card-green">
          <i class="glyphicon glyphicon-piggy-bank stat-icon"></i>
          <div class="stat-label">Saldo Disponible</div>
          <div class="stat-value">S/ {{ fmt(kpi.saldo_disponible) }}</div>
          <small style="opacity: 0.85;">suma de cuentas activas</small>
        </div>
      </div>

      <!-- Total Gastos Mes -->
      <div class="col-xs-12 col-sm-6 col-md-3">
        <div class="stat-card stat-card-amber">
          <i class="glyphicon glyphicon-arrow-down stat-icon"></i>
          <div class="stat-label">Gastos del Mes</div>
          <div class="stat-value">S/ {{ fmt(kpi.total_gastos_mes) }}</div>
          <small style="opacity: 0.85;">{{ monthLabel || currentMonthName }}</small>
        </div>
      </div>

      <!-- Préstamos Pendientes -->
      <div class="col-xs-12 col-sm-6 col-md-3">
        <div class="stat-card stat-card-blue">
          <i class="glyphicon glyphicon-retweet stat-icon"></i>
          <div class="stat-label">Préstamos Pendientes</div>
          <div class="stat-value">S/ {{ fmt(kpi.prestamos_pendientes) }}</div>
          <small style="opacity: 0.85;">por cobrar</small>
        </div>
      </div>

      <!-- Balance Total -->
      <div class="col-xs-12 col-sm-6 col-md-3">
        <div class="stat-card stat-card-purple">
          <i class="glyphicon glyphicon-equalizer stat-icon"></i>
          <div class="stat-label">Balance Total</div>
          <div class="stat-value">S/ {{ fmt(kpi.balance_total) }}</div>
          <small style="opacity: 0.85;">saldo + préstamos</small>
        </div>
      </div>
    </div>

    <!-- Monthly Spending Quota Bar (Azure style) -->
    <div v-if="kpi.monthly_limit > 0" class="panel panel-default" style="margin-bottom: 20px;">
      <div class="panel-body" style="padding: 12px 18px; background: #fafafa; border-radius: 4px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 6px; flex-wrap:wrap; gap:6px;">
          <div>
            <i class="glyphicon glyphicon-dashboard text-muted"></i>
            <strong style="margin-left: 5px;">Cuota de Gasto Mensual ({{ currentMonthName }}):</strong>
            <span style="margin-left: 6px;">S/ {{ fmt(kpi.total_gastos_mes) }} de S/ {{ fmt(kpi.monthly_limit) }}</span>
          </div>
          <div>
            <span :class="'label label-' + (kpi.quota_alert_class || 'success')" style="font-size:12px; padding:3px 8px;">
              {{ kpi.quota_pct }}% consumido
            </span>
          </div>
        </div>
        <div class="progress" style="height: 12px; margin-bottom: 0;">
          <div
            :class="'progress-bar progress-bar-' + (kpi.quota_alert_class || 'success') + ' progress-bar-striped active'"
            role="progressbar"
            :style="{ width: Math.min(kpi.quota_pct, 100) + '%' }"
          ></div>
        </div>
      </div>
    </div>

    <!-- Main Content Row: Left (Accounts & Movements), Right (Interactive Calendar) -->
    <div class="row">
      <!-- Left Column: Accounts + Recent Movements -->
      <div class="col-xs-12 col-md-5">
        <!-- Accounts Panel -->
        <div class="panel panel-default">
          <div class="panel-heading panel-heading-flex" style="min-height:44px; padding: 8px 14px;">
            <div class="page-heading" style="font-size:14px; font-weight:600;">
              <i class="glyphicon glyphicon-credit-card text-primary"></i> Mis Cuentas
            </div>
            <router-link to="/accounts" class="btn-panel-subtle">
              <i class="glyphicon glyphicon-cog"></i> Administrar
            </router-link>
          </div>
          <div class="panel-body" style="padding:0;">
            <div class="table-responsive" style="margin-bottom:0;">
              <table class="table table-hover table-condensed" style="margin-bottom:0;">
                <thead>
                  <tr style="background:#f9f9f9; font-size:12px;">
                    <SortableTh label="Cuenta" sort-key="name" :active-key="accSortKey" :sort-dir="accSortDir" @sort="toggleAccSort" />
                    <SortableTh label="Tipo" sort-key="account_type" :active-key="accSortKey" :sort-dir="accSortDir" @sort="toggleAccSort" />
                    <SortableTh label="Saldo Actual" sort-key="balance" :active-key="accSortKey" :sort-dir="accSortDir" align="right" @sort="toggleAccSort" />
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="sortedAccounts.length === 0">
                    <td colspan="3" class="text-center text-muted" style="padding:15px;">No hay cuentas registradas.</td>
                  </tr>
                  <tr v-for="acc in sortedAccounts" :key="acc.account_id" style="font-size:13px;">
                    <td>
                      <span v-if="acc.color" :style="{ display:'inline-block', width:'10px', height:'10px', borderRadius:'50%', background:acc.color, marginRight:'6px' }"></span>
                      <strong>{{ acc.name || acc.account_name }}</strong>
                    </td>
                    <td>
                      <span class="label label-default" style="font-size:10.5px;">{{ acc.account_type }}</span>
                    </td>
                    <td class="text-right font-weight-bold" :style="{ color: (parseFloat(acc.balance || acc.current_balance) >= 0 ? '#27ae60' : '#e74c3c'), fontWeight: 700 }">
                      {{ acc.currency || 'S/' }} {{ fmt(acc.balance || acc.current_balance) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Recent Transactions Panel -->
        <div class="panel panel-default">
          <div class="panel-heading" style="padding: 10px 14px;">
            <i class="glyphicon glyphicon-time text-muted"></i>
            <strong style="margin-left:5px; font-size:14px;">Últimos Movimientos</strong>
          </div>
          <div class="panel-body" style="padding:0;">
            <div v-if="recentTxns.length === 0" class="text-center text-muted" style="padding:20px;">
              No hay movimientos recientes registrados.
            </div>
            <ul v-else class="list-group" style="margin-bottom:0; font-size:12.5px;">
              <li
                v-for="(txn, idx) in recentTxns"
                :key="idx"
                class="list-group-item"
                style="display:flex; justify-content:space-between; align-items:center; padding:9px 14px;"
                :style="{ backgroundColor: txn.txn_type === 'ingreso' ? '#f0fff4' : '#fff5f5' }"
              >
                <div>
                  <i :class="'glyphicon ' + (txn.txn_type === 'ingreso' ? 'glyphicon-arrow-up text-success' : 'glyphicon-arrow-down text-danger')" style="margin-right:6px;"></i>
                  <strong>{{ txn.description }}</strong>
                  <div style="font-size:11px; color:#888; margin-top:2px;">{{ txn.txn_date }}</div>
                </div>
                <div class="text-right">
                  <span
                    :class="'label ' + (txn.txn_type === 'ingreso' ? 'label-success' : 'label-danger')"
                    style="font-size:12px; font-weight:700;"
                  >
                    {{ txn.txn_type === 'ingreso' ? '+' : '-' }} S/ {{ fmt(txn.amount) }}
                  </span>
                </div>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Right Column: Interactive Financial Calendar -->
      <div class="col-xs-12 col-md-7">
        <div class="panel panel-default">
          <div class="panel-heading panel-heading-flex" style="padding: 8px 14px;">
            <div class="page-heading" style="font-size:14px; font-weight:600;">
              <i class="glyphicon glyphicon-calendar text-info"></i> Calendario Financiero
            </div>
            <!-- Calendar Navigation -->
            <div class="cal-nav-group">
              <button type="button" class="cal-nav-btn" @click="prevMonth" title="Mes Anterior">&lsaquo;</button>
              <span style="font-weight:700; font-size:13px; min-width:130px; text-align:center; color:#1e293b;">
                {{ currentMonthDisplay }}
              </span>
              <button type="button" class="cal-nav-btn" @click="nextMonth" title="Mes Siguiente">&rsaquo;</button>
              <button type="button" class="cal-today-btn" @click="goToToday" style="margin-left:2px;">Hoy</button>
            </div>
          </div>
          <div class="panel-body" style="padding: 10px;">
            <!-- Calendar Grid -->
            <div class="calendar-container">
              <!-- Weekday Header -->
              <div class="calendar-week-header">
                <div v-for="w in weekDays" :key="w" class="calendar-weekday">{{ w }}</div>
              </div>

              <!-- Days Grid -->
              <div class="calendar-grid">
                <div
                  v-for="(cell, cIdx) in calendarCells"
                  :key="cIdx"
                  class="calendar-day"
                  :class="{
                    'other-month': !cell.currentMonth,
                    'is-today': cell.isToday
                  }"
                >
                  <div class="day-number">{{ cell.dayNumber }}</div>
                  <div class="day-events">
                    <div
                      v-for="(ev, evIdx) in cell.events.slice(0, 3)"
                      :key="evIdx"
                      class="cal-event-pill"
                      :style="{ backgroundColor: ev.backgroundColor, color: ev.textColor || '#fff' }"
                      :title="ev.title"
                      @click="openEventDetails(ev)"
                    >
                      {{ ev.title }}
                    </div>
                    <div
                      v-if="cell.events.length > 3"
                      class="cal-event-more"
                      @click="openDayMore(cell)"
                      title="Ver todos los eventos"
                    >
                      +{{ cell.events.length - 3 }} más
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Calendar Legend -->
            <div class="calendar-legend">
              <span class="label" style="background:#27ae60; color:#fff;">
                <i class="glyphicon glyphicon-arrow-up"></i> Ingresos
              </span>
              <span class="label" style="background:#c0392b; color:#fff;">
                <i class="glyphicon glyphicon-arrow-down"></i> Gastos
              </span>
              <span class="label" style="background:#e67e22; color:#fff;">
                <i class="glyphicon glyphicon-transfer"></i> Préstamos
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Calendar Event Details Modal (AppModal) -->
    <AppModal
      v-model="showEventModal"
      :title="selectedEvent?.typeLabel || 'Detalle del Movimiento'"
      :icon="selectedEventIcon"
      :header-color="selectedEvent?.backgroundColor || '#337ab7'"
    >
      <template #body>
        <div v-if="selectedEvent" style="font-size: 13px;">
          <div class="text-center" style="margin-bottom: 16px;">
            <div :style="{ fontSize: '26px', fontWeight: '700', color: selectedEvent.backgroundColor }">
              {{ (selectedEvent.type === 'ingreso' ? '+' : (selectedEvent.type === 'gasto' ? '-' : '⇄ ')) }}
              S/ {{ fmt(selectedEvent.amount) }}
            </div>
            <div style="margin-top: 6px;">
              <span :class="'label ' + (selectedEvent.badgeClass || 'label-default')">
                {{ selectedEvent.status || 'REGISTRADO' }}
              </span>
              <span v-if="selectedEvent.type === 'prestamo' && selectedEvent.balancePending" class="label label-danger" style="margin-left:6px;">
                Pendiente: S/ {{ fmt(selectedEvent.balancePending) }}
              </span>
            </div>
          </div>

          <table class="table table-condensed table-striped" style="margin-bottom:0;">
            <tbody>
              <tr>
                <th style="width: 35%; color: #666;">Concepto:</th>
                <td style="font-weight: 600;">{{ selectedEvent.concept || '-' }}</td>
              </tr>
              <tr>
                <th style="color: #666;">Fecha:</th>
                <td>{{ selectedEvent.start || '-' }}</td>
              </tr>
              <tr v-if="selectedEvent.account">
                <th style="color: #666;">Cuenta:</th>
                <td>{{ selectedEvent.account }}</td>
              </tr>
              <tr v-if="selectedEvent.type === 'prestamo' && selectedEvent.borrower">
                <th style="color: #666;">Prestatario:</th>
                <td>{{ selectedEvent.borrower }}</td>
              </tr>
              <tr v-else-if="selectedEvent.category">
                <th style="color: #666;">Categoría:</th>
                <td>{{ selectedEvent.category }} {{ selectedEvent.project ? '(' + selectedEvent.project + ')' : '' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <template #footer>
        <button class="btn btn-default" @click="showEventModal = false">Cerrar</button>
        <button v-if="selectedEventRoute" class="btn btn-primary" @click="goToEventModule">
          <i class="glyphicon glyphicon-share-alt"></i> Ir al módulo
        </button>
      </template>
    </AppModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useDashboardStore } from '@/stores/useDashboardStore'
import AppModal from '@/components/AppModal.vue'
import SortableTh from '@/components/SortableTh.vue'
import { useTableSort } from '@/utils/sort'

const router = useRouter()
const store  = useDashboardStore()

const kpi        = computed(() => store.kpi)
const accounts   = computed(() => store.accounts)
const {
  sortKey: accSortKey,
  sortDir: accSortDir,
  toggleSort: toggleAccSort,
  sortRows: sortAccRows
} = useTableSort('', 'asc', {
  name:    r => r.name || r.account_name || '',
  balance: r => parseFloat(r.balance ?? r.current_balance) || 0,
})
const sortedAccounts = computed(() => sortAccRows(accounts.value))
const recentTxns = computed(() => store.recentTxns)
const events     = computed(() => store.events)
const monthLabel = computed(() => store.monthLabel)

const showEventModal = ref(false)
const selectedEvent  = ref(null)

// Calendar state: current year and month view
const calDate = ref(new Date())

const weekDays = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb']

const monthNames = [
  'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
  'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
]

const currentMonthName = computed(() => {
  return monthNames[new Date().getMonth()] + ' ' + new Date().getFullYear()
})

const currentMonthDisplay = computed(() => {
  const y = calDate.value.getFullYear()
  const m = calDate.value.getMonth()
  return monthNames[m] + ' ' + y
})

function prevMonth() {
  const d = new Date(calDate.value)
  d.setMonth(d.getMonth() - 1)
  calDate.value = d
}

function nextMonth() {
  const d = new Date(calDate.value)
  d.setMonth(d.getMonth() + 1)
  calDate.value = d
}

function goToToday() {
  calDate.value = new Date()
}

function dismissAlert() {
  if (kpi.value) {
    kpi.value.show_alert = false
  }
}

// Generate calendar cells for current view
const calendarCells = computed(() => {
  const year  = calDate.value.getFullYear()
  const month = calDate.value.getMonth()

  const firstDay = new Date(year, month, 1)
  const lastDay  = new Date(year, month + 1, 0)

  const startingDayOfWeek = firstDay.getDay()
  const totalDaysInMonth  = lastDay.getDate()

  const prevMonthLastDay = new Date(year, month, 0).getDate()

  const cells = []
  const todayStr = new Date().toISOString().slice(0, 10)

  // Preceding days from previous month
  for (let i = startingDayOfWeek - 1; i >= 0; i--) {
    const d = prevMonthLastDay - i
    const prevDate = new Date(year, month - 1, d)
    const dateStr = prevDate.toISOString().slice(0, 10)
    cells.push({
      dayNumber: d,
      dateStr,
      currentMonth: false,
      isToday: dateStr === todayStr,
      events: getEventsForDate(dateStr)
    })
  }

  // Days in current month
  for (let d = 1; d <= totalDaysInMonth; d++) {
    const curDate = new Date(year, month, d)
    // Adjust timezone formatting to YYYY-MM-DD
    const yStr = curDate.getFullYear()
    const mStr = String(curDate.getMonth() + 1).padStart(2, '0')
    const dStr = String(d).padStart(2, '0')
    const dateStr = `${yStr}-${mStr}-${dStr}`

    cells.push({
      dayNumber: d,
      dateStr,
      currentMonth: true,
      isToday: dateStr === todayStr,
      events: getEventsForDate(dateStr)
    })
  }

  // Trailing days from next month to fill grid (up to 35 or 42 cells)
  const remaining = (7 - (cells.length % 7)) % 7
  for (let d = 1; d <= remaining; d++) {
    const nextDate = new Date(year, month + 1, d)
    const yStr = nextDate.getFullYear()
    const mStr = String(nextDate.getMonth() + 1).padStart(2, '0')
    const dStr = String(d).padStart(2, '0')
    const dateStr = `${yStr}-${mStr}-${dStr}`
    cells.push({
      dayNumber: d,
      dateStr,
      currentMonth: false,
      isToday: dateStr === todayStr,
      events: getEventsForDate(dateStr)
    })
  }

  return cells
})

function getEventsForDate(dateStr) {
  if (!events.value || events.value.length === 0) return []
  return events.value.filter(e => {
    const evDate = typeof e.start === 'string' ? e.start.slice(0, 10) : ''
    return evDate === dateStr
  })
}

function openEventDetails(ev) {
  selectedEvent.value = ev
  showEventModal.value = true
}

function openDayMore(cell) {
  if (cell.events && cell.events.length > 0) {
    selectedEvent.value = cell.events[0]
    showEventModal.value = true
  }
}

const selectedEventIcon = computed(() => {
  if (!selectedEvent.value) return 'glyphicon-usd'
  if (selectedEvent.value.type === 'ingreso') return 'glyphicon-arrow-up'
  if (selectedEvent.value.type === 'gasto') return 'glyphicon-arrow-down'
  return 'glyphicon-retweet'
})

const selectedEventRoute = computed(() => {
  if (!selectedEvent.value) return null
  if (selectedEvent.value.type === 'ingreso') return '/income'
  if (selectedEvent.value.type === 'gasto') return '/expense'
  if (selectedEvent.value.type === 'prestamo') return '/loans'
  return null
})

function goToEventModule() {
  if (selectedEventRoute.value) {
    showEventModal.value = false
    router.push(selectedEventRoute.value)
  }
}

function fmt(n) {
  const num = parseFloat(n) || 0
  return num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')
}

onMounted(async () => {
  await Promise.all([store.load(), store.loadEvents()])
})
</script>

<style scoped>
/* Stat Cards matching dashboard.php */
.stat-card {
  border-radius: 6px;
  padding: 18px 20px 14px;
  color: #fff;
  margin-bottom: 15px;
  position: relative;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.stat-card .stat-icon {
  position: absolute;
  right: 16px;
  top: 14px;
  font-size: 36px;
  opacity: 0.25;
}
.stat-card .stat-label {
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 1px;
  opacity: 0.85;
  margin-bottom: 6px;
}
.stat-card .stat-value {
  font-size: 26px;
  font-weight: 700;
  line-height: 1.1;
}
.stat-card-green  { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
.stat-card-amber  { background: linear-gradient(135deg, #fd7e14 0%, #ffc107 100%); }
.stat-card-blue   { background: linear-gradient(135deg, #17a2b8 0%, #007bff 100%); }
.stat-card-purple { background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%); }

/* Calendar component */
.calendar-container {
  width: 100%;
  border: 1px solid #ddd;
  border-radius: 4px;
  overflow: hidden;
  background: #fff;
}
.calendar-week-header {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  background: #f7f7f7;
  border-bottom: 1px solid #ddd;
}
.calendar-weekday {
  padding: 8px 4px;
  text-align: center;
  font-weight: 700;
  font-size: 12px;
  color: #555;
  border-right: 1px solid #eee;
}
.calendar-weekday:last-child {
  border-right: none;
}
.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
}
.calendar-day {
  min-height: 75px;
  border-right: 1px solid #eee;
  border-bottom: 1px solid #eee;
  padding: 4px;
  background: #fff;
  transition: background-color 0.15s ease;
}
.calendar-day:nth-child(7n) {
  border-right: none;
}
.calendar-day.other-month {
  background: #fbfbfb;
  opacity: 0.5;
}
.calendar-day.is-today {
  background: #fcf8e3 !important;
}
.day-number {
  font-size: 11px;
  font-weight: 700;
  color: #444;
  margin-bottom: 3px;
}
.day-events {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.cal-event-pill {
  font-size: 10.5px;
  line-height: 1.25;
  padding: 2px 4px;
  border-radius: 3px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  cursor: pointer;
  font-weight: 600;
  box-shadow: 0 1px 2px rgba(0,0,0,0.1);
  transition: opacity 0.15s ease, transform 0.1s ease;
}
.cal-event-pill:hover {
  opacity: 0.9;
  transform: scale(0.98);
}
.cal-event-more {
  font-size: 10px;
  color: #337ab7;
  font-weight: 700;
  cursor: pointer;
  text-align: center;
  margin-top: 1px;
}
.calendar-legend {
  margin-top: 10px;
  padding-top: 8px;
  border-top: 1px solid #eee;
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  gap: 8px;
}
.calendar-legend .label {
  font-size: 11px;
  padding: 4px 8px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
</style>
