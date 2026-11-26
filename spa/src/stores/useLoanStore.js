import { defineStore } from 'pinia'
import { ref, reactive, computed, watch } from 'vue'
import * as api from '@/api'
import { cacheGet, cacheSet, cacheDel } from './cache'

const CACHE_KEY = 'v3_loans'

function formatDisplayDate(iso) {
  const m = String(iso || '').match(/^(\d{4})-(\d{2})-(\d{2})$/)
  return m ? `${m[3]}/${m[2]}/${m[1]}` : ''
}

export const useLoanStore = defineStore('loan', () => {
  const rows     = ref([])
  const loading  = ref(false)
  const error    = ref(null)
  const search   = ref('')
  const page     = ref(1)
  const pageSize = ref(15)

  const filters = reactive({
    status: '',             // '', 'PENDIENTE', 'PARCIAL', 'PAGADO', 'UNPAID', 'OVERDUE'
    borrower: '',           // '', 'id:<id>|<name>', 'name:<lowerName>'
    accountId: '',          // '', '<id>', 'name:<lowerName>'
    dateField: 'loan_date', // 'loan_date' | 'due_date'
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
    if (filters.borrower) count++
    if (filters.accountId) count++
    if (filters.dateFrom || filters.dateTo) count++
    if (filters.interestFilter) count++
    if (filters.minAmount !== '' && filters.minAmount !== null) count++
    if (filters.maxAmount !== '' && filters.maxAmount !== null) count++
    return count
  })

  function resetFilters() {
    filters.status = ''
    filters.borrower = ''
    filters.accountId = ''
    filters.dateField = 'loan_date'
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

      // 2. Borrower filter
      if (filters.borrower) {
        if (filters.borrower.startsWith('id:')) {
          const [idPart, ...nameParts] = filters.borrower.slice(3).split('|')
          const targetId   = parseInt(idPart, 10)
          const targetName = nameParts.join('|').toLowerCase()
          const idMatch    = m.borrower_id !== null && m.borrower_id === targetId
          const nameMatch  = !m.borrower_id && targetName && (m.borrower_name || '').toLowerCase() === targetName
          if (!idMatch && !nameMatch) return false
        } else if (filters.borrower.startsWith('name:')) {
          const targetName = filters.borrower.slice(5).toLowerCase()
          if ((m.borrower_name || '').toLowerCase() !== targetName) return false
        }
      }

      // 3. Source Account filter
      if (filters.accountId) {
        if (filters.accountId.startsWith('name:')) {
          const targetAccName = filters.accountId.slice(5).toLowerCase()
          if ((m.account_name || '').toLowerCase() !== targetAccName) return false
        } else if (String(m.account_id || '') !== String(filters.accountId)) {
          return false
        }
      }

      // 4. Date range filter
      if (filters.dateFrom || filters.dateTo) {
        const targetDate = filters.dateField === 'due_date' ? m.due_date : m.loan_date
        if (!targetDate) return false
        if (filters.dateFrom && targetDate < filters.dateFrom) return false
        if (filters.dateTo && targetDate > filters.dateTo) return false
      }

      // 5. Interest filter
      if (filters.interestFilter) {
        const hasInterest = m.interest_type !== 'NONE' && m.interest_type !== 'NINGUNO'
        if (filters.interestFilter === 'WITH' && !hasInterest) return false
        if (filters.interestFilter === 'NONE' && hasInterest) return false
      }

      // 6. Capital amount range filter
      if (minAmt !== null && !Number.isNaN(minAmt) && m.principal_amount < minAmt) return false
      if (maxAmt !== null && !Number.isNaN(maxAmt) && m.principal_amount > maxAmt) return false

      // 7. Free-text search across structured fields
      if (q) {
        const haystack = [
          m.borrower_name,
          m.document_type,
          m.document_number,
          m.concept,
          m.notes,
          m.account_name,
          m.loan_date,
          formatDisplayDate(m.loan_date),
          m.due_date,
          formatDisplayDate(m.due_date),
          m.principal_amount,
          m.total_receivable,
          m.amount_repaid,
          m.balance_pending,
          m.status,
        ]
        if (!haystack.some(v => String(v ?? '').toLowerCase().includes(q))) return false
      }

      return true
    })
  })

  const totalPages = computed(() => Math.max(1, Math.ceil(filtered.value.length / pageSize.value)))

  const pageRows = computed(() => {
    const safePage = Math.min(page.value, totalPages.value)
    const s = (safePage - 1) * pageSize.value
    return filtered.value.slice(s, s + pageSize.value)
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
      const { data } = await api.fetchLoans()
      rows.value = Array.isArray(data?.data) ? data.data : []
      cacheSet(CACHE_KEY, rows.value)
      page.value = 1
    } catch (e) {
      error.value = e.response?.data?.messages || e.message || 'Error cargando préstamos.'
    } finally {
      loading.value = false
    }
  }

  function invalidate() {
    cacheDel(CACHE_KEY)
    cacheDel('loans_v2')
    cacheDel('loans')
  }

  async function reload() {
    invalidate()
    await load(true)
  }

  return {
    rows, loading, error, search, page, pageSize,
    filters, activeFilterCount, resetFilters,
    filtered, totalPages, pageRows,
    load, reload, invalidate
  }
})
