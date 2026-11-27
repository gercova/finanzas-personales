import * as api from '@/api'
import { createEntityStore } from './createEntityStore'
import { matchesRefFilter, matchesDateRange, matchesNumberRange } from '@/utils/filters'

export const useExpenseStore = createEntityStore(
  'expense',
  'v3_expenses',
  () => api.fetchExpenses(),
  {
    searchFields: ['concept', 'account_name', 'category_name', 'expense_date', 'amount', 'status', 'notes'],
    defaultFilters: {
      accountId:     '',
      categoryId:    '',
      projectId:     '',
      status:        '', // '', 'PAGADO', 'PENDIENTE'
      receiptFilter: '', // '', 'WITH', 'NONE'
      dateFrom:      '',
      dateTo:        '',
      minAmount:     '',
      maxAmount:     '',
    },
    countFiltersFn(f) {
      let count = 0
      if (f.accountId) count++
      if (f.categoryId) count++
      if (f.projectId) count++
      if (f.status) count++
      if (f.receiptFilter) count++
      if (f.dateFrom || f.dateTo) count++
      if (f.minAmount !== '' && f.minAmount !== null) count++
      if (f.maxAmount !== '' && f.maxAmount !== null) count++
      return count
    },
    filterFn(row, f) {
      if (!matchesRefFilter(row.account_id, row.account_name, f.accountId)) return false
      if (!matchesRefFilter(row.category_id, row.category_name, f.categoryId)) return false
      if (f.projectId === '__NONE__' && row.project_id) return false
      if (f.projectId && f.projectId !== '__NONE__' && String(row.project_id || '') !== String(f.projectId)) return false
      if (f.status && row.status !== f.status) return false
      if (f.receiptFilter === 'WITH' && !row.receipt_image) return false
      if (f.receiptFilter === 'NONE' && row.receipt_image) return false
      if (!matchesDateRange(row.expense_date, f.dateFrom, f.dateTo)) return false
      if (!matchesNumberRange(row.amount, f.minAmount, f.maxAmount)) return false
      return true
    },
  }
)
