import { defineStore } from 'pinia'
import { ref, reactive, computed } from 'vue'
import * as api from '@/api'
import { setCsrfToken } from '@/api/http'
import { cacheDelAll } from './cache'

export const useAuthStore = defineStore('auth', () => {
  const authenticated     = ref(false)
  const sessionChecked    = ref(false)
  const appName           = ref('myFin')
  const allowRegistration = ref(true)
  const user              = ref(null)
  const sessionReason     = ref(null)
  const headerMetrics     = reactive({
    month_income: 0,
    today_expenses: 0,
  })

  const isAdmin = computed(() => {
    if (!authenticated.value || !user.value) return false
    const role = String(user.value.role || '').toUpperCase()
    const uname = String(user.value.username || '').toLowerCase()
    return role === 'ADMIN' || uname === 'admin'
  })

  let pendingSessionPromise = null

  async function ensureSession(force = false) {
    if (sessionChecked.value && !force) return authenticated.value
    if (pendingSessionPromise && !force) return pendingSessionPromise

    pendingSessionPromise = (async () => {
      try {
        const { data } = await api.getSession()
        if (data?.csrf_token) setCsrfToken(data.csrf_token)
        if (data?.app_name)   appName.value = data.app_name
        if (typeof data?.allow_registration === 'boolean') {
          allowRegistration.value = data.allow_registration
        }
        authenticated.value = Boolean(data?.authenticated)
        user.value          = data?.user || null
        sessionReason.value = data?.reason || null
        if (data?.header_metrics) {
          headerMetrics.month_income   = Number(data.header_metrics.month_income || 0)
          headerMetrics.today_expenses = Number(data.header_metrics.today_expenses || 0)
        } else {
          headerMetrics.month_income   = 0
          headerMetrics.today_expenses = 0
        }
      } catch {
        authenticated.value = false
        user.value = null
      } finally {
        sessionChecked.value = true
        pendingSessionPromise = null
      }
      return authenticated.value
    })()

    return pendingSessionPromise
  }

  async function login(username, password) {
    const fd = new FormData()
    fd.append('username', username)
    fd.append('password', password)

    const { data } = await api.login(fd)
    if (data?.csrf_token) setCsrfToken(data.csrf_token)

    if (data?.success) {
      cacheDelAll()
      authenticated.value  = true
      sessionChecked.value = true
      sessionReason.value  = null
      user.value           = data.user || null
      if (data.header_metrics) {
        headerMetrics.month_income   = Number(data.header_metrics.month_income || 0)
        headerMetrics.today_expenses = Number(data.header_metrics.today_expenses || 0)
      }
    }
    return data
  }

  async function register(payload) {
    const fd = new FormData()
    fd.append('full_name', payload.full_name)
    fd.append('username', payload.username)
    fd.append('email', payload.email || '')
    fd.append('password', payload.password)
    fd.append('confirm_password', payload.confirm_password)

    const { data } = await api.register(fd)
    if (data?.csrf_token) setCsrfToken(data.csrf_token)
    return data
  }

  async function logout() {
    try {
      const { data } = await api.logout()
      if (data?.csrf_token) setCsrfToken(data.csrf_token)
    } catch {
      // Ignore network errors during logout cleanup
    } finally {
      clearSessionState()
    }
  }

  function clearSessionState(reason = null) {
    cacheDelAll()
    authenticated.value        = false
    user.value                 = null
    sessionReason.value        = reason
    headerMetrics.month_income   = 0
    headerMetrics.today_expenses = 0
  }

  async function refreshHeaderMetrics() {
    if (!authenticated.value) return
    try {
      const { data } = await api.getHeaderMetrics()
      if (data?.success) {
        headerMetrics.month_income   = Number(data.month_income || 0)
        headerMetrics.today_expenses = Number(data.today_expenses || 0)
      }
    } catch {
      // Ignore transient errors
    }
  }

  function setUsername(newUsername) {
    if (user.value) {
      user.value = { ...user.value, username: newUsername }
    }
  }

  function setAppName(newName) {
    if (newName) appName.value = newName
  }

  return {
    authenticated,
    sessionChecked,
    appName,
    allowRegistration,
    isAdmin,
    user,
    sessionReason,
    headerMetrics,
    ensureSession,
    login,
    register,
    logout,
    clearSessionState,
    refreshHeaderMetrics,
    setUsername,
    setAppName,
  }
})
