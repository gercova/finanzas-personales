import http, { getBaseURL, getCsrfToken } from './http'

// ─── Auth & Session ───────────────────────────────────────────────────────────
export const getSession          = ()     => http.get('php_action/authSession.php')
export const login               = (data) => http.post('php_action/authLogin.php', data)
export const register            = (data) => http.post('php_action/authRegister.php', data)
export const logout              = ()     => http.post('php_action/authLogout.php')
export const getHeaderMetrics    = ()     => http.get('php_action/getHeaderMetrics.php')

// ─── Form Options (Dropdowns) ─────────────────────────────────────────────────
export const getFormOptions      = ()   => http.get('php_action/getFormOptions.php')

// ─── Incomes ──────────────────────────────────────────────────────────────────
export const fetchIncomes        = ()   => http.get('php_action/fetchIncomes.php')
export const getIncomeData       = (id) => http.get('php_action/getIncomeData.php', { params: { id } })
export const createIncome        = (fd) => http.post('php_action/createIncome.php', fd)
export const editIncome          = (fd) => http.post('php_action/editIncome.php', fd)
export const removeIncome        = (id) => http.post('php_action/removeIncome.php', new URLSearchParams({ income_id: id }))

// ─── Expenses ─────────────────────────────────────────────────────────────────
export const fetchExpenses       = ()   => http.get('php_action/fetchExpenses.php')
export const getExpenseData      = (id) => http.get('php_action/getExpenseData.php', { params: { id } })
export const createExpense       = (fd) => http.post('php_action/createExpense.php', fd)
export const editExpense         = (fd) => http.post('php_action/editExpense.php', fd)
export const removeExpense       = (id) => http.post('php_action/removeExpense.php', new URLSearchParams({ expense_id: id }))

// ─── Loans ────────────────────────────────────────────────────────────────────
export const fetchLoans          = ()   => http.get('php_action/fetchLoans.php')
export const getLoanData         = (id) => http.get('php_action/getLoanData.php', { params: { id } })
export const createLoan          = (fd) => http.post('php_action/createLoan.php', fd)
export const editLoan            = (fd) => http.post('php_action/editLoan.php', fd)
export const removeLoan          = (id) => http.post('php_action/removeLoan.php', new URLSearchParams({ loan_id: id }))
export const updateLoanStatus    = (id, s) => http.post('php_action/updateLoanStatus.php', new URLSearchParams({ loan_id: id, status: s }))
export const addLoanPayment      = (fd) => http.post('php_action/addLoanPayment.php', fd)

// ─── Borrowers (Prestatarios) & Decolecta API ─────────────────────────────────
export const fetchBorrowers      = ()   => http.get('php_action/fetchBorrowers.php')
export const getBorrowerData     = (id) => http.get('php_action/getBorrowerData.php', { params: { id } })
export const createBorrower      = (fd) => http.post('php_action/createBorrower.php', fd)
export const editBorrower        = (fd) => http.post('php_action/editBorrower.php', fd)
export const removeBorrower      = (id) => http.post('php_action/removeBorrower.php', new URLSearchParams({ id }))
export const lookupDecolecta     = (type, number) => http.get('php_action/lookupDecolecta.php', { params: { type, number } })

// ─── Projects ─────────────────────────────────────────────────────────────────
export const fetchProjects       = ()   => http.get('php_action/fetchProjects.php')
export const getProjectData      = (id) => http.get('php_action/getProjectData.php', { params: { id } })
export const createProject       = (fd) => http.post('php_action/createProject.php', fd)
export const editProject         = (fd) => http.post('php_action/editProject.php', fd)
export const removeProject       = (id) => http.post('php_action/removeProject.php', new URLSearchParams({ project_id: id }))

// ─── Assets ───────────────────────────────────────────────────────────────────
export const fetchAssets         = ()   => http.get('php_action/fetchAssets.php')
export const getAssetData        = (id) => http.get('php_action/getAssetData.php', { params: { id } })
export const createAsset         = (fd) => http.post('php_action/createAsset.php', fd)
export const editAsset           = (fd) => http.post('php_action/editAsset.php', fd)
export const removeAsset         = (id) => http.post('php_action/removeAsset.php', new URLSearchParams({ asset_id: id }))

// ─── Accounts ─────────────────────────────────────────────────────────────────
export const fetchAccounts       = ()   => http.get('php_action/fetchAccounts.php')
export const getAccountData      = (id) => http.get('php_action/getAccountData.php', { params: { id } })
export const createAccount       = (fd) => http.post('php_action/createAccount.php', fd)
export const editAccount         = (fd) => http.post('php_action/editAccount.php', fd)
export const removeAccount       = (id) => http.post('php_action/removeAccount.php', new URLSearchParams({ account_id: id }))

// ─── Financial Categories ─────────────────────────────────────────────────────
export const fetchFinCategories  = ()   => http.get('php_action/fetchFinCategories.php')
export const getFinCategoryData  = (id) => http.get('php_action/getFinCategoryData.php', { params: { id } })
export const createFinCategory   = (fd) => http.post('php_action/createFinCategory.php', fd)
export const editFinCategory     = (fd) => http.post('php_action/editFinCategory.php', fd)
export const removeFinCategory   = (id) => http.post('php_action/removeFinCategory.php', new URLSearchParams({ category_id: id }))

// ─── Dashboard & Reports & Settings ───────────────────────────────────────────
export const getDashboardData       = ()       => http.get('php_action/getDashboardData.php')
export const fetchCalendarEvents    = ()       => http.get('php_action/fetchCalendarEvents.php')
export const getFinancialReportData = ()       => http.get('php_action/getFinancialReportData.php')
export const getReportsChartData    = (params) => http.get('php_action/getReportsChartData.php', { params })
export const getSettingsData        = ()       => http.get('php_action/getSettingsData.php')
export const updateSpendingLimit    = (fd)     => http.post('php_action/updateSpendingLimit.php', fd)
export const changeUsername         = (fd)     => http.post('php_action/changeUsername.php', fd)
export const changePassword         = (fd)     => http.post('php_action/changePassword.php', fd)

// ─── Admin System Configuration & Database Migrations ─────────────────────────
export const adminGetSystemConfig       = ()   => http.get('php_action/adminGetSystemConfig.php')
export const adminUpdateSystemSettings  = (fd) => http.post('php_action/adminUpdateSystemSettings.php', fd)
export const adminRunMigrations         = (fd) => http.post('php_action/adminRunMigrations.php', fd)
export const adminIntegrateMigration    = (fd) => http.post('php_action/adminIntegrateMigration.php', fd)

// ─── Bulk import & templates ──────────────────────────────────────────────────
export const bulkImport             = (fd)     => http.post('php_action/bulkImport.php', fd)
export const templateUrl            = (module) => `${getBaseURL()}php_action/downloadTemplate.php?module=${encodeURIComponent(module)}&csrf_token=${encodeURIComponent(getCsrfToken())}`
export const assetUrl               = (path)   => path ? `${getBaseURL()}${String(path).replace(/^\/+/, '')}` : ''
