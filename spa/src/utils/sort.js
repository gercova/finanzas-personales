import { ref } from 'vue'

const ISO_DATE_RE = /^\d{4}-\d{2}-\d{2}(?:[ T]\d{2}:\d{2}(?::\d{2})?)?$/
const DMY_DATE_RE = /^(\d{2})\/(\d{2})\/(\d{4})$/
const NUM_RE = /^-?\d+(?:\.\d+)?$/

function isEmpty(v) {
  return v === null || v === undefined || String(v).trim() === ''
}

/**
 * Normalizes a cell value into a typed representation for accurate comparison.
 */
function normalizeValue(v) {
  if (typeof v === 'boolean') return { kind: 'num', val: v ? 1 : 0 }
  if (typeof v === 'number') return { kind: 'num', val: Number.isNaN(v) ? 0 : v }

  const s = String(v).trim()
  if (NUM_RE.test(s)) {
    return { kind: 'num', val: parseFloat(s) }
  }
  const dmy = s.match(DMY_DATE_RE)
  if (dmy) {
    return { kind: 'date', val: `${dmy[3]}-${dmy[2]}-${dmy[1]}` }
  }
  if (ISO_DATE_RE.test(s)) {
    return { kind: 'date', val: s.replace('T', ' ') }
  }
  return { kind: 'str', val: s }
}

/**
 * Compares two values in ascending or descending order, keeping empty values last.
 */
export function compareSortValues(a, b, dir = 'asc') {
  const aEmpty = isEmpty(a)
  const bEmpty = isEmpty(b)
  if (aEmpty && bEmpty) return 0
  if (aEmpty) return 1
  if (bEmpty) return -1

  const na = normalizeValue(a)
  const nb = normalizeValue(b)
  const factor = dir === 'desc' ? -1 : 1

  if (na.kind === 'num' && nb.kind === 'num') {
    return (na.val - nb.val) * factor
  }
  if (na.kind === 'date' && nb.kind === 'date') {
    return na.val.localeCompare(nb.val) * factor
  }
  return String(na.val).localeCompare(String(nb.val), 'es', {
    sensitivity: 'base',
    numeric: true,
  }) * factor
}

/**
 * Returns a new sorted array without mutating the source array.
 */
export function sortList(list, key, dir = 'asc', valueGetter = null) {
  if (!Array.isArray(list) || !key) return Array.isArray(list) ? list : []
  const getter = typeof valueGetter === 'function' ? valueGetter : (row) => row?.[key]
  return [...list].sort((a, b) => compareSortValues(getter(a), getter(b), dir))
}

/**
 * Reusable reactive sorting state for standalone tables outside DataTable.vue.
 */
export function useTableSort(initialKey = '', initialDir = 'asc', valueGetters = {}) {
  const sortKey = ref(initialKey)
  const sortDir = ref(initialDir)

  function toggleSort(key) {
    if (!key) return
    if (sortKey.value === key) {
      sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
    } else {
      sortKey.value = key
      sortDir.value = 'asc'
    }
  }

  function sortRows(rows) {
    if (!sortKey.value) return Array.isArray(rows) ? rows : []
    const getter = valueGetters[sortKey.value] || null
    return sortList(rows, sortKey.value, sortDir.value, getter)
  }

  return {
    sortKey,
    sortDir,
    toggleSort,
    sortRows,
  }
}
