import http from './http'

// ─── Incomes ──────────────────────────────────────────────────────────────────
export const fetchIncomes   = ()         => http.get('php_action/fetchIncomes.php')
export const getIncomeData  = (id)       => http.get('php_action/getIncomeData.php', { params: { id } })
export const createIncome   = (fd)       => http.post('php_action/createIncome.php', fd)
export const editIncome     = (fd)       => http.post('php_action/editIncome.php', fd)
export const removeIncome   = (id)       => http.post('php_action/removeIncome.php', new URLSearchParams({ income_id: id }))

// ─── Expenses ─────────────────────────────────────────────────────────────────
export const fetchExpenses  = ()         => http.get('php_action/fetchExpenses.php')
export const getExpenseData = (id)       => http.get('php_action/getExpenseData.php', { params: { id } })
export const createExpense  = (fd)       => http.post('php_action/createExpense.php', fd)
export const editExpense    = (fd)       => http.post('php_action/editExpense.php', fd)
export const removeExpense  = (id)       => http.post('php_action/removeExpense.php', new URLSearchParams({ expense_id: id }))

// ─── Loans ────────────────────────────────────────────────────────────────────
export const fetchLoans     = ()         => http.get('php_action/fetchLoans.php')
export const getLoanData    = (id)       => http.get('php_action/getLoanData.php', { params: { id } })
export const createLoan     = (fd)       => http.post('php_action/createLoan.php', fd)
export const editLoan       = (fd)       => http.post('php_action/editLoan.php', fd)
export const removeLoan     = (id)       => http.post('php_action/removeLoan.php', new URLSearchParams({ loan_id: id }))
export const updateLoanStatus = (id, s)  => http.post('php_action/updateLoanStatus.php', new URLSearchParams({ loan_id: id, status: s }))
export const addLoanPayment   = (fd)     => http.post('php_action/addLoanPayment.php', fd)

// ─── Projects ─────────────────────────────────────────────────────────────────
export const fetchProjects  = ()         => http.get('php_action/fetchProjects.php')
export const getProjectData = (id)       => http.get('php_action/getProjectData.php', { params: { id } })
export const createProject  = (fd)       => http.post('php_action/createProject.php', fd)
export const editProject    = (fd)       => http.post('php_action/editProject.php', fd)
export const removeProject  = (id)       => http.post('php_action/removeProject.php', new URLSearchParams({ project_id: id }))

// ─── Assets ───────────────────────────────────────────────────────────────────
export const fetchAssets    = ()         => http.get('php_action/fetchAssets.php')
export const getAssetData   = (id)       => http.get('php_action/getAssetData.php', { params: { id } })
export const createAsset    = (fd)       => http.post('php_action/createAsset.php', fd)
export const editAsset      = (fd)       => http.post('php_action/editAsset.php', fd)
export const removeAsset    = (id)       => http.post('php_action/removeAsset.php', new URLSearchParams({ asset_id: id }))

// ─── Accounts ─────────────────────────────────────────────────────────────────
export const fetchAccounts  = ()         => http.get('php_action/fetchAccounts.php')
export const getAccountData = (id)       => http.get('php_action/getAccountData.php', { params: { id } })
export const createAccount  = (fd)       => http.post('php_action/createAccount.php', fd)
export const editAccount    = (fd)       => http.post('php_action/editAccount.php', fd)
export const removeAccount  = (id)       => http.post('php_action/removeAccount.php', new URLSearchParams({ account_id: id }))

// ─── Financial Categories ─────────────────────────────────────────────────────
export const fetchFinCategories = ()     => http.get('php_action/fetchFinCategories.php')

// ─── Dashboard ────────────────────────────────────────────────────────────────
export const fetchCalendarEvents = ()    => http.get('php_action/fetchCalendarEvents.php')

// ─── Bulk import & templates ──────────────────────────────────────────────────
export const bulkImport      = (fd)      => http.post('php_action/bulkImport.php', fd)
export const templateUrl     = (module)  => `php_action/downloadTemplate.php?module=${module}&csrf_token=${encodeURIComponent(window.systemCsrfToken || '')}`
