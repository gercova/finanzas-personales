import { defineStore } from 'pinia'
import { ref, reactive, computed, watch } from 'vue'
import * as api from '@/api'
import { cacheGet, cacheSet, cacheDel } from './cache'
import { sortList } from '@/utils/sort'

const CACHE_KEY = 'v3_debts'

function formatDisplayDate(iso) {
  const m = String(iso || '').match(/^(\d{4})-(\d{2})-(\d{2})$/)
  return m ? `${m[3]}/${m[2]}/${m[1]}` : ''
}

export const useDebtStore = defineStore('debt', () => {
  const rows     = ref([])
  const loading  = ref(false)
  const error    = ref(null)
  const search   = ref('')
  const page     = ref(1)
  const pageSize = ref(15)
  const sortKey  = ref('')
  const sortDir  = ref('asc')
  let sortGetter = null

  function setSort(key, valueGetter = null) {
    if (!key) return
    if (sortKey.value === key) {
      sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
    } else {
      sortKey.value = key
      sortDir.value = 'asc'
    }
    sortGetter = typeof valueGetter === 'function' ? valueGetter : null
    page.value = 1
  }

  const filters = reactive({
    status: '',             // '', 'PENDIENTE', 'PARCIAL', 'PAGADO', 'UNPAID', 'OVERDUE'
    lender: '',             // '', 'id:<id>|<name>', 'name:<lowerName>'
    lenderType: '',         // '', 'PERSONA', 'EMPRESA', 'ENTIDAD_FINANCIERA'
    accountId: '',          // '', '<id>', 'name:<lowerName>'
    dateField: 'debt_date', // 'debt_date' | 'due_date'
    dateFrom: '',           // 'YYYY-MM-DD'
    dateTo: '',             // 'YYYY-MM-DD'
    interestFilter: '',     // '', 'WITH', 'NONE'
    minAmount: '',
    maxAmount: '',
  })

  watch(filters, () => {
    page.value = 1
  }, { deep: true })

  const activeFilterCount = computed(() => {
    let count = 0
    if (filters.status) count++
    if (filters.lender) count++
    if (filters.lenderType) count++
    if (filters.accountId) count++
    if (filters.dateFrom || filters.dateTo) count++
    if (filters.interestFilter) count++
    if (filters.minAmount !== '' && filters.minAmount !== null) count++
    if (filters.maxAmount !== '' && filters.maxAmount !== null) count++
    return count
  })

  function resetFilters() {
    filters.status = ''
    filters.lender = ''
    filters.lenderType = ''
    filters.accountId = ''
    filters.dateField = 'debt_date'
    filters.dateFrom = ''
    filters.dateTo = ''
    filters.interestFilter = ''
    filters.minAmount = ''
    filters.maxAmount = ''
    search.value = ''
    page.value = 1
  }

  const filtered = computed(() => {
    const q        = search.value.trim().toLowerCase()
    const todayISO = new Date().toLocaleDateString('en-CA')

    const minAmt = filters.minAmount !== '' && filters.minAmount !== null ? parseFloat(filters.minAmount) : null
    const maxAmt = filters.maxAmount !== '' && filters.maxAmount !== null ? parseFloat(filters.maxAmount) : null

    return rows.value.filter(m => {
      // 1. Status filter
      if (filters.status) {
        if (filters.status === 'UNPAID') {
          if (m.status !== 'PENDIENTE' && m.status !== 'PARCIAL') return false
        } else if (filters.status === 'OVERDUE') {
          if (m.status === 'PAGADO' || !m.due_date || m.due_date >= todayISO) return false
        } else if (m.status !== filters.status) {
          return false
        }
      }

      // 2. Lender filter
      if (filters.lender) {
        if (filters.lender.startsWith('id:')) {
          const [idPart, ...nameParts] = filters.lender.slice(3).split('|')
          const targetId   = parseInt(idPart, 10)
          const targetName = nameParts.join('|').toLowerCase()
          const idMatch    = m.lender_id !== null && m.lender_id === targetId
          const nameMatch  = !m.lender_id && targetName && (m.lender_name || '').toLowerCase() === targetName
          if (!idMatch && !nameMatch) return false
        } else if (filters.lender.startsWith('name:')) {
          const targetName = filters.lender.slice(5).toLowerCase()
          if ((m.lender_name || '').toLowerCase() !== targetName) return false
        }
      }

      // 3. Lender type filter
      if (filters.lenderType && m.lender_type !== filters.lenderType) {
        return false
      }

      // 4. Destination Account filter
      if (filters.accountId) {
        if (filters.accountId.startsWith('name:')) {
          const targetAccName = filters.accountId.slice(5).toLowerCase()
          if ((m.account_name || '').toLowerCase() !== targetAccName) return false
        } else if (String(m.account_id || '') !== String(filters.accountId)) {
          return false
        }
      }

      // 5. Date range filter
      if (filters.dateFrom || filters.dateTo) {
        const targetDate = filters.dateField === 'due_date' ? m.due_date : m.debt_date
        if (!targetDate) return false
        if (filters.dateFrom && targetDate < filters.dateFrom) return false
        if (filters.dateTo && targetDate > filters.dateTo) return false
      }

      // 6. Interest filter
      if (filters.interestFilter) {
        const hasInterest = m.interest_type !== 'NONE' && m.interest_type !== 'NINGUNO'
        if (filters.interestFilter === 'WITH' && !hasInterest) return false
        if (filters.interestFilter === 'NONE' && hasInterest) return false
      }

      // 7. Capital amount range filter
      if (minAmt !== null && !Number.isNaN(minAmt) && m.principal_amount < minAmt) return false
      if (maxAmt !== null && !Number.isNaN(maxAmt) && m.principal_amount > maxAmt) return false

      // 8. Free-text search across structured fields
      if (q) {
        const haystack = [
          m.lender_name,
          m.lender_type,
          m.document_type,
          m.document_number,
          m.concept,
          m.notes,
          m.account_name,
          m.debt_date,
          formatDisplayDate(m.debt_date),
          m.due_date,
          formatDisplayDate(m.due_date),
          m.principal_amount,
          m.total_payable,
          m.amount_paid,
          m.balance_pending,
          m.status,
        ]
        if (!haystack.some(v => String(v ?? '').toLowerCase().includes(q))) return false
      }

      return true
    })
  })

  const sorted = computed(() => {
    if (!sortKey.value) return filtered.value
    return sortList(filtered.value, sortKey.value, sortDir.value, sortGetter)
  })

  const totalPages = computed(() => Math.max(1, Math.ceil(filtered.value.length / pageSize.value)))

  const pageRows = computed(() => {
    const safePage = Math.min(page.value, totalPages.value)
    const s = (safePage - 1) * pageSize.value
    return sorted.value.slice(s, s + pageSize.value)
  })

  async function load(force = false) {
    if (!force) {
      const c = cacheGet(CACHE_KEY)
      if (Array.isArray(c)) {
        rows.value = c
        return
      }
    }
    loading.value = true
    error.value   = null
    try {
      const { data } = await api.fetchDebts()
      if (data?.success === false) {
        rows.value  = []
        error.value = data.messages || 'Error cargando préstamos recibidos.'
        return
      }
      rows.value = Array.isArray(data?.data) ? data.data : []
      cacheSet(CACHE_KEY, rows.value)
      page.value = 1
    } catch (e) {
      error.value = e.response?.data?.messages || e.message || 'Error cargando préstamos recibidos.'
    } finally {
      loading.value = false
    }
  }

  function invalidate() {
    cacheDel(CACHE_KEY)
    cacheDel('debts')
  }

  async function reload() {
    invalidate()
    await load(true)
  }

  return {
    rows, loading, error, search, page, pageSize,
    sortKey, sortDir, setSort,
    filters, activeFilterCount, resetFilters,
    filtered, totalPages, pageRows,
    load, reload, invalidate
  }
})
