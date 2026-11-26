<template>
  <AppNavbar v-if="authStore.authenticated && !route.meta.hideNavbar" />

  <div :class="authStore.authenticated && !route.meta.hideNavbar ? 'container main-content-container' : ''">
    <div v-if="!authStore.sessionChecked" class="text-center" style="padding:60px 0;">
      <i class="glyphicon glyphicon-refresh" style="font-size:28px;animation:spin 1s linear infinite;color:#888;"></i>
      <p class="text-muted" style="margin-top:10px;">Iniciando aplicación…</p>
    </div>

    <router-view v-else v-slot="{ Component, route: currentRoute }">
      <Transition :name="currentRoute.meta.transition || 'page-fade'" mode="out-in">
        <component :is="Component" :key="currentRoute.path" />
      </Transition>
    </router-view>
  </div>

  <AppToast ref="toast" />
</template>

<script setup>
import { ref, provide } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/useAuthStore'
import { useFormOptionsStore } from '@/stores/useFormOptionsStore'
import { setUnauthorizedHandler } from '@/api/http'
import AppNavbar from './components/AppNavbar.vue'
import AppToast  from './components/AppToast.vue'

const route        = useRoute()
const router       = useRouter()
const authStore    = useAuthStore()
const optionsStore = useFormOptionsStore()

setUnauthorizedHandler((code) => {
  authStore.clearSessionState(code)
  optionsStore.reset()
  if (router.currentRoute.value.path !== '/login') {
    router.replace({ path: '/login', query: { error: code || 'timeout' } })
  }
})

const toast = ref(null)
provide('toast', {
  success: (msg) => toast.value?.show(msg, 'success'),
  danger:  (msg) => toast.value?.show(msg, 'danger'),
  warning: (msg) => toast.value?.show(msg, 'warning'),
  info:    (msg) => toast.value?.show(msg, 'info'),
})
</script>

<style>
@keyframes spin { to { transform: rotate(360deg); } }

.page-fade-enter-active, .page-fade-leave-active { transition: opacity .18s ease, transform .18s ease; }
.page-fade-enter-from  { opacity: 0; transform: translateY(8px); }
.page-fade-leave-to    { opacity: 0; transform: translateY(-6px); }
</style>
