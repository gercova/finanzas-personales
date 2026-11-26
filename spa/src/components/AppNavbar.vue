<template>
  <nav class="navbar navbar-default navbar-static-top">
    <div class="container">
      <div class="navbar-header" style="display:flex;align-items:center;">
        <button
          type="button"
          class="navbar-toggle"
          :class="{ collapsed: !mobileOpen }"
          @click="mobileOpen = !mobileOpen"
          aria-label="Toggle navigation"
        >
          <span class="sr-only">Toggle navigation</span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
        </button>

        <router-link class="navbar-brand" to="/dashboard" :title="authStore.appName" @click="closeMenus">
          <i class="glyphicon glyphicon-piggy-bank" style="color:#27ae60;"></i> {{ authStore.appName }}
        </router-link>

        <div class="myfin-metrics-container hidden-xs">
          <span class="metric-badge metric-badge-income" title="Ingresos del mes">
            <i class="glyphicon glyphicon-arrow-up"></i> Mes: S/ {{ fmt(authStore.headerMetrics.month_income) }}
          </span>
          <span class="metric-badge metric-badge-expense" title="Gastos de hoy">
            <i class="glyphicon glyphicon-arrow-down"></i> Hoy: S/ {{ fmt(authStore.headerMetrics.today_expenses) }}
          </span>
        </div>
      </div>

      <div class="navbar-collapse collapse" :class="{ in: mobileOpen }">
        <ul class="nav navbar-nav navbar-right">
          <li :class="{ active: route.path === '/dashboard' }">
            <router-link to="/dashboard" @click="closeMenus">
              <i class="glyphicon glyphicon-home"></i> Inicio
            </router-link>
          </li>
          <li :class="{ active: route.path === '/income' }">
            <router-link to="/income" @click="closeMenus">
              <i class="glyphicon glyphicon-arrow-up text-success"></i> Ingresos
            </router-link>
          </li>
          <li :class="{ active: route.path === '/expense' }">
            <router-link to="/expense" @click="closeMenus">
              <i class="glyphicon glyphicon-arrow-down text-danger"></i> Gastos
            </router-link>
          </li>
          <li :class="{ active: route.path === '/loans' || route.path === '/borrowers' }">
            <router-link to="/loans" @click="closeMenus">
              <i class="glyphicon glyphicon-retweet text-warning"></i> Préstamos
            </router-link>
          </li>
          <li :class="{ active: route.path === '/debts' || route.path === '/lenders' }">
            <router-link to="/debts" @click="closeMenus">
              <i class="glyphicon glyphicon-save text-danger"></i> Deudas
            </router-link>
          </li>
          <li :class="{ active: route.path === '/projects' }">
            <router-link to="/projects" @click="closeMenus">
              <i class="glyphicon glyphicon-briefcase text-primary"></i> Proyectos
            </router-link>
          </li>
          <li :class="{ active: route.path === '/assets' }">
            <router-link to="/assets" @click="closeMenus">
              <i class="glyphicon glyphicon-certificate" style="color:#8e44ad;"></i> Activos
            </router-link>
          </li>
          <li :class="{ active: route.path === '/reports' }">
            <router-link to="/reports" @click="closeMenus">
              <i class="glyphicon glyphicon-stats text-info"></i> Reportes
            </router-link>
          </li>

          <li
            ref="userMenuRef"
            class="dropdown"
            :class="{ open: userMenuOpen, active: route.path === '/accounts' || route.path === '/settings' || route.path === '/system-config' }"
          >
            <a
              href="#"
              class="dropdown-toggle"
              @click.prevent="userMenuOpen = !userMenuOpen"
              :aria-expanded="userMenuOpen ? 'true' : 'false'"
            >
              <i class="glyphicon glyphicon-user"></i> <span class="caret"></span>
            </a>
            <ul class="dropdown-menu">
              <li class="dropdown-header">
                Usuario: <strong>{{ authStore.user?.username || 'admin' }}</strong>
                <span v-if="authStore.isAdmin" class="label label-danger" style="font-size:9px;margin-left:4px;">ADMIN</span>
              </li>
              <li role="separator" class="divider"></li>
              <li :class="{ active: route.path === '/accounts' }">
                <router-link to="/accounts" @click="closeMenus">
                  <i class="glyphicon glyphicon-credit-card"></i> Cuentas y Categorías
                </router-link>
              </li>
              <li :class="{ active: route.path === '/settings' }">
                <router-link to="/settings" @click="closeMenus">
                  <i class="glyphicon glyphicon-wrench"></i> Configuración
                </router-link>
              </li>
              <li v-if="authStore.isAdmin" :class="{ active: route.path === '/system-config' }">
                <router-link to="/system-config" @click="closeMenus">
                  <i class="glyphicon glyphicon-cog text-danger"></i> Configuración General (Admin)
                </router-link>
              </li>
              <li role="separator" class="divider"></li>
              <li>
                <a href="#" @click.prevent="handleLogout">
                  <i class="glyphicon glyphicon-log-out"></i> Salir
                </a>
              </li>
            </ul>
          </li>
        </ul>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/useAuthStore'
