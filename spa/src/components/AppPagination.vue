<template>
  <nav v-if="totalPages > 1" aria-label="Paginación" style="margin:10px 0 0;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
    <ul class="pagination pagination-sm" style="margin:0;">
      <li :class="{ disabled: modelValue === 1 }">
        <a href="#" @click.prevent="go(1)" title="Primera">&laquo;</a>
      </li>
      <li :class="{ disabled: modelValue === 1 }">
        <a href="#" @click.prevent="go(modelValue - 1)">&lsaquo;</a>
      </li>

      <li v-for="p in pages" :key="p" :class="{ active: p === modelValue, disabled: p === '…' }">
        <a href="#" @click.prevent="p !== '…' && go(p)">{{ p }}</a>
      </li>

      <li :class="{ disabled: modelValue === totalPages }">
        <a href="#" @click.prevent="go(modelValue + 1)">&rsaquo;</a>
      </li>
      <li :class="{ disabled: modelValue === totalPages }">
        <a href="#" @click.prevent="go(totalPages)" title="Última">&raquo;</a>
      </li>
    </ul>

    <small class="text-muted">
      Página {{ modelValue }} / {{ totalPages }}
      &nbsp;·&nbsp; {{ totalRows }} registros
    </small>

    <select class="form-control input-sm" style="width:70px;" :value="pageSize" @change="$emit('update:pageSize', +$event.target.value)">
      <option v-for="n in [10,15,25,50,100]" :key="n" :value="n">{{ n }}</option>
    </select>
    <small class="text-muted">/ página</small>
  </nav>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: { type: Number, required: true },   // current page
  totalPages: { type: Number, required: true },
  totalRows:  { type: Number, default: 0 },
  pageSize:   { type: Number, default: 15 },
})
const emit = defineEmits(['update:modelValue', 'update:pageSize'])

function go(p) {
  p = Math.max(1, Math.min(props.totalPages, +p))
  if (p !== props.modelValue) emit('update:modelValue', p)
}

const pages = computed(() => {
  const { modelValue: cur, totalPages: total } = props
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1)
  const arr = []
  if (cur > 3)        arr.push(1, '…')
  for (let i = Math.max(1, cur - 2); i <= Math.min(total, cur + 2); i++) arr.push(i)
  if (cur < total - 2) arr.push('…', total)
  return arr
})
</script>
