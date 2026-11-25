import http, { getBaseURL } from './http'

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
export const getDashboardData       = ()   => http.get('php_action/getDashboardData.php')
export const fetchCalendarEvents    = ()   => http.get('php_action/fetchCalendarEvents.php')
export const getFinancialReportData = ()   => http.get('php_action/getFinancialReportData.php')
export const getSettingsData        = ()   => http.get('php_action/getSettingsData.php')
export const updateSpendingLimit    = (fd) => http.post('php_action/updateSpendingLimit.php', fd)
export const changeUsername         = (fd) => http.post('php_action/changeUsername.php', fd)
export const changePassword         = (fd) => http.post('php_action/changePassword.php', fd)

// ─── POS / Inventory ──────────────────────────────────────────────────────────
export const fetchBrand             = ()   => http.get('php_action/fetchBrand.php')
export const createBrand            = (fd) => http.post('php_action/createBrand.php', fd)
export const editBrand              = (fd) => http.post('php_action/editBrand.php', fd)
export const removeBrand            = (id) => http.post('php_action/removeBrand.php', new URLSearchParams({ brandId: id }))
export const fetchSelectedBrand     = (id) => http.post('php_action/fetchSelectedBrand.php', new URLSearchParams({ brandId: id }))

export const fetchCategories        = ()   => http.get('php_action/fetchCategories.php')
export const createCategories       = (fd) => http.post('php_action/createCategories.php', fd)
export const editCategories         = (fd) => http.post('php_action/editCategories.php', fd)
export const removeCategories       = (id) => http.post('php_action/removeCategories.php', new URLSearchParams({ categoriesId: id }))
export const fetchSelectedCategories= (id) => http.post('php_action/fetchSelectedCategories.php', new URLSearchParams({ categoriesId: id }))

export const fetchProduct           = ()   => http.get('php_action/fetchProduct.php')
export const createProduct          = (fd) => http.post('php_action/createProduct.php', fd)
export const editProduct            = (fd) => http.post('php_action/editProduct.php', fd)
export const editProductImage       = (fd) => http.post('php_action/editProductImage.php', fd)
export const removeProduct          = (id) => http.post('php_action/removeProduct.php', new URLSearchParams({ productId: id }))
export const fetchSelectedProduct   = (id) => http.post('php_action/fetchSelectedProduct.php', new URLSearchParams({ productId: id }))

export const fetchOrder             = ()   => http.get('php_action/fetchOrder.php')

// ─── Bulk import & templates ──────────────────────────────────────────────────
export const bulkImport             = (fd)     => http.post('php_action/bulkImport.php', fd)
export const templateUrl            = (module) => `${getBaseURL()}php_action/downloadTemplate.php?module=${module}&csrf_token=${encodeURIComponent(window.systemCsrfToken || '')}`
