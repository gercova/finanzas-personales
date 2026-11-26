import { createRouter, createWebHashHistory } from 'vue-router'

const routes = [
  { path: '/',          redirect: '/dashboard' },
  { path: '/dashboard', component: () => import('@/views/DashboardView.vue'), meta: { title: 'Inicio', navId: 'navDashboard' } },
  { path: '/income',    component: () => import('@/views/IncomeView.vue'),    meta: { title: 'Ingresos', navId: 'navIncome' } },
  { path: '/expense',   component: () => import('@/views/ExpenseView.vue'),   meta: { title: 'Gastos', navId: 'navExpense' } },
  { path: '/loans',     component: () => import('@/views/LoansView.vue'),     meta: { title: 'Préstamos', navId: 'navLoans' } },
  { path: '/borrowers', component: () => import('@/views/LoansView.vue'),     meta: { title: 'Prestatarios', navId: 'navLoans', tab: 'borrowers' } },
  { path: '/projects',  component: () => import('@/views/ProjectsView.vue'),  meta: { title: 'Proyectos', navId: 'navProjects' } },
  { path: '/assets',    component: () => import('@/views/AssetsView.vue'),    meta: { title: 'Activos', navId: 'navAssets' } },
  { path: '/accounts',  component: () => import('@/views/AccountsView.vue'),  meta: { title: 'Cuentas y Categorías', navId: 'navAccounts' } },
  { path: '/reports',   component: () => import('@/views/ReportsView.vue'),   meta: { title: 'Reportes Financieros', navId: 'navReport' } },
  { path: '/settings',  component: () => import('@/views/SettingsView.vue'),  meta: { title: 'Configuración', navId: 'navSetting' } },
]

const router = createRouter({
  history: createWebHashHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 })
})

router.afterEach((to) => {
  const appName = (typeof window !== 'undefined' && window.__APP_NAME__) || 'myFin'
  document.title = `${to.meta.title || ''} — ${appName}`

  // Synchronize navbar active class in DOM
  if (typeof document !== 'undefined') {
    document.querySelectorAll('.navbar-nav > li').forEach(li => li.classList.remove('active'))
    if (to.meta.navId) {
      const activeEl = document.getElementById(to.meta.navId)
      if (activeEl) activeEl.classList.add('active')
    }
  }
})

export default router
