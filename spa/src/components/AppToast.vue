<template>
  <Teleport to="body">
    <Transition name="toast-fade">
      <div v-if="visible"
        :class="['alert', `alert-${type}`, 'spa-toast']"
        role="alert"
        @click="hide">
        <button type="button" class="close" @click.stop="hide"><span>&times;</span></button>
        <span v-html="message"></span>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref } from 'vue'

const visible = ref(false)
const message = ref('')
const type    = ref('success')
let timer = null

function show(msg, t = 'success', duration = 4000) {
  message.value = msg
  type.value = t
  visible.value = true
  clearTimeout(timer)
  timer = setTimeout(hide, duration)
}
function hide() { visible.value = false }

defineExpose({ show })
</script>

<style scoped>
.spa-toast {
  position: fixed;
  top: 70px;
  right: 20px;
  z-index: 9999;
  min-width: 280px;
  max-width: 420px;
  box-shadow: 0 4px 16px rgba(0,0,0,.18);
  cursor: pointer;
  border-radius: 4px;
}
.toast-fade-enter-active, .toast-fade-leave-active { transition: opacity .3s, transform .3s; }
.toast-fade-enter-from, .toast-fade-leave-to { opacity: 0; transform: translateX(30px); }
</style>
