import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  { path: '/',          redirect: '/dashboard' },
  { path: '/dashboard', component: () => import('@/views/DashboardView.vue'), meta: { title: 'Inicio' } },
  { path: '/income',    component: () => import('@/views/IncomeView.vue'),    meta: { title: 'Ingresos' } },
  { path: '/expense',   component: () => import('@/views/ExpenseView.vue'),   meta: { title: 'Gastos' } },
  { path: '/loans',     component: () => import('@/views/LoansView.vue'),     meta: { title: 'Préstamos' } },
  { path: '/projects',  component: () => import('@/views/ProjectsView.vue'),  meta: { title: 'Proyectos' } },
  { path: '/assets',    component: () => import('@/views/AssetsView.vue'),    meta: { title: 'Activos' } },
  { path: '/accounts',  component: () => import('@/views/AccountsView.vue'),  meta: { title: 'Cuentas' } },
  { path: '/reports',   component: () => import('@/views/ReportsView.vue'),   meta: { title: 'Reportes' } },
  { path: '/settings',  component: () => import('@/views/SettingsView.vue'),  meta: { title: 'Configuración' } },
]

const router = createRouter({
  // createWebHashHistory uses #/route — no server rewrite needed.
  // This is the safest choice for a PHP-hosted SPA where the shell page
  // may be accessed as /app.php or via /app/* rewrites.
  history: createWebHistory('/app/'),
  routes,
  scrollBehavior: () => ({ top: 0 })
})

router.afterEach((to) => {
  const appName = window.__APP_NAME__ || 'myFin'
  document.title = `${to.meta.title || ''} — ${appName}`
})

export default router
