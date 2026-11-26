import { createRouter, createWebHashHistory } from 'vue-router'
import { useAuthStore } from '@/stores/useAuthStore'

const routes = [
  { path: '/',          redirect: '/dashboard' },
  { path: '/login',     component: () => import('@/views/LoginView.vue'),     meta: { title: 'Ingreso', guestOnly: true, hideNavbar: true } },
  { path: '/register',  component: () => import('@/views/RegisterView.vue'),  meta: { title: 'Registro', guestOnly: true, hideNavbar: true } },
  { path: '/dashboard', component: () => import('@/views/DashboardView.vue'), meta: { title: 'Inicio' } },
  { path: '/income',    component: () => import('@/views/IncomeView.vue'),    meta: { title: 'Ingresos' } },
  { path: '/expense',   component: () => import('@/views/ExpenseView.vue'),   meta: { title: 'Gastos' } },
  { path: '/loans',     component: () => import('@/views/LoansView.vue'),     meta: { title: 'Préstamos' } },
  { path: '/borrowers', component: () => import('@/views/LoansView.vue'),     meta: { title: 'Prestatarios', tab: 'borrowers' } },
  { path: '/projects',  component: () => import('@/views/ProjectsView.vue'),  meta: { title: 'Proyectos' } },
  { path: '/assets',    component: () => import('@/views/AssetsView.vue'),    meta: { title: 'Activos' } },
  { path: '/accounts',  component: () => import('@/views/AccountsView.vue'),  meta: { title: 'Cuentas y Categorías' } },
  { path: '/reports',       component: () => import('@/views/ReportsView.vue'),      meta: { title: 'Reportes Financieros' } },
  { path: '/settings',      component: () => import('@/views/SettingsView.vue'),     meta: { title: 'Configuración' } },
  { path: '/system-config', component: () => import('@/views/SystemConfigView.vue'), meta: { title: 'Configuración General del Sistema', requiresAdmin: true } },
  { path: '/:pathMatch(.*)*', redirect: '/dashboard' },
]

const router = createRouter({
  history: createWebHashHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 })
})

router.beforeEach(async (to) => {
  const authStore = useAuthStore()
  const isAuth = await authStore.ensureSession()

  if (!to.meta.guestOnly && !isAuth) {
    return {
      path: '/login',
      query: to.fullPath && to.fullPath !== '/dashboard' ? { redirect: to.fullPath } : {}
    }
  }

  if (to.meta.guestOnly && isAuth) {
    return { path: '/dashboard' }
  }

  if (to.meta.requiresAdmin && !authStore.isAdmin) {
    return { path: '/dashboard' }
  }

  return true
})

router.afterEach((to) => {
  const authStore = useAuthStore()
  const appName = authStore.appName || 'myFin'
  document.title = to.meta.title ? `${to.meta.title} — ${appName}` : appName
})

export default router
