<!--
  Pure Vue 3 reusable paginated table over structured JSON objects.
  Props:
    title       – Panel heading title
    icon        – Optional glyphicon class
    columns     – Array<{ key: string, label: string, align?: string, cellClass?: string, style?: object }>
    store       – Pinia store with { pageRows, loading, error, search, page, pageSize, totalPages, filtered }
    headerColor – Optional background color for panel heading
    rowStyleFn  – Optional (row) => styleObject
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
      <slot name="filters" />
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:8px;">
        <div>
          <input
            class="form-control input-sm"
            style="width:220px;"
            type="search"
            placeholder="Buscar…"
            v-model="store.search"
            @input="store.page = 1"
          />
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
              <SortableTh
                v-for="col in columns"
                :key="col.key"
                :label="col.label"
                :sort-key="col.key"
                :active-key="store.sortKey || ''"
                :sort-dir="store.sortDir || 'asc'"
                :align="col.align || ''"
                :th-style="col.thStyle"
                :sortable="isSortable(col)"
                @sort="onSort(col)"
              />
            </tr>
          </thead>
          <tbody>
            <tr v-if="store.pageRows.length === 0">
              <td :colspan="columns.length" class="text-center text-muted" style="padding:24px;">
                No hay registros que mostrar.
              </td>
            </tr>
            <tr
              v-for="(row, ri) in store.pageRows"
              :key="row.id ?? ri"
              :style="rowStyleFn ? rowStyleFn(row) : undefined"
              class="spa-table-row"
            >
              <td
                v-for="col in columns"
                :key="col.key"
                :class="[col.cellClass, col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : '']"
                :style="col.tdStyle"
              >
                <slot
                  :name="'cell-' + col.key"
                  :row="row"
                  :value="row[col.key]"
                  :index="(store.page - 1) * store.pageSize + ri + 1"
                >
                  {{ col.key === '_index' ? (store.page - 1) * store.pageSize + ri + 1 : (row[col.key] ?? '—') }}
                </slot>
              </td>
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
import SortableTh    from './SortableTh.vue'

const props = defineProps({
  title:       { type: String,   default: '' },
  icon:        { type: String,   default: '' },
  columns:     { type: Array,    required: true },
  store:       { type: Object,   required: true },
  headerColor: { type: String,   default: '' },
  rowStyleFn:  { type: Function, default: null },
})

const headingStyle = computed(() =>
  props.headerColor ? { background: props.headerColor, color: '#fff' } : {}
)

function isSortable(col) {
  return col.sortable !== false && col.key !== '_index' && col.key !== 'actions' && typeof props.store?.setSort === 'function'
}

function onSort(col) {
  if (!isSortable(col)) return
  props.store.setSort(col.key, col.sortValue)
}
</script>

<style>
@keyframes spin { to { transform: rotate(360deg); } }

/* Use background-color on hover instead of filter to preserve CSS stacking context */
.spa-table-row {
  transition: background .15s;
}
.spa-table-row:hover > td {
  background-color: rgba(0, 0, 0, 0.025);
}

.spa-table-row > td {
  position: static;
  vertical-align: middle;
}
.spa-table-row > td:last-child {
  position: relative;
}

/* Elevate the active row and dropdown above subsequent rows */
.spa-table-row:has(.btn-group.open) {
  position: relative !important;
  z-index: 1050 !important;
}
.spa-table-row:has(.btn-group.open) > td {
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

.table-responsive:has(.open) {
  overflow: visible !important;
}
</style>
