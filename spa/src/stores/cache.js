/**
 * High-performance TTL-based cache with dual-tier storage:
 * Fast in-memory cache + persistent localStorage fallback.
 */
const PREFIX = 'myfin_'
const DEFAULT_TTL = 60_000 // 60 seconds

// In-memory hot cache
const memoryCache = new Map()

export function cacheGet(key) {
  const fullKey = PREFIX + key
  const now = Date.now()

  // 1. Check in-memory
  if (memoryCache.has(fullKey)) {
    const entry = memoryCache.get(fullKey)
    if (now < entry.exp) {
      return entry.data
    }
    memoryCache.delete(fullKey)
  }

  // 2. Check localStorage
  try {
    const raw = localStorage.getItem(fullKey)
    if (!raw) return null
    const { data, exp } = JSON.parse(raw)
    if (now > exp) {
      localStorage.removeItem(fullKey)
      return null
    }
    // Populate in-memory cache for faster subsequent reads
    memoryCache.set(fullKey, { data, exp })
    return data
  } catch {
    return null
  }
}

export function cacheSet(key, data, ttl = DEFAULT_TTL) {
  const fullKey = PREFIX + key
  const exp = Date.now() + ttl
  memoryCache.set(fullKey, { data, exp })
  try {
    localStorage.setItem(fullKey, JSON.stringify({ data, exp }))
  } catch {
    // quota exceeded or private mode, silently continue with memoryCache
  }
}

export function cacheDel(key) {
  const fullKey = PREFIX + key
  memoryCache.delete(fullKey)
  try {
    localStorage.removeItem(fullKey)
  } catch {}
}

export function cacheInvalidateKeys(...keys) {
  keys.forEach(k => cacheDel(k))
}

export function cacheDelAll() {
  memoryCache.clear()
  try {
    Object.keys(localStorage)
      .filter(k => k.startsWith(PREFIX))
      .forEach(k => localStorage.removeItem(k))
  } catch {}
}
