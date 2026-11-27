<template>
  <th
    :class="[alignClass, { 'spa-th-sortable': sortable, 'spa-th-sorted': isActive }]"
    :style="thStyle"
    :aria-sort="ariaSort"
    :tabindex="sortable ? 0 : undefined"
    @click="onClick"
    @keydown.enter.prevent="onClick"
    @keydown.space.prevent="onClick"
  >
    <span class="spa-th-inner">
      <span>{{ label }}</span>
      <i
        v-if="sortable"
        class="glyphicon spa-sort-icon"
        :class="iconClass"
        aria-hidden="true"
      ></i>
    </span>
  </th>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  label:     { type: String,  required: true },
  sortKey:   { type: String,  default: '' },
  activeKey: { type: String,  default: '' },
  sortDir:   { type: String,  default: 'asc' },
  align:     { type: String,  default: '' },
  sortable:  { type: Boolean, default: true },
  thStyle:   { type: [Object, String, Array], default: undefined },
})

const emit = defineEmits(['sort'])

const isActive = computed(() => props.sortable && props.sortKey && props.activeKey === props.sortKey)

const alignClass = computed(() => {
  if (props.align === 'right') return 'text-right'
  if (props.align === 'center') return 'text-center'
  return ''
})

const ariaSort = computed(() => {
  if (!props.sortable) return undefined
  if (!isActive.value) return 'none'
  return props.sortDir === 'desc' ? 'descending' : 'ascending'
})

const iconClass = computed(() => {
  if (!isActive.value) return 'glyphicon-sort'
  return props.sortDir === 'desc' ? 'glyphicon-triangle-bottom' : 'glyphicon-triangle-top'
})

function onClick() {
  if (props.sortable && props.sortKey) {
    emit('sort', props.sortKey)
  }
}
</script>

<style>
.spa-th-sortable {
  cursor: pointer;
  user-select: none;
  white-space: nowrap;
  transition: background-color 0.12s ease;
}
.spa-th-sortable:hover {
  background-color: rgba(0, 0, 0, 0.04) !important;
}
.spa-th-sortable:focus-visible {
  outline: 2px solid #337ab7;
  outline-offset: -2px;
}
.spa-th-inner {
  display: inline-flex;
  align-items: center;
  gap: 5px;
}
.text-right .spa-th-inner {
  justify-content: flex-end;
}
.text-center .spa-th-inner {
  justify-content: center;
}
.spa-sort-icon {
  font-size: 10px;
  opacity: 0.3;
  transition: opacity 0.12s ease, color 0.12s ease;
}
.spa-th-sortable:hover .spa-sort-icon {
  opacity: 0.65;
}
.spa-th-sorted .spa-sort-icon {
  opacity: 1;
  color: #337ab7;
}
</style>
