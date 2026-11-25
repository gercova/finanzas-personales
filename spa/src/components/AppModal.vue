<!--
  Lightweight Bootstrap-3-compatible modal wrapper.
  Usage:
    <AppModal v-model="showModal" title="…" size="lg">
      <template #body>…</template>
      <template #footer>…</template>
    </AppModal>
-->
<template>
  <Teleport to="body">
    <Transition name="modal-fade" @after-leave="onAfterLeave">
      <div v-if="modelValue" class="modal" tabindex="-1" role="dialog"
           style="display:block;" @mousedown.self="close">

        <div :class="['modal-dialog', sizeClass]" role="document">
          <div class="modal-content" @click.stop>

            <div class="modal-header" :style="headerStyle">
              <button type="button" class="close" @click="close" :style="closeStyle">
                <span aria-hidden="true">&times;</span>
              </button>
              <h4 class="modal-title">
                <span v-if="icon"><i :class="'glyphicon ' + icon"></i>&nbsp;</span>
                {{ title }}
              </h4>
            </div>

            <div class="modal-body">
              <slot name="body" />
            </div>

            <div class="modal-footer">
              <slot name="footer">
                <button type="button" class="btn btn-default" @click="close">Cancelar</button>
              </slot>
            </div>

          </div>
        </div>
      </div>
    </Transition>

    <!-- Backdrop -->
    <Transition name="backdrop-fade">
      <div v-if="modelValue" class="modal-backdrop" style="opacity:.5;" @click="close" />
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue:  { type: Boolean, default: false },
  title:       { type: String,  default: '' },
  icon:        { type: String,  default: '' },        // glyphicon name e.g. "glyphicon-plus-sign"
  size:        { type: String,  default: '' },        // 'sm' | 'lg' | ''
  headerColor: { type: String,  default: '' },        // e.g. '#27ae60'
})
const emit = defineEmits(['update:modelValue', 'closed'])

const sizeClass  = computed(() => props.size ? `modal-${props.size}` : '')
const headerStyle = computed(() =>
  props.headerColor ? { background: props.headerColor, color: '#fff' } : {}
)
const closeStyle = computed(() => props.headerColor ? { color: '#fff' } : {})

function close() { emit('update:modelValue', false) }
function onAfterLeave() { emit('closed') }
</script>

<style scoped>
.modal { background: transparent; }

.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity .25s, transform .25s; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; transform: translateY(-20px); }

.backdrop-fade-enter-active, .backdrop-fade-leave-active { transition: opacity .25s; }
.backdrop-fade-enter-from,   .backdrop-fade-leave-to     { opacity: 0; }
</style>
