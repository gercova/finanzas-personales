import * as api from '@/api'
import { createEntityStore } from './createEntityStore'

export const useLenderStore = createEntityStore(
  'lender',
  'v3_lenders',
  () => api.fetchLenders(),
  {
    pageSize: 10,
    searchFields: ['lender_type', 'document_type', 'document_number', 'full_name', 'phone', 'email', 'address', 'notes'],
  }
)
