import * as api from '@/api'
import { createEntityStore } from './createEntityStore'
import { matchesDateRange, matchesNumberRange } from '@/utils/filters'

export const useAssetStore = createEntityStore(
  'asset',
  'v3_assets',
  () => api.fetchAssets(),
  {
    searchFields: ['asset_name', 'category', 'estimated_value', 'purchase_price', 'purchase_date', 'document_number', 'location_or_custody', 'status', 'notes'],
    defaultFilters: {
      category: '', // '', 'VEHICULO', 'INMUEBLE', 'DOCUMENTO_FIDUCIARIO', 'EQUIPO_TECNOLOGICO', 'OTRO'
      status:   '', // '', 'ACTIVO', 'EN_GARANTIA', 'VENDIDO', 'DADO_DE_BAJA'
      location: '', // '', '__NONE__', 'name:<lowerLoc>'
      hasImage: '', // '', 'WITH', 'NONE'
      dateFrom: '',
      dateTo:   '',
      minValue: '',
      maxValue: '',
    },
    countFiltersFn(f) {
      let count = 0
      if (f.category) count++
      if (f.status) count++
      if (f.location) count++
      if (f.hasImage) count++
      if (f.dateFrom || f.dateTo) count++
      if (f.minValue !== '' && f.minValue !== null) count++
      if (f.maxValue !== '' && f.maxValue !== null) count++
      return count
    },
    filterFn(row, f) {
      if (f.category && row.category !== f.category) return false
      if (f.status && row.status !== f.status) return false
      if (f.location === '__NONE__' && String(row.location_or_custody || '').trim()) return false
      if (f.location && f.location !== '__NONE__') {
        const target = f.location.startsWith('name:') ? f.location.slice(5).toLowerCase() : f.location.toLowerCase()
        if (String(row.location_or_custody || '').trim().toLowerCase() !== target) return false
      }
      if (f.hasImage === 'WITH' && !row.image_path) return false
      if (f.hasImage === 'NONE' && row.image_path) return false
      if (!matchesDateRange(row.purchase_date, f.dateFrom, f.dateTo)) return false
      if (!matchesNumberRange(row.estimated_value, f.minValue, f.maxValue)) return false
      return true
    },
  }
)