import { useFormOptionsStore } from '@/stores/useFormOptionsStore'
import { useDashboardStore } from '@/stores/useDashboardStore'
import { useReportStore } from '@/stores/useReportStore'

const route        = useRoute()
const router       = useRouter()
const authStore    = useAuthStore()
const optionsStore = useFormOptionsStore()

const mobileOpen   = ref(false)
const userMenuOpen = ref(false)
const userMenuRef  = ref(null)

function fmt(n) {
  const num = parseFloat(n) || 0
  return num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')
}

function closeMenus() {
  mobileOpen.value   = false
  userMenuOpen.value = false
}

async function handleLogout() {
  closeMenus()
  await authStore.logout()
  optionsStore.reset()
  useDashboardStore().invalidate()
  useReportStore().invalidate()
  router.replace('/login')
}

function onDocClick(e) {
  if (userMenuOpen.value && userMenuRef.value && !userMenuRef.value.contains(e.target)) {
    userMenuOpen.value = false
  }
}

onMounted(() => document.addEventListener('click', onDocClick))
onUnmounted(() => document.removeEventListener('click', onDocClick))
</script>

<style>
.navbar-default {
  background-color: #f8f8f8;
  background-image: linear-gradient(to bottom, #ffffff 0%, #f8f8f8 100%);
  border-color: #e7e7e7 !important;
  box-shadow: inset 0 1px 0 rgba(255,255,255,.15), 0 1px 5px rgba(0,0,0,.075) !important;
  margin-bottom: 20px;
}
.navbar-default .navbar-brand {
  color: #333 !important;
  font-weight: 700;
  font-size: 19px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}
.navbar-default .navbar-nav > li > a {
  color: #555 !important;
  font-size: 13.5px;
  font-weight: 500;
  padding: 15px 12px;
  transition: background-color .15s ease, color .15s ease;
}
.navbar-default .navbar-nav > li > a:hover {
  color: #222 !important;
  background: #e7e7e7 !important;
}
.navbar-default .navbar-nav > .active > a,
.navbar-default .navbar-nav > .active > a:hover {
  color: #333 !important;
  background: #e2e2e2 !important;
  font-weight: 600;
  box-shadow: inset 0 3px 9px rgba(0,0,0,.075) !important;
}
.navbar-default .navbar-nav > .open > a {
  background: #e2e2e2 !important;
  color: #333 !important;
}
.navbar-default .navbar-nav .dropdown-menu {
  background: #fff !important;
  border: 1px solid #ccc !important;
  border-radius: 4px !important;
  box-shadow: 0 6px 12px rgba(0,0,0,.175) !important;
}
.navbar-default .navbar-nav .dropdown-menu > li > a {
  color: #333 !important;
  padding: 7px 18px !important;
}
.navbar-default .navbar-nav .dropdown-menu > li > a:hover {
  background: #f5f5f5 !important;
}
.myfin-metrics-container {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  margin-left: 10px;
  height: 50px;
}
.metric-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 5px 9px;
  border-radius: 4px;
  font-size: 11.5px;
  font-weight: 600;
  white-space: nowrap;
}
.metric-badge-income {
  background: #dff0d8;
  color: #2e7d32;
  border: 1px solid #c8e6c9;
}
.metric-badge-expense {
  background: #fce4ec;
  color: #c2185b;
  border: 1px solid #f8bbd0;
}
.panel-heading-flex {
  display: flex !important;
  justify-content: space-between !important;
  align-items: center !important;
  min-height: 52px !important;
  padding: 8px 15px !important;
}
.panel-heading-flex .page-heading {
  margin: 0 !important;
  font-size: 16px !important;
  font-weight: 600 !important;
  display: flex !important;
  align-items: center !important;
}
.panel-heading-flex .btn {
  margin: 0 !important;
  display: inline-flex !important;
  align-items: center !important;
  gap: 5px !important;
}
#app {
  min-height: 300px;
}
</style>
