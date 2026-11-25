/**
 * TTL-based localStorage cache.
 * Keys are prefixed so they don't clash with other apps.
 */
const PREFIX = 'myfin_'
const DEFAULT_TTL = 60_000 // 60 seconds

export function cacheGet(key) {
  try {
    const raw = localStorage.getItem(PREFIX + key)
    if (!raw) return null
    const { data, exp } = JSON.parse(raw)
    if (Date.now() > exp) { localStorage.removeItem(PREFIX + key); return null }
    return data
  } catch { return null }
}

export function cacheSet(key, data, ttl = DEFAULT_TTL) {
  try {
    localStorage.setItem(PREFIX + key, JSON.stringify({ data, exp: Date.now() + ttl }))
  } catch { /* storage full — silently skip */ }
}

export function cacheDel(key) {
  localStorage.removeItem(PREFIX + key)
}

export function cacheDelAll() {
  Object.keys(localStorage)
    .filter(k => k.startsWith(PREFIX))
    .forEach(k => localStorage.removeItem(k))
}
