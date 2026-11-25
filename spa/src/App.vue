<template>
  <router-view v-slot="{ Component, route }">
    <Transition :name="route.meta.transition || 'page-fade'" mode="out-in">
      <component :is="Component" :key="route.path" />
    </Transition>
  </router-view>
  <AppToast ref="toast" />
</template>

<script setup>
import { ref, provide } from 'vue'
import AppToast from './components/AppToast.vue'

const toast = ref(null)
provide('toast', {
  success: (msg) => toast.value?.show(msg, 'success'),
  danger:  (msg) => toast.value?.show(msg, 'danger'),
  warning: (msg) => toast.value?.show(msg, 'warning'),
  info:    (msg) => toast.value?.show(msg, 'info'),
})
</script>

<style>
.page-fade-enter-active, .page-fade-leave-active { transition: opacity .18s ease, transform .18s ease; }
.page-fade-enter-from  { opacity: 0; transform: translateY(8px); }
.page-fade-leave-to    { opacity: 0; transform: translateY(-6px); }
</style>
