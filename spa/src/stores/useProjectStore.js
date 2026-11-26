import * as api from '@/api'
import { createEntityStore } from './createEntityStore'

export const useProjectStore = createEntityStore(
  'project',
  'v3_projects',
  () => api.fetchProjects(),
  { searchFields: ['project_name', 'client_name', 'budget', 'start_date', 'end_date', 'status'] }
)
