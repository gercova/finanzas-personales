import { defineStore } from 'pinia'
import { ref } from 'vue'
import * as api from '@/api'

/**
 * Pure reactive store for modal form & filter dropdown options.
 * Loaded cleanly via REST JSON API with zero PHP window globals.
 */
export const useFormOptionsStore = defineStore('formOptions', () => {
  const accounts          = ref([])
  const incomeCategories  = ref([])
  const expenseCategories = ref([])
  const projects          = ref([])
  const borrowers         = ref([])
  const loaded            = ref(false)

  async function load(force = false) {
    if (!force && loaded.value && accounts.value.length > 0) return
    try {
      const { data } = await api.getFormOptions()
      if (data?.success) {
        accounts.value          = data.accounts || []
        incomeCategories.value  = data.incomeCategories || []
        expenseCategories.value = data.expenseCategories || []
        projects.value          = data.projects || []
        borrowers.value         = data.borrowers || []
        loaded.value            = true
      }
    } catch (e) {
      console.error('Error cargando opciones de formulario:', e)
    }
  }

  async function reload() {
    await load(true)
  }

  function reset() {
    accounts.value          = []
    incomeCategories.value  = []
    expenseCategories.value = []
    projects.value          = []
    borrowers.value         = []
    loaded.value            = false
  }

  return {
    accounts,
    incomeCategories,
    expenseCategories,
    projects,
    borrowers,
    loaded,
    load,
    reload,
    reset
  }
})
