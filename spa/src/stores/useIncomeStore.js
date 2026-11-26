import * as api from '@/api'
import { createEntityStore } from './createEntityStore'

export const useIncomeStore = createEntityStore(
  'income',
  'v3_incomes',
  () => api.fetchIncomes(),
  { searchFields: ['concept', 'account_name', 'category_name', 'project_name', 'income_date', 'amount', 'status', 'notes'] }
)
