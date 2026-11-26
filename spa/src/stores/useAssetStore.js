import * as api from '@/api'
import { createEntityStore } from './createEntityStore'

export const useAssetStore = createEntityStore(
  'asset',
  'v3_assets',
  () => api.fetchAssets(),
  { searchFields: ['asset_name', 'category', 'estimated_value', 'purchase_price', 'purchase_date', 'document_number', 'location_or_custody', 'status', 'notes'] }
)
