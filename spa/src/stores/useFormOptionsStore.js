import { defineStore } from 'pinia'
import { ref } from 'vue'
import * as api from '@/api'

/**
 * Reactive store for modal form dropdown options (accounts, categories, projects, POS brands & categories).
 * Initialized from PHP-injected window globals for zero-latency boot, and refreshed on mutations.
 */
export const useFormOptionsStore = defineStore('formOptions', () => {
  const accounts          = ref(typeof window !== 'undefined' ? (window.incomeAccounts || []) : [])
  const incomeCategories  = ref(typeof window !== 'undefined' ? (window.incomeCategories || []) : [])
  const expenseCategories = ref(typeof window !== 'undefined' ? (window.expenseCategories || []) : [])
  const projects          = ref(typeof window !== 'undefined' ? (window.incomeProjects || []) : [])
  const brands            = ref([])
  const posCategories     = ref([])
  const loadedPos         = ref(false)

  async function load(force = false) {
    if (!force && loadedPos.value && accounts.value.length > 0) return
    try {
      const { data } = await api.getFormOptions()
      if (data?.success) {
        accounts.value          = data.accounts || []
        incomeCategories.value  = data.incomeCategories || []
        expenseCategories.value = data.expenseCategories || []
        projects.value          = data.projects || []
        brands.value            = data.brands || []
        posCategories.value     = data.posCategories || []
        loadedPos.value         = true

        if (typeof window !== 'undefined') {
          window.incomeAccounts    = accounts.value
          window.expenseAccounts   = accounts.value
          window.loanAccounts      = accounts.value
          window.incomeCategories  = incomeCategories.value
          window.expenseCategories = expenseCategories.value
          window.incomeProjects    = projects.value
          window.expenseProjects   = projects.value
        }
      }
    } catch (e) {
      console.error('Error cargando opciones de formulario:', e)
    }
  }

  async function reload() {
    await load(true)
  }

  return {
    accounts,
    incomeCategories,
    expenseCategories,
    projects,
    brands,
    posCategories,
    load,
    reload
  }
})
