# myFin — Personal Finance & Wealth Management System

<p align="left">
  <strong>🌐 Language / Idioma:</strong>
  <strong>English</strong> |
  <a href="README.es.md">Español</a>
</p>

[![PHP Version](https://img.shields.io/badge/PHP-8.1%20|%208.2%20|%208.3-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
[![MariaDB](https://img.shields.io/badge/MariaDB-10.5+-003545?style=flat-square&logo=mariadb&logoColor=white)](https://mariadb.org/)
[![Vue 3](https://img.shields.io/badge/Vue-3.5+-4FC08D?style=flat-square&logo=vue.js&logoColor=white)](https://vuejs.org/)
[![Vite](https://img.shields.io/badge/Vite-8.3+-646CFF?style=flat-square&logo=vite&logoColor=white)](https://vitejs.dev/)
[![Pinia](https://img.shields.io/badge/Pinia-4.1+-FFE56D?style=flat-square&logo=vue.js&logoColor=black)](https://pinia.vuejs.org/)
[![Highcharts](https://img.shields.io/badge/Highcharts-12.0+-8085e9?style=flat-square)](https://www.highcharts.com/)
[![License](https://img.shields.io/badge/License-Proprietary-red?style=flat-square)](#licencia)

> **myFin** is an enterprise-grade personal finance, wealth tracking, and debtor management application engineered to replace error-prone spreadsheets with an automated, transactional, and cloud-ready web platform.

---

## 📑 Table of Contents

- [Overview & Core Value](#-overview--core-value)
- [Key Features & Modules](#-key-features--modules)
- [System Architecture](#-system-architecture)
- [Technology Stack](#-technology-stack)
- [Security & Threat Mitigations](#-security--threat-mitigations)
- [Decolecta API Integration (RENIEC / SUNAT)](#-decolecta-api-integration-reniec--sunat)
- [Highcharts Executive Analytics](#-highcharts-executive-analytics)
- [Prerequisites & System Requirements](#-prerequisites--system-requirements)
- [Installation & Quick Start Guide](#-installation--quick-start-guide)
- [Environment Configuration (`.env`)](#-environment-configuration-env)
- [Folder Structure](#-folder-structure)
- [API Endpoints Reference](#-api-endpoints-reference)
- [Troubleshooting & FAQ](#-troubleshooting--faq)
- [License](#-license)

---

## 🌟 Overview & Core Value

Traditional personal finance tracking using spreadsheets (Excel, Google Sheets) or legacy desktop software inevitably faces severe operational hurdles:
- **Balance Desynchronization:** Manual updates between cash, digital wallets, bank transfers, and debtor lists cause persistent reconciliation discrepancies.
- **Absence of Identity Verification:** Recording loans manually leads to typographical mistakes, unverified names, and lack of tax/legal identification.
- **Static Visuals:** Spreadsheets lack interactive multi-dimensional drilldown analytics, cloud-style monthly quota monitoring, and unified cross-account tracking.

**myFin** solves these challenges by combining:
1. **Mathematical Consistency via Database Triggers:** Every income, expense, loan disbursement, and loan payment updates account balances through ACID MariaDB triggers, eliminating application-level race conditions.
2. **Modern Single-Page Application (SPA):** High-speed reactive frontend built with Vue 3, Vite, and Pinia, operating inside a responsive Bootstrap 3 layout with static modal defense (`backdrop="static"`).
3. **Multi-Tenant Row-Level Security:** Strict multi-user architecture where every user operates their independent accounts, categories, loans, assets, and projects with zero cross-tenant visibility.
4. **Automated Identity Verification:** Direct integration with **Decolecta API** to query and auto-fill official DNI (RENIEC) and RUC (SUNAT) records for borrowers.
5. **Interactive Executive Analytics:** Highcharts 12-month grouped comparative bar chart with multi-dimensional filtering (Year, Month, Category) and instant granular drilldown on bar click.

---

## 🚀 Key Features & Modules

### 1. Executive Dashboard & Interactive Calendar
- **Real-Time Financial Metrics:** Instant visibility over liquid balance, monthly expenditures, pending loans, and wealth net worth.
- **Spending Quota Monitor:** Azure-style monthly expenditure limit with progressive color-coded progress bars and threshold warning badges.
- **Full Interactive Calendar:** Displays daily incomes, expenses, and loan disbursements directly within their calendar date cells.

### 2. Incomes Management (`/income`)
- Record recurring (salary, monthly services) and one-off income streams.
- Direct association with destination accounts (*Efectivo*, *BCP*, *BBVA*, *Interbank*, *Yape*, *Plin*) and projects.
- Search, dynamic pagination, and CSV bulk import.

### 3. Expenses Management (`/expense`)
- Record daily expenditures categorized by financial activity.
- Receipt/voucher image attachment support.
- Real-time balance deduction from the origin account.

### 4. Loans & Borrowers Management (`/loans` & `/borrowers`)
- **Loans Engine:** Track capital lent, interest models (**None**, **Percentage %**, **Fixed S/**), repayment dates, and payment statuses (**PENDIENTE**, **PARCIAL**, **PAGADO**).
- **Loan Payments (Amortizations):** Register partial or complete loan payments directly crediting destination accounts.
- **Prestatarios (Borrowers) Catalog:** Dedicated table storing borrower documents, names, phones, and addresses.
- **Decolecta API Integration:** One-click DNI/RUC query automatically auto-populating full names and fiscal addresses.
- **On-the-Fly Borrower Registration:** Register new borrowers directly from the loan modal without losing form progress.

### 5. Projects Management (`/projects`)
- Oversee client-based and personal projects with defined budgets and delivery dates.
- Link incomes and expenses directly to projects to calculate real-time net profitability.

### 6. Wealth & Physical Assets (`/assets`)
- Inventory physical possessions, equipment, vehicles, and real estate with estimated market values.
- Real-time inclusion into the user's total Net Worth calculation.

### 7. Accounts & Financial Categories (`/accounts`)
- Manage multi-currency accounts (Soles S/, USD $) across banks, cash reserves, and digital wallets.
- Configure customized income and expense categories.

### 8. Executive Reports & Visual Analytics (`/reports`)
- Comprehensive KPI summaries (Disponible, Gastos Año, Préstamos Pendientes, Balance Total, Activos, Patrimonio Neto).
- **Highcharts Grouped Comparative Bar Chart:** 12-month overview of Ingresos, Gastos, Préstamos, and Proyectos.
- **Granular Filter (Drilldown on Click):** Clicking any bar displays subcategory distributions and transaction-level tables.
- Print-ready reporting layout.

### 9. Settings & Security (`/settings`)
- Configure monthly spending limit thresholds.
- Update profile username and password with secure cryptographic hashing.

---

## 🏗 System Architecture

```
                    ┌─────────────────────────────────────────┐
                    │               Web Browser               │
                    │   Vue 3 SPA (Vite + Pinia + Highcharts) │
                    └────────────────────┬────────────────────┘
                                         │ JSON / HTTPS (Axios)
                                         ▼
                    ┌─────────────────────────────────────────┐
                    │      Backend API Layer (PHP 8.3)        │
                    │  Session Auth • CSRF Guard • Allowlist  │
                    └────────────┬──────────────────┬─────────┘
                                 │                  │
                 cURL Bearer API │                  │ Prepared SQL
                                 ▼                  ▼
                    ┌──────────────────────┐  ┌───────────────────────┐
                    │  Decolecta API       │  │  MariaDB 10.5+        │
                    │  (RENIEC / SUNAT)    │  │  Multi-Tenant Tables  │
                    │                      │  │  Views & Procedures   │
                    │                      │  │  ACID Balance Triggers│
                    └──────────────────────┘  └───────────────────────┘
```

---

## 💻 Technology Stack

| Layer | Technologies |
| :--- | :--- |
| **Backend** | PHP 8.1 / 8.2 / 8.3, Apache 2.4, MariaDB 10.5+ / MySQL 8.0+ |
| **Frontend SPA** | Vue 3 (Composition API), Vite 8, Pinia 4, Vue Router 4, Axios |
| **Styling & UI** | Bootstrap 3 (Custom Light Theme), Font Awesome, Glyphicons |
| **Charts & Analytics** | Highcharts 12 (Exporting, Drilldown, Grouped Column Bars) |
| **Identity API** | Decolecta API (RENIEC DNI / SUNAT RUC endpoints) |
| **Package Managers**| Composer (PHP), npm (Node.js) |

---

## 🛡 Security & Threat Mitigations

- **Multi-Tenant Row-Level Security:** Every financial record is tagged with `created_by`. All database operations enforce `WHERE created_by = ? AND is_deleted = 0`. Server-side validation helper `verifyOwnedEntity()` validates ownership against an allowlist before performing any mutation.
- **Authentication & Rate Limiting:** Passwords hashed with SHA-256. Progressive lockout mechanism blocks brute-force attempts after 5 failures for 15 minutes.
- **Anti-CSRF Defense:** Cryptographic tokens generated per session, verified on all state-changing `POST` requests and injected into Axios headers (`X-CSRF-Token`).
- **SQL Injection Prevention:** 100% of queries utilize prepared statements (`mysqli::prepare()`) with typed parameter binding.
- **XSS Sanitization:** Comprehensive input sanitization via `cleanInput()`, output encoding via `htmlspecialchars()`, and automatic escaping by Vue template interpolation.
- **Accidental Modal Closure Protection:** Vue modals enforce `backdrop="static"` and `keyboard="false"`. Dialogs cannot close inadvertently by clicking outside.

---

## 🔍 Decolecta API Integration (RENIEC / SUNAT)

The platform integrates with Decolecta API to eliminate manual entry errors when recording borrowers:
- **DNI Lookup:** Queries `https://api.decolecta.com/v1/reniec/dni?numero={dni}`. Auto-populates full personal names directly from official RENIEC records.
- **RUC Lookup:** Queries `https://api.decolecta.com/v1/sunat/ruc?numero={ruc}`. Auto-populates company legal name (*razón social*), fiscal address, district, province, and department.

Configured in `.env`:
```env
DECOLECTA_API_TOKEN=your_token_here
DECOLECTA_API_URL=https://api.decolecta.com/v1
```

---

## 📊 Highcharts Executive Analytics

Located in `/app.php#/reports`:
- **12-Month Grouped Bar Chart:** Compares **Ingresos** (`#3498db`), **Gastos** (`#5b2c6f`), **Préstamos** (`#2ecc71`), and **Proyectos** (`#e67e22`) across the year.
- **Multi-Dimensional Filters:**
  - Dynamic **Year** dropdown.
  - **Month** selector (Full Year or single month focus).
  - Instant **Category** visibility toggles.
- **Interactive Drilldown (Bar Click):**
  - Clicking any bar triggers a granular drilldown for that month and activity.
  - Displays activity breakdown cards with percentage progress bars.
  - Renders a searchable, paginated transaction table of underlying records.
- **Export Capabilities:** Export chart to PNG, JPEG, PDF, SVG, or view in fullscreen.

---

## ⚙ Prerequisites & System Requirements

- **PHP:** 8.1, 8.2, or 8.3 with extensions: `mysqli`, `curl`, `mbstring`, `json`, `fileinfo`.
- **Database:** MariaDB 10.5+ or MySQL 8.0+.
- **Web Server:** Apache 2.4 (with `mod_rewrite` enabled) or Nginx.
- **Node.js & npm:** Node.js 18+ and npm 9+ (required for building frontend assets).

---

## 📦 Installation & Quick Start Guide

### 1. Clone the Repository
```bash
git clone https://github.com/your-username/personal-finance.git
cd personal-finance
```

### 2. Configure Environment Variables
Copy the example environment file and configure your credentials:
```bash
cp .env.example .env
```
Edit `.env` to configure your database connection and Decolecta API token:
```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_USER=your_db_user
DB_PASS=your_db_password
DB_NAME=personal_finance

DECOLECTA_API_TOKEN=your_token_here
DECOLECTA_API_URL=https://api.decolecta.com/v1
```

### 3. Initialize the Database
Create the database and import the complete schema:
```bash
mysql -u your_db_user -p -e "CREATE DATABASE personal_finance CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u your_db_user -p personal_finance < database/schema_complete.sql
```
*(Optional)* Import realistic sample seed data:
```bash
mysql -u your_db_user -p personal_finance < database/seed_samples_from_images.sql
```

### 4. Compile the Frontend SPA
Navigate to the `spa` directory, install npm dependencies, and compile the production bundle:
```bash
cd spa
npm install
npm run build
cd ..
```
The compiled bundle will be output to `spa/dist/` and automatically served by `app.php`.

### 5. Configure Web Server Permissions
Ensure write permissions for receipt image uploads:
```bash
chmod -R 775 assests/images/
```

### 6. Access the Application
Open your browser and navigate to:
```
http://localhost/personal-finance/
```
Default demo credentials:
- **Username:** `admin`
- **Password:** `admin`

---

## 🔧 Environment Configuration (`.env`)

| Variable | Description | Default |
| :--- | :--- | :--- |
| `APP_NAME` | Application title rendered across header and pages | `myFin` |
| `APP_ENV` | Application environment (`development` / `production`) | `development` |
| `APP_DEBUG` | Verbose debug flag | `true` |
| `APP_URL` | Base canonical application URL | `http://localhost/personal-finance` |
| `DB_HOST` | Database server host | `127.0.0.1` |
| `DB_PORT` | Database server port | `3306` |
| `DB_USER` | Database username | `admin` |
| `DB_PASS` | Database password | `******` |
| `DB_NAME` | Database schema name | `personal_finance` |
| `DB_CHARSET` | Character encoding | `utf8mb4` |
| `DECOLECTA_API_TOKEN`| Bearer token for Decolecta API | `sk_...` |
| `DECOLECTA_API_URL`  | Endpoint URL for Decolecta service | `https://api.decolecta.com/v1` |

---

## 📁 Folder Structure

```
personal-finance/
├── app.php                       # Authenticated host shell for the Vue 3 SPA
├── dashboard.php                 # Direct dashboard controller
├── income.php                    # Direct incomes controller
├── expense.php                   # Direct expenses controller
├── loans.php                     # Direct loans controller
├── projects.php                  # Direct projects controller
├── assets.php                    # Direct assets controller
├── accounts.php                  # Direct accounts controller
├── finance_report.php            # Direct reports controller
├── setting.php                   # Direct settings controller
├── index.php                     # Authentication entry & login page
├── register.php                  # User registration page
├── logout.php                    # Session termination script
├── PDR.md                        # Project Design Report & Technical Specs
├── README.md                     # Complete project documentation
├── database/
│   ├── schema_complete.sql       # Turnkey database schema with triggers & procedures
│   ├── migration_multiuser_optimization.sql # Multi-tenant optimization script
│   └── seed_samples_from_images.sql         # Seed data based on real records
├── php_action/                   # Backend RESTful JSON Controllers
│   ├── core.php                  # Session and environment bootstrapper
│   ├── db_connect.php            # MariaDB connection & verifyOwnedEntity()
│   ├── security.php              # CSRF, XSS, rate-limiting & session security
│   ├── lookupDecolecta.php       # Decolecta API proxy for RENIEC/SUNAT queries
│   ├── fetchBorrowers.php        # Prestatarios list provider
│   ├── createBorrower.php        # Prestatario creation endpoint
│   ├── editBorrower.php          # Prestatario update endpoint
│   ├── removeBorrower.php        # Prestatario soft delete endpoint
│   ├── fetchLoans.php            # Loans list provider
│   ├── createLoan.php            # Loan creation endpoint
│   ├── editLoan.php              # Loan update endpoint
│   ├── removeLoan.php            # Loan soft delete endpoint
│   ├── addLoanPayment.php        # Loan amortization endpoint
│   ├── getReportsChartData.php   # Highcharts 12-month comparative & drilldown API
│   ├── getDashboardData.php      # Dashboard KPIs & quotas API
│   └── getFormOptions.php        # Centralized modal dropdown options API
└── spa/                          # Vue 3 + Vite + Pinia Single-Page Application
    ├── package.json              # SPA dependencies & build scripts
    ├── vite.config.js            # Vite configuration & alias definitions
    └── src/
        ├── api/                  # Axios HTTP client & API bindings
        ├── components/           # Reusable Vue components & modals
        │   ├── forms/            # Form components (Loan, Borrower, Expense, etc.)
        │   ├── AppModal.vue      # Static backdrop modal component
        │   ├── DataTable.vue     # Reusable paginated search table
        │   └── FinancialBarChart.vue # Highcharts grouped bar chart & drilldown
        ├── stores/               # Pinia stores with in-memory TTL caching
        ├── views/                # Routed views (Dashboard, Loans, Reports, etc.)
        └── router/               # Vue Router hash navigation definitions
```

---

## 📡 API Endpoints Reference

All endpoints return JSON responses with standard structure: `{ success: boolean, messages?: string, data?: any }`.

| Endpoint | Method | Purpose |
| :--- | :--- | :--- |
| `php_action/getDashboardData.php` | `GET` | Fetches dashboard summary, account totals, and spending limit status. |
| `php_action/fetchCalendarEvents.php` | `GET` | Fetches daily events (incomes, expenses, loans) for calendar cells. |
| `php_action/getFormOptions.php` | `GET` | Returns active accounts, categories, projects, and borrowers for modal dropdowns. |
| `php_action/lookupDecolecta.php` | `GET` | Queries Decolecta API for DNI (RENIEC) or RUC (SUNAT). |
| `php_action/fetchBorrowers.php` | `GET` | Returns formatted list of borrowers for DataTable. |
| `php_action/createBorrower.php` | `POST`| Registers a new borrower. |
| `php_action/editBorrower.php` | `POST`| Updates an existing borrower. |
| `php_action/removeBorrower.php` | `POST`| Soft-deletes a borrower. |
| `php_action/fetchLoans.php` | `GET` | Returns formatted list of loans with status badges and actions. |
| `php_action/createLoan.php` | `POST`| Registers a new loan disbursement. |
| `php_action/editLoan.php` | `POST`| Updates loan terms or status. |
| `php_action/addLoanPayment.php` | `POST`| Records an amortization payment towards an active loan. |
| `php_action/removeLoan.php` | `POST`| Soft-deletes a loan. |
| `php_action/fetchIncomes.php` | `GET` | Returns list of confirmed and pending income records. |
| `php_action/createIncome.php` | `POST`| Creates a new income record. |
| `php_action/fetchExpenses.php` | `GET` | Returns list of expenditures. |
| `php_action/createExpense.php` | `POST`| Creates a new expense entry with optional voucher upload. |
| `php_action/getReportsChartData.php` | `GET` | Returns 12-month series for Highcharts and granular drilldown data. |
| `php_action/updateSpendingLimit.php` | `POST`| Sets or updates the monthly budget limit. |

---

## ❓ Troubleshooting & FAQ

#### 1. Why do modals not close when clicking on the dark backdrop?
This is by design. All modals are configured with `backdrop="static"` and `keyboard="false"` to prevent users from accidentally discarding uncompleted form entries when clicking outside. Use the **Cancelar** or **Cerrar** buttons to dismiss a modal.

#### 2. How do I change the Vite build output?
Vite compiles into `/spa/dist`. If you modify files inside `/spa/src/`, run:
```bash
npm run build --prefix spa
```
Changes take effect immediately upon browser reload.

#### 3. Where are database balance updates handled?
Account balances (`accounts.current_balance`) are calculated by **MariaDB triggers** (`trg_incomes_*`, `trg_expenses_*`, `trg_loans_*`, `trg_loan_payments_*`). Do not add manual `UPDATE accounts` queries in PHP scripts, as triggers ensure transactional ACID consistency.

---

## 📄 License

Proprietary Software. Developed for **myFin Personal Finance & Wealth Management System**. All rights reserved.
