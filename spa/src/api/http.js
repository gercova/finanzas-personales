/**
 * Axios instance pre-configured for the PHP backend.
 * Automatically determines the base URL (handling subdirectories like /personal-finance/)
 * and sends the CSRF token on mutating requests.
 */
import axios from 'axios'

export const getBaseURL = () => {
  if (typeof window !== 'undefined' && window.__BASE_URL__) {
    return window.__BASE_URL__
  }
  if (typeof window !== 'undefined') {
    const p = window.location.pathname
    const idx = p.indexOf('/personal-finance')
    if (idx !== -1) {
      return '/personal-finance/'
    }
  }
  return '/'
}

const http = axios.create({
  baseURL: getBaseURL(),
  withCredentials: true,
  headers: { 'X-Requested-With': 'XMLHttpRequest' }
})

// Attach CSRF token to every mutating request
http.interceptors.request.use(config => {
  const token = typeof window !== 'undefined' ? (window.systemCsrfToken || '') : ''
  if (token && ['post', 'put', 'patch', 'delete'].includes(config.method?.toLowerCase())) {
    if (config.data instanceof FormData) {
      if (!config.data.has('csrf_token')) {
        config.data.set('csrf_token', token)
      }
    } else if (typeof config.data === 'string') {
      if (!config.data.includes('csrf_token=')) {
        config.data += (config.data ? '&' : '') + `csrf_token=${encodeURIComponent(token)}`
      }
    } else if (config.data instanceof URLSearchParams) {
      if (!config.data.has('csrf_token')) {
        config.data.append('csrf_token', token)
      }
    } else {
      config.data = { ...(config.data || {}), csrf_token: token }
    }
    config.headers['X-CSRF-Token'] = token
  }
  return config
})

// On 401 (session expired) redirect to login
http.interceptors.response.use(
  r => r,
  err => {
    if (err.response?.status === 401) {
      const base = getBaseURL()
      window.location.href = base + 'index.php?error=timeout'
    }
    return Promise.reject(err)
  }
)

export default http
