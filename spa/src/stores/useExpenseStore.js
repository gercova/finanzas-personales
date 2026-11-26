import * as api from '@/api'
import { createEntityStore } from './createEntityStore'

export const useExpenseStore = createEntityStore(
  'expense',
  'v3_expenses',
  () => api.fetchExpenses(),
  { searchFields: ['concept', 'account_name', 'category_name', 'expense_date', 'amount', 'status', 'notes'] }
)
