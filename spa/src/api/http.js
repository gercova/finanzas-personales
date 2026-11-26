/**
 * Pure decoupled Axios instance for the backend REST JSON API.
 * Zero reliance on PHP-injected window globals or hard page reloads.
 */
import axios from 'axios'

let csrfToken = ''
let unauthorizedHandler = null

export function setCsrfToken(token) {
  csrfToken = token || ''
}

export function getCsrfToken() {
  return csrfToken
}

export function setUnauthorizedHandler(handler) {
  unauthorizedHandler = handler
}

export const getBaseURL = () => {
  if (typeof window === 'undefined') return '/'
  const p = window.location.pathname
  // Handle subdirectory installations like /personal-finance/ or /personal-finance/app.php
  const m = p.match(/^(.*\/)(?:[^/]+\.php|app(?:\/.*)?)$/)
  if (m && m[1]) {
    return m[1]
  }
  const pfIdx = p.indexOf('/personal-finance')
  if (pfIdx !== -1) {
    return p.slice(0, pfIdx) + '/personal-finance/'
  }
  return p.endsWith('/') ? p : p + '/'
}

const http = axios.create({
  baseURL: getBaseURL(),
  withCredentials: true,
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
    'Accept': 'application/json'
  }
})

// Attach reactive CSRF token to every mutating request
http.interceptors.request.use(config => {
  if (csrfToken && ['post', 'put', 'patch', 'delete'].includes(config.method?.toLowerCase())) {
    if (config.data instanceof FormData) {
      if (!config.data.has('csrf_token')) {
        config.data.set('csrf_token', csrfToken)
      }
    } else if (typeof config.data === 'string') {
      if (!config.data.includes('csrf_token=')) {
        config.data += (config.data ? '&' : '') + `csrf_token=${encodeURIComponent(csrfToken)}`
      }
    } else if (config.data instanceof URLSearchParams) {
      if (!config.data.has('csrf_token')) {
        config.data.append('csrf_token', csrfToken)
      }
    } else {
      config.data = { ...(config.data || {}), csrf_token: csrfToken }
    }
    config.headers['X-CSRF-Token'] = csrfToken
  }
  return config
})

// Handle 401 session expiration cleanly inside the SPA (no hard reload)
http.interceptors.response.use(
  r => r,
  err => {
    if (err.response?.status === 401 && typeof unauthorizedHandler === 'function') {
      const code = err.response?.data?.code || 'timeout'
      unauthorizedHandler(code)
    }
    return Promise.reject(err)
  }
)

export default http
