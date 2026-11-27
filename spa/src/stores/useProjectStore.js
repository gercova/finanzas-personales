import * as api from '@/api'
import { createEntityStore } from './createEntityStore'
import { matchesDateRange, matchesNumberRange } from '@/utils/filters'

export const useProjectStore = createEntityStore(
  'project',
  'v3_projects',
  () => api.fetchProjects(),
  {
    searchFields: ['project_name', 'client_name', 'budget', 'start_date', 'end_date', 'status'],
    defaultFilters: {
      client:    '',           // '', '__NONE__', 'name:<lowerClient>'
      status:    '',           // '', 'ACTIVE', 'ON_HOLD', 'COMPLETED'
      forecast:  '',           // '', 'LOW', 'MEDIUM', 'HIGH'
      dateField: 'start_date', // 'start_date' | 'end_date'
      dateFrom:  '',
      dateTo:    '',
      minBudget: '',
      maxBudget: '',
    },
    countFiltersFn(f) {
      let count = 0
      if (f.client) count++
      if (f.status) count++
      if (f.forecast) count++
      if (f.dateFrom || f.dateTo) count++
      if (f.minBudget !== '' && f.minBudget !== null) count++
      if (f.maxBudget !== '' && f.maxBudget !== null) count++
      return count
    },
    filterFn(row, f) {
      if (f.client === '__NONE__' && String(row.client_name || '').trim()) return false
      if (f.client && f.client !== '__NONE__') {
        const target = f.client.startsWith('name:') ? f.client.slice(5).toLowerCase() : f.client.toLowerCase()
        if (String(row.client_name || '').trim().toLowerCase() !== target) return false
      }
      if (f.status && row.status !== f.status) return false
      if (f.forecast) {
        const ratio = Number(row.forecast_ratio) || 0
        if (f.forecast === 'LOW' && ratio > 70) return false
        if (f.forecast === 'MEDIUM' && (ratio <= 70 || ratio > 95)) return false
        if (f.forecast === 'HIGH' && ratio <= 95) return false
      }
      const targetDate = f.dateField === 'end_date' ? row.end_date : row.start_date
      if (!matchesDateRange(targetDate, f.dateFrom, f.dateTo)) return false
      if (!matchesNumberRange(row.budget, f.minBudget, f.maxBudget)) return false
      return true
    },
  }
)
