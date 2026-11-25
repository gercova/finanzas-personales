import { defineStore } from 'pinia'
import { ref } from 'vue'
import * as api from '@/api'
import { cacheGet, cacheSet, cacheDel } from './cache'

const CACHE_KEY_DASH = 'dashboard_data'
const CACHE_KEY_EVENTS = 'calendar_events'
const TTL_DASH = 30_000 // 30s cache

export const useDashboardStore = defineStore('dashboard', () => {
  const kpi         = ref({
    saldo_disponible: 0,
    total_gastos_mes: 0,
    prestamos_pendientes: 0,
    balance_total: 0,
    monthly_limit: 4000,
    warning_threshold: 75,
    critical_threshold: 90,
    quota_pct: 0,
    quota_alert_class: 'success',
    show_alert: false
  })
  const accounts    = ref([])
  const recentTxns  = ref([])
  const events      = ref([])
  const loading     = ref(false)
  const eventsLoading = ref(false)
  const error       = ref(null)
  const monthLabel  = ref('')

  async function load(force = false) {
    if (!force) {
      const cached = cacheGet(CACHE_KEY_DASH)
      if (cached) {
        kpi.value        = cached.kpi
        accounts.value   = cached.accounts
        recentTxns.value = cached.recentTxns
        monthLabel.value = cached.month_label || ''
        return
      }
    }

    loading.value = true
    error.value   = null
    try {
      const { data } = await api.getDashboardData()
      if (data.success) {
        kpi.value        = data.kpi
        accounts.value   = data.accounts
        recentTxns.value = data.recentTxns
        monthLabel.value = data.month_label
        cacheSet(CACHE_KEY_DASH, {
          kpi: data.kpi,
          accounts: data.accounts,
          recentTxns: data.recentTxns,
          month_label: data.month_label
        }, TTL_DASH)
      } else {
        error.value = data.messages || 'Error al cargar dashboard.'
      }
    } catch (e) {
      error.value = e.message
    } finally {
      loading.value = false
    }
  }

  async function loadEvents(force = false) {
    if (!force) {
      const cached = cacheGet(CACHE_KEY_EVENTS)
      if (cached) {
        events.value = cached
        return
      }
    }

    eventsLoading.value = true
    try {
      const { data } = await api.fetchCalendarEvents()
      events.value = Array.isArray(data) ? data : []
      cacheSet(CACHE_KEY_EVENTS, events.value, TTL_DASH)
    } catch (e) {
      console.error('Error cargando eventos de calendario:', e)
    } finally {
      eventsLoading.value = false
    }
  }

  function invalidate() {
    cacheDel(CACHE_KEY_DASH)
    cacheDel(CACHE_KEY_EVENTS)
  }

  async function reload() {
    invalidate()
    await Promise.all([load(true), loadEvents(true)])
  }

  return {
    kpi,
    accounts,
    recentTxns,
    events,
    loading,
    eventsLoading,
    error,
    monthLabel,
    load,
    loadEvents,
    reload,
    invalidate
  }
})
