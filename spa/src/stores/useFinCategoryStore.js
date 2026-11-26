import * as api from '@/api'
import { createEntityStore } from './createEntityStore'

export const useFinCategoryStore = createEntityStore(
  'finCategory',
  'v3_fin_categories',
  () => api.fetchFinCategories(),
  { searchFields: ['category_name', 'category_type'] }
)
