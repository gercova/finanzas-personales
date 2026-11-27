import * as api from '@/api'
import { createEntityStore } from './createEntityStore'
import { matchesRefFilter, matchesDateRange, matchesNumberRange } from '@/utils/filters'

function normalizeIncomeStatus(s) {
  if (s === 'CONFIRMED' || s === 'ACTIVO') return 'CONFIRMED'
  if (s === 'PENDING' || s === 'PENDIENTE') return 'PENDING'
  if (s === 'CANCELLED' || s === 'CANCELADO') return 'CANCELLED'
  return s || ''
}

export const useIncomeStore = createEntityStore(
  'income',
  'v3_incomes',
  () => api.fetchIncomes(),
  {
    searchFields: ['concept', 'account_name', 'category_name', 'project_name', 'income_date', 'amount', 'status', 'notes'],
    defaultFilters: {
      accountId:  '',
      categoryId: '',
      projectId:  '',
      status:     '', // '', 'CONFIRMED', 'PENDING', 'CANCELLED'
      recurring:  '', // '', 'YES', 'NO'
      dateFrom:   '',
      dateTo:     '',
      minAmount:  '',
      maxAmount:  '',
    },
    countFiltersFn(f) {
      let count = 0
      if (f.accountId) count++
      if (f.categoryId) count++
      if (f.projectId) count++
      if (f.status) count++
      if (f.recurring) count++
      if (f.dateFrom || f.dateTo) count++
      if (f.minAmount !== '' && f.minAmount !== null) count++
      if (f.maxAmount !== '' && f.maxAmount !== null) count++
      return count
    },
    filterFn(row, f) {
      if (!matchesRefFilter(row.account_id, row.account_name, f.accountId)) return false
      if (!matchesRefFilter(row.category_id, row.category_name, f.categoryId)) return false
      if (!matchesRefFilter(row.project_id, row.project_name, f.projectId)) return false
      if (f.status && normalizeIncomeStatus(row.status) !== f.status) return false
      if (f.recurring === 'YES' && !row.is_recurring) return false
      if (f.recurring === 'NO' && row.is_recurring) return false
      if (!matchesDateRange(row.income_date, f.dateFrom, f.dateTo)) return false
      if (!matchesNumberRange(row.amount, f.minAmount, f.maxAmount)) return false
      return true
    },
  }
)
