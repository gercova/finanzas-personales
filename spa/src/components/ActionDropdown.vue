<template>
  <div ref="rootRef" class="btn-group" :class="{ open: isOpen, dropup: isDropup }">
    <button
      type="button"
      class="btn btn-xs btn-default dropdown-toggle"
      @click.stop="toggle"
      :aria-expanded="isOpen ? 'true' : 'false'"
    >
      <i v-if="showIcon" class="glyphicon glyphicon-cog"></i>
      <template v-if="label">&nbsp;{{ label }}</template>
      &nbsp;<span class="caret"></span>
    </button>
    <ul v-show="isOpen" ref="menuRef" class="dropdown-menu dropdown-menu-right" @click.stop="onMenuClick">
      <slot :close="close" />
    </ul>
  </div>
</template>

<script setup>
import { ref, nextTick, onMounted, onUnmounted } from 'vue'

defineProps({
  label:    { type: String,  default: 'Acción' },
  showIcon: { type: Boolean, default: true },
})

const rootRef  = ref(null)
const menuRef  = ref(null)
const isOpen   = ref(false)
const isDropup = ref(false)

function close() {
  isOpen.value   = false
  isDropup.value = false
}

async function toggle() {
  if (isOpen.value) {
    close()
    return
  }
  window.dispatchEvent(new CustomEvent('myfin:close-dropdowns', { detail: rootRef.value }))
  isOpen.value = true
  await nextTick()

  // Smart auto-dropup detection using viewport bounds
  if (rootRef.value && menuRef.value) {
    const rect       = rootRef.value.getBoundingClientRect()
    const menuHeight = menuRef.value.offsetHeight || 180
    const spaceBelow = window.innerHeight - rect.bottom
    const spaceAbove = rect.top
    isDropup.value = spaceBelow < menuHeight + 12 && spaceAbove > spaceBelow
  }
}

function onMenuClick(e) {
  if (e.target.closest('a, button')) {
    close()
  }
}

function onDocClick(e) {
  if (isOpen.value && rootRef.value && !rootRef.value.contains(e.target)) {
    close()
  }
}

function onOtherDropdownOpen(e) {
  if (isOpen.value && e.detail !== rootRef.value) {
    close()
  }
}

function onKeyDown(e) {
  if (isOpen.value && e.key === 'Escape') {
    close()
  }
}

onMounted(() => {
  document.addEventListener('click', onDocClick)
  document.addEventListener('keydown', onKeyDown)
  window.addEventListener('myfin:close-dropdowns', onOtherDropdownOpen)
})

onUnmounted(() => {
  document.removeEventListener('click', onDocClick)
  document.removeEventListener('keydown', onKeyDown)
  window.removeEventListener('myfin:close-dropdowns', onOtherDropdownOpen)
})
</script>
