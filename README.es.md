# myFin — Sistema de Gestión de Finanzas Personales y Patrimonio

<p align="left">
  <strong>🌐 Language / Idioma:</strong>
  <a href="README.md">English</a> |
  <strong>Español</strong>
</p>

[![Versión PHP](https://img.shields.io/badge/PHP-8.1%20|%208.2%20|%208.3-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
[![MariaDB](https://img.shields.io/badge/MariaDB-10.5+-003545?style=flat-square&logo=mariadb&logoColor=white)](https://mariadb.org/)
[![Vue 3](https://img.shields.io/badge/Vue-3.5+-4FC08D?style=flat-square&logo=vue.js&logoColor=white)](https://vuejs.org/)
[![Vite](https://img.shields.io/badge/Vite-8.3+-646CFF?style=flat-square&logo=vite&logoColor=white)](https://vitejs.dev/)
[![Pinia](https://img.shields.io/badge/Pinia-4.1+-FFE56D?style=flat-square&logo=vue.js&logoColor=black)](https://pinia.vuejs.org/)
[![Highcharts](https://img.shields.io/badge/Highcharts-12.0+-8085e9?style=flat-square)](https://www.highcharts.com/)
[![Licencia](https://img.shields.io/badge/Licencia-Propietaria-red?style=flat-square)](#licencia)

> **myFin** es una plataforma integral de finanzas personales, gestión de patrimonio y control de préstamos a terceros, desarrollada para reemplazar el seguimiento tradicional en hojas de cálculo por una solución web automatizada, transaccional y lista para la nube.

---

## 📑 Tabla de Contenidos

- [Visión General y Valor Central](#-visión-general-y-valor-central)
- [Módulos y Funcionalidades Clave](#-módulos-y-funcionalidades-clave)
- [Arquitectura del Sistema](#-arquitectura-del-sistema)
- [Pila Tecnológica](#-pila-tecnológica)
- [Seguridad y Mitigación de Amenazas](#-seguridad-y-mitigación-de-amenazas)
- [Integración con Decolecta API (RENIEC / SUNAT)](#-integración-con-decolecta-api-reniec--sunat)
- [Análisis Ejecutivo con Highcharts](#-análisis-ejecutivo-con-highcharts)
- [Requisitos Previos del Sistema](#-requisitos-previos-del-sistema)
- [Guía de Instalación y Puesta en Marcha](#-guía-de-instalación-y-puesta-en-marcha)
- [Configuración del Entorno (`.env`)](#-configuración-del-entorno-env)
- [Estructura de Directorios](#-estructura-de-directorios)
- [Referencia de Endpoints API](#-referencia-de-endpoints-api)
- [Preguntas Frecuentes y Resolución de Problemas](#-preguntas-frecuentes-y-resolución-de-problemas)
- [Licencia](#-licencia)

---

## 🌟 Visión General y Valor Central

El control de finanzas personales mediante hojas de cálculo (Excel, Google Sheets) o sistemas heredados de escritorio enfrenta serias limitaciones:
- **Desincronización de Saldos:** Modificar saldos manualmente entre efectivo, billeteras digitales, bancos y deudores produce inconsistencias frecuentes.
- **Falta de Validación de Identidad:** Anotar deudores de manera manual genera errores tipográficos, falta de números de documento y datos fiscales desactualizados.
- **Gráficos Estáticos:** Las hojas de cálculo carecen de análisis multidimensional interactivo, alertas de cuota de gasto mensual al estilo Azure y visión unificada multicuenta.

**myFin** resuelve estas dificultades integrando:
1. **Consistencia Matemática mediante Triggers:** Los saldos de las cuentas se actualizan automáticamente mediante disparadores (*triggers*) en MariaDB ante cualquier ingreso, gasto o préstamo, eliminando errores de doble escritura en la aplicación.
2. **Aplicación de Página Única (SPA) Moderna:** Interfaz rápida y reactiva en Vue 3, Vite y Pinia, funcionando dentro del diseño clásico y probado de Bootstrap 3 con modales estáticos que previenen cierres accidentales.
3. **Aislamiento Multi-Usuario Estricto:** Cada usuario cuenta con sus propias cuentas, categorías, ingresos, gastos, préstamos y proyectos, garantizando privacidad total.
4. **Verificación de Identidad con Decolecta API:** Consulta y autocompletado en un clic de registros oficiales de DNI (RENIEC) y RUC (SUNAT).
5. **Analítica Interactiva con Highcharts:** Gráfico de barras comparativo de 12 meses con filtros dinámicos (Año, Mes, Categoría) y desglose granular con solo hacer clic en las barras.

---

## 🚀 Módulos y Funcionalidades Clave

### 1. Panel Principal (Dashboard) y Calendario Financiero
- **Métricas Ejecutivas en Tiempo Real:** Saldo disponible consolidado, gastos del mes, préstamos pendientes por cobrar y patrimonio neto.
- **Monitor de Cuota de Gasto Mensual:** Al estilo de presupuestos en la nube (Microsoft Azure), con barras de progreso cromáticas y alertas de umbral superado.
- **Calendario Completo Interactivo:** Muestra los eventos de ingresos, gastos y préstamos directamente en sus respectivas casillas de fecha.

### 2. Gestión de Ingresos (`/income`)
- Registro de ingresos fijos (mensualidades, salarios) e ingresos extraordinarios.
- Vinculación con cuentas de destino (*Efectivo*, *BCP*, *BBVA*, *Interbank*, *Yape*, *Plin*) y proyectos de software o servicios.
- Búsqueda en tiempo real, paginación dinámica e importación masiva por CSV.

### 3. Control de Gastos Diarios (`/expense`)
- Registro de gastos diarios clasificados por categorías financieras.
- Soporte para adjuntar imágenes de comprobantes o vouchers digitales.
- Deducción automática del saldo de la cuenta de origen.

### 4. Préstamos y Prestatarios (`/loans` y `/borrowers`)
- **Motor de Préstamos:** Control de capital prestado, modalidades de interés (**Sin Interés**, **Porcentaje %**, **Monto Fijo S/**), fecha de vencimiento y estados (**PENDIENTE**, **PARCIAL**, **PAGADO**).
- **Amortizaciones (Abonos de Pago):** Registro de pagos parciales o totales que acreditan fondos en la cuenta bancaria elegida.
- **Catálogo de Prestatarios:** Tabla exclusiva para registrar documento, nombres, teléfono, correo y dirección.
- **Consulta con Decolecta API:** Búsqueda automática en RENIEC (DNI) o SUNAT (RUC) que completa los datos en un instante.
- **Registro de Prestatario al Vuelo:** Posibilidad de registrar un nuevo prestatario directamente dentro del formulario del préstamo sin perder los datos ya escritos.

### 5. Proyectos Financieros (`/projects`)
- Supervisión de proyectos de clientes y proyectos personales con presupuesto asignado y fechas límite.
- Enlace directo de ingresos percibidos y gastos incurridos para determinar la rentabilidad real.

### 6. Activos Físicos y Financieros (`/assets`)
- Inventario de pertenencias, equipos de trabajo, vehículos e inmuebles con su valor estimado de mercado.
- Sumatoria automática en el cálculo del Patrimonio Neto del usuario.

### 7. Cuentas y Categorías Financieras (`/accounts`)
- Administración de cuentas en moneda nacional (S/) o extranjera (USD $) entre bancos, efectivo y billeteras móviles.
- Configuración de categorías personalizadas de ingresos y egresos.

### 8. Reportes Financieros y Analítica Visual (`/reports`)
- Resumen ejecutivo con indicadores clave (Saldo Disponible, Gasto Anual, Préstamos Pendientes, Balance Total, Activos y Ahorro Neto).
- **Gráfico de Barras Comparativo (Highcharts):** Comparativa mensual de Ingresos, Gastos, Préstamos y Proyectos.
- **Filtro Granular al Clic:** Al pulsar sobre una barra mensual, se despliega el desglose por tipo de actividad y la tabla de transacciones correspondiente.
- Función de impresión directa del informe financiero.

### 9. Configuración y Seguridad (`/settings`)
- Establecimiento del límite presupuestario de gasto mensual.
- Actualización de nombre de usuario y contraseña con cifrado seguro.

---

## 🏗 Arquitectura del Sistema

```
                    ┌─────────────────────────────────────────┐
                    │            Navegador Web                │
                    │   Vue 3 SPA (Vite + Pinia + Highcharts) │
                    └────────────────────┬────────────────────┘
                                         │ JSON / HTTPS (Axios)
                                         ▼
                    ┌─────────────────────────────────────────┐
                    │      Capa API Backend (PHP 8.3)         │
                    │ Autenticación • Token CSRF • Allowlist  │
                    └────────────┬──────────────────┬─────────┘
                                 │                  │
                 cURL Bearer API │                  │ SQL Preparado
                                 ▼                  ▼
                    ┌──────────────────────┐  ┌───────────────────────┐
                    │  Decolecta API       │  │  MariaDB 10.5+        │
                    │  (RENIEC / SUNAT)    │  │  Tablas Multi-Tenant  │
                    │                      │  │  Vistas y Procedimientos│
                    │                      │  │  Triggers de Balance  │
                    └──────────────────────┘  └───────────────────────┘
```

---

## 💻 Pila Tecnológica

| Capa | Tecnologías |
| :--- | :--- |
| **Backend** | PHP 8.1 / 8.2 / 8.3, Apache 2.4, MariaDB 10.5+ / MySQL 8.0+ |
| **Frontend SPA** | Vue 3 (Composition API), Vite 8, Pinia 4, Vue Router 4, Axios |
| **Estilos e Interfaz**| Bootstrap 3 (Tema Claro Personalizado), Font Awesome, Glyphicons |
| **Gráficos y Analítica** | Highcharts 12 (Módulo de Exportación, Drilldown, Columnas Agrupadas) |
| **API de Identidad** | Decolecta API (Endpoints oficiales RENIEC DNI / SUNAT RUC) |
| **Gestores de Paquetes**| Composer (PHP), npm (Node.js) |

---

## 🛡 Seguridad y Mitigación de Amenazas

- **Aislamiento Multi-Usuario a Nivel de Fila:** Todos los registros contienen `created_by`. Las operaciones imponen `WHERE created_by = ? AND is_deleted = 0`. La función `verifyOwnedEntity()` comprueba la propiedad de la entidad frente a una lista blanca antes de permitir cambios.
- **Autenticación y Protección por Tasa de Intentos:** Contraseñas hasheadas con SHA-256. Bloqueo temporal tras 5 intentos fallidos durante 15 minutos para repeler ataques de fuerza bruta.
- **Protección Anti-CSRF:** Token criptográfico por sesión verificado en todas las peticiones `POST` e inyectado en las cabeceras de Axios (`X-CSRF-Token`).
- **Prevención de Inyección SQL:** Todas las consultas sin excepción emplean sentencias preparadas (`mysqli::prepare()`) con enlace tipado de parámetros.
- **Sanitización contra XSS:** Limpieza de datos de entrada (`cleanInput()`), codificación de salida (`htmlspecialchars()`) y escape automático en las plantillas de Vue.
- **Blindaje de Modales:** Modales configurados con `backdrop="static"` y `keyboard="false"`. No pueden cerrarse por accidente haciendo clic afuera.

---

## 🔍 Integración con Decolecta API (RENIEC / SUNAT)

El sistema agiliza el registro de personas a quienes se les presta dinero:
- **Consulta DNI:** Llama a `https://api.decolecta.com/v1/reniec/dni?numero={dni}` y autocompleta nombres y apellidos según el padrón de RENIEC.
- **Consulta RUC:** Llama a `https://api.decolecta.com/v1/sunat/ruc?numero={ruc}` y completa la razón social, condición tributaria y domicilio fiscal.

Configuración en `.env`:
```env
DECOLECTA_API_TOKEN=tu_token_aqui
DECOLECTA_API_URL=https://api.decolecta.com/v1
```

---

## 📊 Análisis Ejecutivo con Highcharts

Disponible en `/app.php#/reports`:
- **Gráfico Comparativo de 12 Meses:** Compara **Ingresos** (`#3498db`), **Gastos** (`#5b2c6f`), **Préstamos** (`#2ecc71`) y **Proyectos** (`#e67e22`) en cada mes.
- **Filtros Multidimensionales:**
  - Selector de **Año** dinámico.
  - Filtro de **Mes** (Año Completo o mes específico).
  - Interruptores interactivos para ocultar o mostrar cada **Categoría**.
- **Desglose Granular al Clic:**
  - Al hacer clic sobre cualquier barra mensual, se abre el panel de desglose de ese mes y actividad.
  - Muestra tarjetas de distribución porcentual con barras de progreso.
  - Presenta la tabla completa de transacciones detalladas con buscador y paginación.
- **Exportación:** Permite descargar el gráfico en formato PNG, JPEG, PDF, SVG o abrirlo en pantalla completa.

---

## ⚙ Requisitos Previos del Sistema

- **PHP:** 8.1, 8.2 o 8.3 con extensiones: `mysqli`, `curl`, `mbstring`, `json`, `fileinfo`.
- **Base de Datos:** MariaDB 10.5+ o MySQL 8.0+.
- **Servidor Web:** Apache 2.4 (con `mod_rewrite` habilitado) o Nginx.
- **Node.js y npm:** Node.js 18+ y npm 9+ (necesarios para compilar el frontend SPA).

---

## 📦 Guía de Instalación y Puesta en Marcha

### 1. Clonar el Repositorio
```bash
git clone https://github.com/tu-usuario/personal-finance.git
cd personal-finance
```

### 2. Configurar Variables de Entorno
Copie el archivo de ejemplo y configure sus credenciales:
```bash
cp .env.example .env
```
Edite `.env` con los datos de su base de datos y token de Decolecta:
```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_USER=tu_usuario_bd
DB_PASS=tu_contraseña_bd
DB_NAME=personal_finance

DECOLECTA_API_TOKEN=tu_token_aqui
DECOLECTA_API_URL=https://api.decolecta.com/v1
```

### 3. Crear e Importar la Base de Datos
Cree la base de datos e importe la estructura completa turnkey:
```bash
mysql -u tu_usuario_bd -p -e "CREATE DATABASE personal_finance CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u tu_usuario_bd -p personal_finance < database/schema_complete.sql
```
*(Opcional)* Si desea cargar datos de ejemplo basados en registros reales:
```bash
mysql -u tu_usuario_bd -p personal_finance < database/seed_samples_from_images.sql
```

### 4. Compilar el Frontend SPA
Entre al directorio `spa`, instale las dependencias de npm y compile el paquete de producción:
```bash
cd spa
npm install
npm run build
cd ..
```
Los archivos optimizados se generarán en `spa/dist/` y serán servidos directamente por `app.php`.

### 5. Ajustar Permisos de Archivos
Asegure permisos de escritura para la subida de comprobantes:
```bash
chmod -R 775 assests/images/
```

### 6. Ingresar al Sistema
Abra su navegador web y visite:
```
http://localhost/personal-finance/
```
Credenciales de demostración iniciales:
- **Usuario:** `admin`
- **Contraseña:** `admin`

---

## 🔧 Configuración del Entorno (`.env`)

| Variable | Descripción | Valor por Defecto |
| :--- | :--- | :--- |
| `APP_NAME` | Nombre de la aplicación en el encabezado | `myFin` |
| `APP_ENV` | Entorno de ejecución (`development` / `production`) | `development` |
| `APP_DEBUG` | Mostrar errores detallados | `true` |
| `APP_URL` | URL base de la aplicación | `http://localhost/personal-finance` |
| `DB_HOST` | Host del servidor de base de datos | `127.0.0.1` |
| `DB_PORT` | Puerto de la base de datos | `3306` |
| `DB_USER` | Usuario de base de datos | `admin` |
| `DB_PASS` | Contraseña de base de datos | `******` |
| `DB_NAME` | Nombre de la base de datos | `personal_finance` |
| `DB_CHARSET` | Codificación de caracteres | `utf8mb4` |
| `DECOLECTA_API_TOKEN`| Token Bearer para el servicio Decolecta | `sk_...` |
| `DECOLECTA_API_URL`  | URL base de la API Decolecta | `https://api.decolecta.com/v1` |

---

## 📁 Estructura de Directorios

```
personal-finance/
├── app.php                       # Shell autenticado anfitrión de la SPA Vue 3
├── dashboard.php                 # Controlador clásico del dashboard
├── income.php                    # Controlador clásico de ingresos
├── expense.php                   # Controlador clásico de gastos
├── loans.php                     # Controlador clásico de préstamos
├── projects.php                  # Controlador clásico de proyectos
├── assets.php                    # Controlador clásico de activos
├── accounts.php                  # Controlador clásico de cuentas
├── finance_report.php            # Controlador clásico de reportes
├── setting.php                   # Controlador clásico de configuración
├── index.php                     # Página de inicio de sesión y autenticación
├── register.php                  # Página de registro de nuevos usuarios
├── logout.php                    # Finalización de sesión
├── PDR.md                        # Project Design Report (English)
├── PDR.es.md                     # Reporte de Diseño del Proyecto (Español)
├── README.md                     # Project Documentation (English)
├── README.es.md                  # Documentación del Proyecto (Español)
├── database/
│   ├── schema_complete.sql       # Esquema completo turnkey con triggers y rutinas
│   ├── migration_multiuser_optimization.sql # Optimización multi-usuario
│   └── seed_samples_from_images.sql         # Datos de prueba basados en casos reales
├── php_action/                   # Controladores RESTful JSON en PHP
│   ├── core.php                  # Inicialización de sesión y variables de entorno
│   ├── db_connect.php            # Conexión MariaDB y verifyOwnedEntity()
│   ├── security.php              # Seguridad CSRF, XSS, rate-limiting y sesiones
│   ├── lookupDecolecta.php       # Proxy cURL a la API Decolecta (RENIEC/SUNAT)
│   ├── fetchBorrowers.php        # Proveedor del listado de prestatarios
│   ├── createBorrower.php        # Registro de nuevo prestatario
│   ├── editBorrower.php          # Actualización de datos del prestatario
│   ├── removeBorrower.php        # Eliminación lógica de prestatario
│   ├── fetchLoans.php            # Listado de préstamos con insignias
│   ├── createLoan.php            # Registro de préstamo
│   ├── editLoan.php              # Edición de préstamo
│   ├── removeLoan.php            # Eliminación lógica de préstamo
│   ├── addLoanPayment.php        # Registro de abonos de amortización
│   ├── getReportsChartData.php   # API para Highcharts y detalle granular
│   ├── getDashboardData.php      # API de métricas del dashboard y cuotas
│   └── getFormOptions.php        # Opciones para desplegables de modales
└── spa/                          # Aplicación de Página Única en Vue 3
    ├── package.json              # Dependencias de npm y scripts de compilación
    ├── vite.config.js            # Configuración de empaquetado con Vite
    └── src/
        ├── api/                  # Cliente HTTP Axios y llamadas API
        ├── components/           # Componentes reutilizables y modales
        │   ├── forms/            # Formularios (Préstamo, Prestatario, Gasto, etc.)
        │   ├── AppModal.vue      # Modal protegido contra cierres accidentales
        │   ├── DataTable.vue     # Tabla reactiva con buscador y paginación
        │   └── FinancialBarChart.vue # Gráfico Highcharts de 12 meses con drilldown
        ├── stores/               # Stores de Pinia con caché TTL en memoria
        ├── views/                # Vistas enrutadas (Dashboard, Préstamos, etc.)
        └── router/               # Rutas hash de Vue Router
```

---

## 📡 Referencia de Endpoints API

Todas las respuestas del API devuelven formato JSON: `{ success: boolean, messages?: string, data?: any }`.

| Endpoint | Método | Finalidad |
| :--- | :--- | :--- |
| `php_action/getDashboardData.php` | `GET` | Métricas generales del dashboard, saldo de cuentas y estado de cuota. |
| `php_action/fetchCalendarEvents.php` | `GET` | Eventos diarios (ingresos, gastos, préstamos) para el calendario. |
| `php_action/getFormOptions.php` | `GET` | Listado de cuentas, categorías, proyectos y prestatarios para selectores. |
| `php_action/lookupDecolecta.php` | `GET` | Consulta oficial a RENIEC (DNI) o SUNAT (RUC) mediante Decolecta. |
| `php_action/fetchBorrowers.php` | `GET` | Listado de prestatarios para DataTable. |
| `php_action/createBorrower.php` | `POST`| Alta de nuevo prestatario. |
| `php_action/editBorrower.php` | `POST`| Modificación de datos del prestatario. |
| `php_action/removeBorrower.php` | `POST`| Eliminación lógica (*soft delete*) de prestatario. |
| `php_action/fetchLoans.php` | `GET` | Listado de préstamos otorgados con cálculos de saldos e intereses. |
| `php_action/createLoan.php` | `POST`| Registro de un nuevo préstamo otorgado. |
| `php_action/editLoan.php` | `POST`| Edición de condiciones del préstamo. |
| `php_action/addLoanPayment.php` | `POST`| Registro de amortización o pago de préstamo recibido. |
| `php_action/removeLoan.php` | `POST`| Eliminación lógica de préstamo. |
| `php_action/fetchIncomes.php` | `GET` | Listado de ingresos confirmados y pendientes. |
| `php_action/createIncome.php` | `POST`| Registro de nuevo ingreso. |
| `php_action/fetchExpenses.php` | `GET` | Listado de gastos registrados. |
| `php_action/createExpense.php` | `POST`| Registro de nuevo gasto con soporte para comprobante. |
| `php_action/getReportsChartData.php` | `GET` | Datos de las 4 series mensuales para Highcharts y detalle granular. |
| `php_action/updateSpendingLimit.php` | `POST`| Establece o actualiza el límite de gasto mensual. |

---

## ❓ Preguntas Frecuentes y Resolución de Problemas

#### 1. ¿Por qué los modales no se cierran al hacer clic en el fondo oscuro?
Es una medida de seguridad intencional. Todos los modales tienen configurado `backdrop="static"` y `keyboard="false"` para evitar que el usuario pierda información que está digitando por un clic accidental fuera de la ventana. Para cerrar un modal debe hacer clic explícitamente en **Cancelar** o en el botón de cerrar.

#### 2. ¿Cómo reflejo los cambios hechos en el código de Vue?
El código de Vue se compila dentro de `/spa/dist`. Si realiza modificaciones en los archivos de `/spa/src/`, ejecute:
```bash
npm run build --prefix spa
```
Los cambios se aplican de inmediato al recargar el navegador.

#### 3. ¿Dónde se calculan los saldos de las cuentas?
Los saldos de las cuentas (`accounts.current_balance`) son gestionados directamente por **Triggers en MariaDB** (`trg_incomes_*`, `trg_expenses_*`, `trg_loans_*`, `trg_loan_payments_*`). No agregue consultas manuales de `UPDATE accounts` en los scripts de PHP, ya que los triggers garantizan consistencia ACID automática.

---

## 📄 Licencia

Software Propietario. Desarrollado para **myFin — Sistema de Finanzas Personales y Patrimonio**. Todos los derechos reservados.
