import * as api from '@/api'
import { createEntityStore } from './createEntityStore'

export const useBorrowerStore = createEntityStore(
  'borrower',
  'v3_borrowers',
  () => api.fetchBorrowers(),
  {
    pageSize: 10,
    searchFields: ['document_type', 'document_number', 'full_name', 'phone', 'email', 'address', 'notes'],
  }
)
