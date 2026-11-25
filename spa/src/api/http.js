/**
 * Axios instance pre-configured for the PHP backend.
 * Automatically sends the CSRF token that PHP set on window.systemCsrfToken.
 */
import axios from 'axios'

const http = axios.create({
  baseURL: '/',          // PHP files live at the domain root
  withCredentials: true, // send the PHP session cookie
  headers: { 'X-Requested-With': 'XMLHttpRequest' }
})

// Attach CSRF token to every mutating request
http.interceptors.request.use(config => {
  const token = window.systemCsrfToken
  if (token && ['post', 'put', 'patch', 'delete'].includes(config.method)) {
    if (config.data instanceof FormData) {
      config.data.set('csrf_token', token)
    } else if (typeof config.data === 'string') {
      config.data += (config.data ? '&' : '') + `csrf_token=${encodeURIComponent(token)}`
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
      window.location.href = '/index.php?error=timeout'
    }
    return Promise.reject(err)
  }
)

export default http
