import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import * as api from '@/api'
import { cacheGet, cacheSet, cacheDel } from './cache'

const CACHE_KEY = 'projects'

export const useProjectStore = defineStore('project', () => {
  const rows     = ref([])
  const loading  = ref(false)
  const error    = ref(null)
  const search   = ref('')
  const page     = ref(1)
  const pageSize = ref(15)

  const filtered   = computed(() => {
    const q = search.value.toLowerCase()
    if (!q) return rows.value
    return rows.value.filter(r => r.some(cell => String(cell).toLowerCase().includes(q)))
  })
  const totalPages = computed(() => Math.max(1, Math.ceil(filtered.value.length / pageSize.value)))
  const pageRows   = computed(() => {
    const s = (page.value - 1) * pageSize.value
    return filtered.value.slice(s, s + pageSize.value)
  })

  async function load(force = false) {
    if (!force) { const c = cacheGet(CACHE_KEY); if (c) { rows.value = c; return } }
    loading.value = true; error.value = null
    try {
      const { data } = await api.fetchProjects()
      rows.value = data.data ?? []; cacheSet(CACHE_KEY, rows.value); page.value = 1
    } catch (e) { error.value = e.message }
    finally { loading.value = false }
  }

  function invalidate() { cacheDel(CACHE_KEY) }
  async function reload() { invalidate(); await load(true) }

  return { rows, loading, error, search, page, pageSize, filtered, totalPages, pageRows, load, reload, invalidate }
})
