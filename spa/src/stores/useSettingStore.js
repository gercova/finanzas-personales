import { defineStore } from 'pinia'
import { ref } from 'vue'
import * as api from '@/api'
import { cacheGet, cacheSet, cacheDel } from './cache'

const CACHE_KEY = 'settings_data'

export const useSettingStore = defineStore('setting', () => {
  const user    = ref(null)
  const quota   = ref({
    month_year: '',
    monthly_limit: 4000,
    warning_threshold: 75,
    critical_threshold: 90,
    spent_this_month: 0,
    pct_spent: 0,
    bar_type: 'success'
  })
  const loading = ref(false)
  const saving  = ref(false)
  const error   = ref(null)

  async function load(force = false) {
    if (!force) {
      const cached = cacheGet(CACHE_KEY)
      if (cached) {
        user.value  = cached.user
        quota.value = cached.quota
        return
      }
    }

    loading.value = true
    error.value   = null
    try {
      const { data } = await api.getSettingsData()
      if (data.success) {
        user.value  = data.user
        quota.value = data.quota
        cacheSet(CACHE_KEY, { user: data.user, quota: data.quota }, 60_000)
      } else {
        error.value = data.messages || 'Error al cargar configuración.'
      }
    } catch (e) {
      error.value = e.message
    } finally {
      loading.value = false
    }
  }

  function invalidate() {
    cacheDel(CACHE_KEY)
  }

  async function reload() {
    invalidate()
    await load(true)
  }

  return {
    user,
    quota,
    loading,
    saving,
    error,
    load,
    reload,
    invalidate
  }
})
