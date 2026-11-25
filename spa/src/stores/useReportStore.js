import { defineStore } from 'pinia'
import { ref } from 'vue'
import * as api from '@/api'
import { cacheGet, cacheSet, cacheDel } from './cache'

const CACHE_KEY = 'financial_report'
const TTL_REPORT = 60_000 // 60s cache

export const useReportStore = defineStore('report', () => {
  const kpi             = ref({
    saldo_disponible: 0,
    gasto_mes: 0,
    gasto_anio: 0,
    prestamos_pendientes: 0,
    balance_total: 0,
    total_activos: 0,
    patrimonio_neto: 0,
    ingresos_anio: 0,
    ahorro_neto_anio: 0
  })
  const accounts        = ref([])
  const monthlyExpenses = ref([])
  const topCategories   = ref([])
  const loading         = ref(false)
  const error           = ref(null)
  const currentYear     = ref(new Date().getFullYear())

  async function load(force = false) {
    if (!force) {
      const cached = cacheGet(CACHE_KEY)
      if (cached) {
        kpi.value             = cached.kpi
        accounts.value        = cached.accounts
        monthlyExpenses.value = cached.monthlyExpenses
        topCategories.value   = cached.topCategories
        currentYear.value     = cached.current_year
        return
      }
    }

    loading.value = true
    error.value   = null
    try {
      const { data } = await api.getFinancialReportData()
      if (data.success) {
        kpi.value             = data.kpi
        accounts.value        = data.accounts
        monthlyExpenses.value = data.monthlyExpenses
        topCategories.value   = data.topCategories
        currentYear.value     = data.current_year
        cacheSet(CACHE_KEY, {
          kpi: data.kpi,
          accounts: data.accounts,
          monthlyExpenses: data.monthlyExpenses,
          topCategories: data.topCategories,
          current_year: data.current_year
        }, TTL_REPORT)
      } else {
        error.value = data.messages || 'Error al obtener reporte.'
      }
    } catch (e) {
      error.value = e.message
    } finally {
      loading.value = false
    }
  }

  function invalidate() {
    cacheDel(CACHE_KEY)
  }

  async function reload() {
    invalidate()
    await load(true)
  }

  return {
    kpi,
    accounts,
    monthlyExpenses,
    topCategories,
    currentYear,
    loading,
    error,
    load,
    reload,
    invalidate
  }
})
