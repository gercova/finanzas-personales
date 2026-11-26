import * as api from '@/api'
import { createEntityStore } from './createEntityStore'

export const useAccountStore = createEntityStore(
  'account',
  'v3_accounts',
  () => api.fetchAccounts(),
  { searchFields: ['account_name', 'account_type', 'account_number', 'currency', 'initial_balance', 'current_balance'] }
)
