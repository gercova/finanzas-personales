import { defineStore } from 'pinia'
import { ref, reactive, computed, watch } from 'vue'
import * as api from '@/api'
import { cacheGet, cacheSet, cacheDel } from './cache'

const CACHE_KEY = 'loans_v2'

/**
 * Normalizes a dd/mm/yyyy date string into YYYY-MM-DD for fallback comparison.
 */
function parseDisplayDate(str) {
  const m = String(str || '').trim().match(/^(\d{2})\/(\d{2})\/(\d{4})$/)
  return m ? `${m[3]}-${m[2]}-${m[1]}` : ''
}

function stripHtml(html) {
  return String(html || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim()
}

function parseAmount(cell) {
  const num = parseFloat(String(cell || '').replace(/[^0-9.-]/g, ''))
  return Number.isFinite(num) ? num : 0
}

/**
 * Fallback metadata builder when rows exist without server-side meta objects.
 */
function deriveMetaFromRow(r) {
  const statusText = stripHtml(r[11]).toUpperCase()
  const interestText = stripHtml(r[7])
  return {
    id: null,
    borrower_id: null,
    borrower_name: stripHtml(r[1]).split(/\s+(?:DNI|RUC|CE|PAS)/i)[0].trim(),
    document_type: '',
    document_number: '',
    concept: stripHtml(r[2]),
    notes: '',
    account_id: null,
    account_name: stripHtml(r[3]),
    loan_date: parseDisplayDate(r[4]),
    due_date: parseDisplayDate(r[5]),
    principal_amount: parseAmount(r[6]),
    interest_type: interestText === '-' || !interestText ? 'NONE' : 'PERCENTAGE',
    interest_value: 0,
    total_receivable: parseAmount(r[8]),
    amount_repaid: parseAmount(r[9]),
    balance_pending: parseAmount(r[10]),
    status: statusText,
  }
}

export const useLoanStore = defineStore('loan', () => {
  const rows     = ref([])
  const meta     = ref([])
  const loading  = ref(false)
  const error    = ref(null)
  const search   = ref('')
  const page     = ref(1)
  const pageSize = ref(15)

  const filters = reactive({
    status: '',          // '', 'PENDIENTE', 'PARCIAL', 'PAGADO', 'UNPAID', 'OVERDUE'
    borrower: '',        // '', 'id:<id>', 'name:<lowerName>'
    accountId: '',       // '', '<id>', 'name:<lowerName>'
    dateField: 'loan_date', // 'loan_date' | 'due_date'
    dateFrom: '',        // 'YYYY-MM-DD'
    dateTo: '',          // 'YYYY-MM-DD'
    interestFilter: '',  // '', 'WITH', 'NONE'
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

  const filteredEntries = computed(() => {
    const q = search.value.trim().toLowerCase()
    const todayISO = new Date().toLocaleDateString('en-CA') // YYYY-MM-DD local

    const minAmt = filters.minAmount !== '' && filters.minAmount !== null ? parseFloat(filters.minAmount) : null
    const maxAmt = filters.maxAmount !== '' && filters.maxAmount !== null ? parseFloat(filters.maxAmount) : null

    const out = []
    for (let i = 0; i < rows.value.length; i++) {
      const r = rows.value[i]
      const m = meta.value[i] || deriveMetaFromRow(r)

      // 1. Status filter
      if (filters.status) {
        if (filters.status === 'UNPAID') {
          if (m.status !== 'PENDIENTE' && m.status !== 'PARCIAL') continue
        } else if (filters.status === 'OVERDUE') {
          if (m.status === 'PAGADO' || !m.due_date || m.due_date >= todayISO) continue
        } else if (m.status !== filters.status) {
          continue
        }
      }

      // 2. Borrower filter ('id:123' or 'name:foo')
      if (filters.borrower) {
        if (filters.borrower.startsWith('id:')) {
          const [idPart, ...nameParts] = filters.borrower.slice(3).split('|')
          const targetId = parseInt(idPart, 10)
          const targetName = nameParts.join('|').toLowerCase()
          const idMatch = m.borrower_id !== null && m.borrower_id === targetId
          const fallbackNameMatch = !m.borrower_id && targetName && m.borrower_name.toLowerCase() === targetName
          if (!idMatch && !fallbackNameMatch) continue
        } else if (filters.borrower.startsWith('name:')) {
          const targetName = filters.borrower.slice(5).toLowerCase()
          if (m.borrower_name.toLowerCase() !== targetName) continue
        }
      }

      // 3. Source Account filter
      if (filters.accountId) {
        if (filters.accountId.startsWith('name:')) {
          const targetAccName = filters.accountId.slice(5).toLowerCase()
          if (m.account_name.toLowerCase() !== targetAccName) continue
        } else {
          if (String(m.account_id || '') !== String(filters.accountId)) continue
        }
      }

      // 4. Date range filter (Loan date or Due date)
      if (filters.dateFrom || filters.dateTo) {
        const targetDate = filters.dateField === 'due_date' ? m.due_date : m.loan_date
        if (!targetDate) continue
        if (filters.dateFrom && targetDate < filters.dateFrom) continue
        if (filters.dateTo && targetDate > filters.dateTo) continue
      }

      // 5. Interest filter
      if (filters.interestFilter) {
        const hasInterest = m.interest_type !== 'NONE' && m.interest_type !== 'NINGUNO'
        if (filters.interestFilter === 'WITH' && !hasInterest) continue
        if (filters.interestFilter === 'NONE' && hasInterest) continue
      }

      // 6. Capital amount range filter
      if (minAmt !== null && !Number.isNaN(minAmt) && m.principal_amount < minAmt) continue
      if (maxAmt !== null && !Number.isNaN(maxAmt) && m.principal_amount > maxAmt) continue

      // 7. Free-text search (visible row text + notes/doc)
      if (q) {
        const rowMatch = r.some((cell, ci) => ci < 12 && stripHtml(cell).toLowerCase().includes(q))
        const metaMatch = (m.notes && m.notes.toLowerCase().includes(q)) ||
                          (m.document_number && m.document_number.toLowerCase().includes(q))
        if (!rowMatch && !metaMatch) continue
      }

      out.push({ row: r, meta: m })
    }
    return out
  })

  const filtered     = computed(() => filteredEntries.value.map(e => e.row))
  const filteredMeta = computed(() => filteredEntries.value.map(e => e.meta))

  const totalPages = computed(() => Math.max(1, Math.ceil(filtered.value.length / pageSize.value)))
  const pageRows   = computed(() => {
    const safePage = Math.min(page.value, totalPages.value)
    const s = (safePage - 1) * pageSize.value
    return filtered.value.slice(s, s + pageSize.value)
  })

  async function load(force = false) {
    if (!force) {
      const c = cacheGet(CACHE_KEY)
      if (c && Array.isArray(c.rows)) {
        rows.value = c.rows
        meta.value = Array.isArray(c.meta) ? c.meta : []
        return
      }
    }
    loading.value = true; error.value = null
    try {
      const { data } = await api.fetchLoans()
      rows.value = data.data ?? []
      meta.value = data.meta ?? []
      cacheSet(CACHE_KEY, { rows: rows.value, meta: meta.value })
      page.value = 1
    } catch (e) { error.value = e.message }
    finally { loading.value = false }
  }

  function invalidate() {
    cacheDel(CACHE_KEY)
    cacheDel('loans')
  }
  async function reload() { invalidate(); await load(true) }

  return {
    rows, meta, loading, error, search, page, pageSize,
    filters, activeFilterCount, resetFilters,
    filtered, filteredMeta, totalPages, pageRows,
    load, reload, invalidate
  }
})
