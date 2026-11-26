<!--
  Reusable paginated table that renders HTML from the PHP API.
  Props:
    headers     – Array<string>
    store       – A pinia store with { pageRows, loading, error, search, page, pageSize, totalPages, filtered, reload }
    headerColor – optional string color for panel heading
    module      – string, used for import modal
-->
<template>
  <div>
    <!-- Panel heading -->
    <div class="panel-heading panel-heading-flex" :style="headingStyle">
      <div class="page-heading">
        <i v-if="icon" :class="'glyphicon ' + icon" style="margin-right:8px;"></i>
        {{ title }}
      </div>
      <div style="display:flex;gap:6px;align-items:center;">
        <slot name="actions" />
      </div>
    </div>

    <!-- Search + page-size row -->
    <div class="panel-body" style="padding-bottom:4px;">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:8px;">
        <div>
          <input class="form-control input-sm"
            style="width:220px;"
            type="search"
            placeholder="Buscar…"
            v-model="store.search"
            @input="store.page = 1" />
        </div>
        <small class="text-muted">
          {{ store.filtered.length }} registro{{ store.filtered.length !== 1 ? 's' : '' }}
        </small>
      </div>

      <!-- Error -->
      <div v-if="store.error" class="alert alert-danger">{{ store.error }}</div>

      <!-- Loading skeleton -->
      <div v-if="store.loading" class="text-center" style="padding:30px 0;">
        <i class="glyphicon glyphicon-refresh" style="font-size:28px;animation:spin 1s linear infinite;color:#aaa;"></i>
        <p class="text-muted" style="margin-top:8px;">Cargando…</p>
      </div>

      <!-- Table -->
      <div v-else class="table-responsive">
        <table class="table table-hover" style="width:100%;">
          <thead>
            <tr>
              <th v-for="h in headers" :key="h" v-html="h"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="store.pageRows.length === 0">
              <td :colspan="headers.length" class="text-center text-muted" style="padding:24px;">
                No hay registros que mostrar.
              </td>
            </tr>
            <tr v-for="(row, ri) in store.pageRows" :key="ri"
                :style="rowStyle(row)"
                class="spa-table-row">
              <td v-for="(cell, ci) in row" :key="ci" v-html="cell"></td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <AppPagination
        v-model="store.page"
        v-model:pageSize="store.pageSize"
        :total-pages="store.totalPages"
        :total-rows="store.filtered.length"
        @update:modelValue="store.page = $event"
        @update:pageSize="val => { store.pageSize = val; store.page = 1 }"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import AppPagination from './AppPagination.vue'

const props = defineProps({
  title:       { type: String, default: '' },
  icon:        { type: String, default: '' },
  headers:     { type: Array,  required: true },
  store:       { type: Object, required: true },
  headerColor: { type: String, default: '' },
})

const headingStyle = computed(() =>
  props.headerColor ? { background: props.headerColor, color: '#fff' } : {}
)

// Loans embed row styles as inline style strings on cell[0]
function rowStyle(row) {
  if (!row || !row[0]) return {}
  const m = String(row[0]).match(/style="([^"]+)"/)
  return m ? m[1].split(';').reduce((acc, s) => {
    const [k, v] = s.split(':')
    if (k && v) acc[k.trim()] = v.trim()
    return acc
  }, {}) : {}
}
</script>

<style>
@keyframes spin { to { transform: rotate(360deg); } }

/* Table row styling: Use background-color instead of filter to avoid breaking CSS stacking contexts */
.spa-table-row {
  transition: background .15s;
}
.spa-table-row:hover > td {
  background-color: rgba(0, 0, 0, 0.025);
}

/* Ensure table cells do not trap or clip dropdown menus */
.spa-table-row > td {
  position: static;
}
.spa-table-row > td:last-child {
  position: relative;
}

/* When a dropdown is open, elevate the row, cell, and button-group above subsequent rows */
.spa-table-row:has(.btn-group.open),
.spa-table-row:has(.open),
.spa-table-row.has-open-dropdown,
.spa-table-row.row-dropdown-open {
  position: relative !important;
  z-index: 1050 !important;
}

.spa-table-row:has(.btn-group.open) > td,
.spa-table-row:has(.open) > td,
.spa-table-row.has-open-dropdown > td,
.spa-table-row.row-dropdown-open > td {
  position: relative !important;
  z-index: 1050 !important;
}

.spa-table-row .btn-group {
  position: relative;
}

.spa-table-row .btn-group.open {
  position: relative !important;
  z-index: 1055 !important;
}

.spa-table-row .btn-group.open .dropdown-menu {
  z-index: 1060 !important;
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.22) !important;
}

/* Prevent clipping in table-responsive when dropdown is open */
.table-responsive:has(.open),
.table-responsive.has-open-dropdown {
  overflow: visible !important;
}
</style>
