/**
 * Formats a Date object as YYYY-MM-DD using local calendar fields.
 */
export function toISODate(d) {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

/**
 * Returns { dateFrom, dateTo } ISO bounds for common quick presets.
 */
export function getDatePresetRange(preset) {
  const now = new Date()
  if (preset === 'today') {
    const iso = toISODate(now)
    return { dateFrom: iso, dateTo: iso }
  }
  if (preset === 'this_month') {
    return {
      dateFrom: toISODate(new Date(now.getFullYear(), now.getMonth(), 1)),
      dateTo: toISODate(new Date(now.getFullYear(), now.getMonth() + 1, 0)),
    }
  }
  if (preset === 'last_month') {
    return {
      dateFrom: toISODate(new Date(now.getFullYear(), now.getMonth() - 1, 1)),
      dateTo: toISODate(new Date(now.getFullYear(), now.getMonth(), 0)),
    }
  }
  if (preset === 'this_year') {
    return {
      dateFrom: `${now.getFullYear()}-01-01`,
      dateTo: `${now.getFullYear()}-12-31`,
    }
  }
  return { dateFrom: '', dateTo: '' }
}

/**
 * Matches a relational entity field (account, category, project) against a filter value.
 * Supports '', '__NONE__', 'name:<lowercaseName>', and exact numeric string IDs.
 */
export function matchesRefFilter(rowId, rowName, filterVal) {
  if (!filterVal) return true
  if (filterVal === '__NONE__') {
    return !rowId && !String(rowName || '').trim()
  }
  if (String(filterVal).startsWith('name:')) {
    const targetName = String(filterVal).slice(5).toLowerCase()
    return String(rowName || '').trim().toLowerCase() === targetName
  }
  return String(rowId || '') === String(filterVal)
}

/**
 * Checks whether an ISO YYYY-MM-DD date falls within inclusive [dateFrom, dateTo] bounds.
 */
export function matchesDateRange(targetDate, dateFrom, dateTo) {
  if (!dateFrom && !dateTo) return true
  if (!targetDate) return false
  if (dateFrom && targetDate < dateFrom) return false
  if (dateTo && targetDate > dateTo) return false
  return true
}

/**
 * Checks whether a numeric amount falls within optional [minVal, maxVal] bounds.
 */
export function matchesNumberRange(val, minRaw, maxRaw) {
  const hasMin = minRaw !== '' && minRaw !== null && minRaw !== undefined
  const hasMax = maxRaw !== '' && maxRaw !== null && maxRaw !== undefined
  if (!hasMin && !hasMax) return true
  const num = Number(val) || 0
  if (hasMin) {
    const min = parseFloat(minRaw)
    if (!Number.isNaN(min) && num < min) return false
  }
  if (hasMax) {
    const max = parseFloat(maxRaw)
    if (!Number.isNaN(max) && num > max) return false
  }
  return true
}

/**
 * Builds deduplicated, locale-sorted { value, label } filter options from form options + existing rows.
 */
export function buildRefOptions(formItems, rows, {
  formIdKey = 'id',
  formNameKey = 'name',
  rowIdKey = 'id',
  rowNameKey = 'name',
} = {}) {
  const list = []
  const seenIds = new Set()
  const seenNames = new Set()

  for (const item of (formItems || [])) {
    const id = String(item?.[formIdKey] ?? '')
    const name = String(item?.[formNameKey] ?? '').trim()
    if (!id || !name) continue
    seenIds.add(id)
    seenNames.add(name.toLowerCase())
    list.push({ value: id, label: name })
  }

  for (const row of (rows || [])) {
    const rawName = String(row?.[rowNameKey] ?? '').trim()
    if (!rawName || rawName === '-') continue
    const rid = row?.[rowIdKey]
    if (rid && !seenIds.has(String(rid))) {
      seenIds.add(String(rid))
      seenNames.add(rawName.toLowerCase())
      list.push({ value: String(rid), label: rawName })
    } else if (!rid && !seenNames.has(rawName.toLowerCase())) {
      seenNames.add(rawName.toLowerCase())
      list.push({ value: `name:${rawName.toLowerCase()}`, label: rawName })
    }
  }

  return list.sort((a, b) => a.label.localeCompare(b.label, 'es'))
}
