import { defineStore } from 'pinia'
import { ref, reactive, computed, watch } from 'vue'
import { cacheGet, cacheSet, cacheDel } from './cache'
import { sortList } from '@/utils/sort'

/**
 * Formats ISO date YYYY-MM-DD as dd/mm/yyyy so free-text search matches both formats.
 */
function formatValueForSearch(val) {
  if (val === null || val === undefined) return ''
  const s = String(val)
  const m = s.match(/^(\d{4})-(\d{2})-(\d{2})$/)
  if (m) return `${s} ${m[3]}/${m[2]}/${m[1]}`
  return s
}

/**
 * Factory for clean, DRY JSON entity table stores backed by Pinia + TTL cache.
 */
export function createEntityStore(storeId, cacheKey, fetcher, options = {}) {
  const defaultPageSize = options.pageSize || 15
  const searchFields    = options.searchFields || null
  const defaultFilters  = options.defaultFilters || null
  const filterFn        = typeof options.filterFn === 'function' ? options.filterFn : null
  const countFiltersFn  = typeof options.countFiltersFn === 'function' ? options.countFiltersFn : null

  return defineStore(storeId, () => {
    const rows     = ref([])
    const loading  = ref(false)
    const error    = ref(null)
    const search   = ref('')
    const page     = ref(1)
    const pageSize = ref(defaultPageSize)
    const sortKey  = ref('')
    const sortDir  = ref('asc')
    let sortGetter = null

    const filters = defaultFilters ? reactive({ ...defaultFilters }) : null

    if (filters) {
      watch(filters, () => {
        page.value = 1
      }, { deep: true })
    }

    const activeFilterCount = computed(() => {
      if (!filters) return 0
      if (countFiltersFn) return countFiltersFn(filters)
      let count = 0
      for (const [k, v] of Object.entries(filters)) {
        if (k === 'dateField') continue
        if (v !== '' && v !== null && v !== undefined) count++
      }
      return count
    })

    function resetFilters() {
      if (filters && defaultFilters) {
        Object.keys(defaultFilters).forEach(k => {
          filters[k] = defaultFilters[k]
        })
      }
      search.value = ''
      page.value   = 1
    }

    function setSort(key, valueGetter = null) {
      if (!key) return
      if (sortKey.value === key) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
      } else {
        sortKey.value = key
        sortDir.value = 'asc'
      }
      sortGetter = typeof valueGetter === 'function' ? valueGetter : null
      page.value = 1
    }

    const filtered = computed(() => {
      const q = search.value.trim().toLowerCase()
      return rows.value.filter(row => {
        if (filterFn && filters && !filterFn(row, filters)) {
          return false
        }
        if (!q) return true
        const vals = searchFields
          ? searchFields.map(k => row[k])
          : Object.values(row)
        return vals.some(v => formatValueForSearch(v).toLowerCase().includes(q))
      })
    })

    const sorted = computed(() => {
      if (!sortKey.value) return filtered.value
      return sortList(filtered.value, sortKey.value, sortDir.value, sortGetter)
    })

    const totalPages = computed(() => Math.max(1, Math.ceil(filtered.value.length / pageSize.value)))

    const pageRows = computed(() => {
      const safePage = Math.min(page.value, totalPages.value)
      const start    = (safePage - 1) * pageSize.value
      return sorted.value.slice(start, start + pageSize.value)
    })

    async function load(force = false) {
      if (!force) {
        const cached = cacheGet(cacheKey)
        if (Array.isArray(cached)) {
          rows.value = cached
          return
        }
      }
      loading.value = true
      error.value   = null
      try {
        const { data } = await fetcher()
        if (data?.success === false) {
          rows.value  = []
          error.value = data.messages || 'Error cargando datos.'
          return
        }
        rows.value = Array.isArray(data?.data) ? data.data : []
        cacheSet(cacheKey, rows.value)
        page.value = 1
      } catch (e) {
        error.value = e.response?.data?.messages || e.message || 'Error cargando datos.'
      } finally {
        loading.value = false
      }
    }

    function invalidate() {
      cacheDel(cacheKey)
    }

    async function reload() {
      invalidate()
      await load(true)
    }

    return {
      rows, loading, error, search, page, pageSize,
      sortKey, sortDir, setSort,
      ...(filters ? { filters, activeFilterCount, resetFilters } : {}),
      filtered, totalPages, pageRows, load, reload, invalidate
    }
  })
}
