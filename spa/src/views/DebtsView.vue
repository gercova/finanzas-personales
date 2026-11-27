<template>
  <div>
    <ol class="breadcrumb">
      <li><a href="#" @click.prevent="$router.push('/dashboard')">Inicio</a></li>
      <li v-if="currentTab === 'debts'" class="active">Préstamos Recibidos</li>
      <template v-else>
        <li><a href="#" @click.prevent="switchTab('debts')">Préstamos Recibidos</a></li>
        <li class="active">Acreedores / Entidades</li>
      </template>
    </ol>

    <!-- Navigation Tabs between Received Debts and Lenders -->
    <ul class="nav nav-tabs" style="margin-bottom:20px;">
      <li :class="{ active: currentTab === 'debts' }">
        <a href="#" @click.prevent="switchTab('debts')">
          <i class="glyphicon glyphicon-save text-danger"></i> Préstamos Recibidos (Deudas)
        </a>
      </li>
      <li :class="{ active: currentTab === 'lenders' }">
        <a href="#" @click.prevent="switchTab('lenders')">
          <i class="glyphicon glyphicon-briefcase text-primary"></i> Acreedores / Entidades
        </a>
      </li>
    </ul>

    <!-- ── TAB 1: GESTIÓN DE PRÉSTAMOS RECIBIDOS ─────────────────────────── -->
    <div v-show="currentTab === 'debts'">
      <div class="row" style="margin-bottom:20px;">
        <div class="col-xs-12 col-sm-4">
          <div class="panel panel-danger">
            <div class="panel-heading"><i class="glyphicon glyphicon-save"></i> Total Recibido (a Pagar)</div>
            <div class="panel-body text-center">
              <h3 style="margin:0;font-weight:700;" class="text-danger">S/ {{ fmtMoney(debtStats.recibido) }}</h3>
              <small class="text-muted">
                {{ (store.activeFilterCount > 0 || store.search) ? 'total filtrado' : 'obligaciones registradas' }}
              </small>
            </div>
          </div>
        </div>
        <div class="col-xs-12 col-sm-4">
          <div class="panel panel-success">
            <div class="panel-heading"><i class="glyphicon glyphicon-ok-sign"></i> Total Pagado</div>
            <div class="panel-body text-center">
              <h3 style="margin:0;font-weight:700;" class="text-success">S/ {{ fmtMoney(debtStats.pagado) }}</h3>
              <small class="text-muted">
                {{ (store.activeFilterCount > 0 || store.search) ? 'amortizado (filtrado)' : 'amortizado' }}
              </small>
            </div>
          </div>
        </div>
        <div class="col-xs-12 col-sm-4">
          <div class="panel panel-warning">
            <div class="panel-heading"><i class="glyphicon glyphicon-time"></i> Total Por Pagar</div>
            <div class="panel-body text-center">
              <h3 style="margin:0;font-weight:700;" class="text-warning">S/ {{ fmtMoney(debtStats.pendiente) }}</h3>
              <small class="text-muted">
                {{ (store.activeFilterCount > 0 || store.search) ? 'saldo pendiente (filtrado)' : 'saldo pendiente' }}
              </small>
            </div>
          </div>
        </div>
      </div>

      <!-- Clickable quick status filters -->
      <div style="margin-bottom:12px; display:flex; flex-wrap:wrap; align-items:center; gap:6px;">
        <button type="button" class="btn btn-xs"
                :style="{ background:'#e74c3c', color:'#fff', border: store.filters.status==='PENDIENTE' ? '2px solid #222' : '2px solid transparent', fontWeight:600 }"
                @click="toggleStatusQuick('PENDIENTE')">
          PENDIENTE
        </button>
        <button type="button" class="btn btn-xs"
                :style="{ background:'#f39c12', color:'#fff', border: store.filters.status==='PARCIAL' ? '2px solid #222' : '2px solid transparent', fontWeight:600 }"
                @click="toggleStatusQuick('PARCIAL')">
          PARCIAL
        </button>
        <button type="button" class="btn btn-xs"
                :style="{ background:'#8e44ad', color:'#fff', border: store.filters.status==='PAGADO' ? '2px solid #222' : '2px solid transparent', fontWeight:600 }"
                @click="toggleStatusQuick('PAGADO')">
          PAGADO
        </button>
        <button type="button" class="btn btn-xs btn-default"
                :class="{ active: store.filters.status==='UNPAID' }"
                @click="toggleStatusQuick('UNPAID')">
          <i class="glyphicon glyphicon-hourglass text-warning"></i> Por pagar
        </button>
        <button type="button" class="btn btn-xs btn-default"
                :class="{ active: store.filters.status==='OVERDUE' }"
                @click="toggleStatusQuick('OVERDUE')">
          <i class="glyphicon glyphicon-exclamation-sign text-danger"></i> Vencidos
        </button>
        <button v-if="store.filters.status" type="button" class="btn btn-xs btn-link" style="padding:2px 6px;" @click="store.filters.status = ''">
          Ver todos
        </button>
      </div>

      <div class="panel panel-default">
        <DataTable
          title="Gestión de Préstamos Recibidos"
          icon="glyphicon-save text-danger"
          :columns="debtColumns"
          :store="store"
          :row-style-fn="debtRowStyle"
        >
          <template #actions>
            <button class="btn btn-default btn-sm" @click="importRef.open()">
              <i class="glyphicon glyphicon-upload"></i> Importar
            </button>
            <button class="btn btn-danger" @click="openAdd">
              <i class="glyphicon glyphicon-plus-sign"></i> Registrar Préstamo Recibido
            </button>
          </template>

          <template #filters>
            <div style="background:#f9f9f9; border:1px solid #e5e5e5; border-radius:4px; padding:10px 12px; margin-bottom:12px;">
              <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; margin-bottom:10px;">
                <div style="font-size:12.5px; font-weight:700; color:#444;">
                  <i class="glyphicon glyphicon-filter text-primary"></i> Filtros de búsqueda
                  <span v-if="store.activeFilterCount > 0" class="badge" style="background:#337ab7; margin-left:4px;">
                    {{ store.activeFilterCount }}
                  </span>
                </div>
                <div style="display:flex; align-items:center; flex-wrap:wrap; gap:4px;">
                  <span class="text-muted" style="font-size:11px; margin-right:2px;">Rango rápido:</span>
                  <button type="button" class="btn btn-xs btn-default" @click="applyDatePreset('today')">Hoy</button>
                  <button type="button" class="btn btn-xs btn-default" @click="applyDatePreset('this_month')">Este mes</button>
                  <button type="button" class="btn btn-xs btn-default" @click="applyDatePreset('last_month')">Mes ant.</button>
                  <button type="button" class="btn btn-xs btn-default" @click="applyDatePreset('this_year')">Este año</button>
                  <button
                    v-if="store.activeFilterCount > 0 || store.search"
                    type="button"
                    class="btn btn-xs btn-danger"
                    style="margin-left:6px;"
                    @click="store.resetFilters()"
                  >
                    <i class="glyphicon glyphicon-remove"></i> Limpiar filtros
                  </button>
                </div>
              </div>

              <div class="row" style="margin-bottom:8px;">
                <div class="col-xs-12 col-sm-6 col-md-3" style="margin-bottom:6px;">
                  <label class="control-label" style="font-size:11.5px; color:#555; margin-bottom:3px; display:block;">
                    <i class="glyphicon glyphicon-user"></i> Acreedor / Entidad
                  </label>
                  <select class="form-control input-sm" v-model="store.filters.lender">
                    <option value="">Todos los acreedores</option>
                    <option v-for="l in lenderFilterOptions" :key="l.value" :value="l.value">
                      {{ l.label }}
                    </option>
                  </select>
                </div>

                <div class="col-xs-12 col-sm-6 col-md-3" style="margin-bottom:6px;">
                  <label class="control-label" style="font-size:11.5px; color:#555; margin-bottom:3px; display:block;">
                    <i class="glyphicon glyphicon-briefcase"></i> Tipo de Acreedor
                  </label>
                  <select class="form-control input-sm" v-model="store.filters.lenderType">
                    <option value="">Todos los tipos</option>
                    <option value="PERSONA">Persona Natural</option>
                    <option value="EMPRESA">Empresa</option>
                    <option value="ENTIDAD_FINANCIERA">Banco / Entidad Financiera</option>
                  </select>
                </div>

                <div class="col-xs-12 col-sm-6 col-md-3" style="margin-bottom:6px;">
                  <label class="control-label" style="font-size:11.5px; color:#555; margin-bottom:3px; display:block;">
                    <i class="glyphicon glyphicon-credit-card"></i> Cuenta Destino
                  </label>
                  <select class="form-control input-sm" v-model="store.filters.accountId">
                    <option value="">Todas las cuentas</option>
                    <option v-for="a in accountFilterOptions" :key="a.value" :value="a.value">
                      {{ a.label }}
                    </option>
                  </select>
                </div>

                <div class="col-xs-12 col-sm-6 col-md-3" style="margin-bottom:6px;">
                  <label class="control-label" style="font-size:11.5px; color:#555; margin-bottom:3px; display:block;">
                    <i class="glyphicon glyphicon-tag"></i> Estado
                  </label>
                  <select class="form-control input-sm" v-model="store.filters.status">
                    <option value="">Todos los estados</option>
                    <option value="PENDIENTE">Pendientes</option>
                    <option value="PARCIAL">Parciales</option>
                    <option value="UNPAID">Por pagar (Pendiente / Parcial)</option>
                    <option value="PAGADO">Pagados</option>
                    <option value="OVERDUE">Vencidos (Con mora)</option>
                  </select>
                </div>
              </div>

              <div class="row">
                <div class="col-xs-12 col-sm-6 col-md-3" style="margin-bottom:4px;">
                  <label class="control-label" style="font-size:11.5px; color:#555; margin-bottom:3px; display:block;">
                    <i class="glyphicon glyphicon-calendar"></i> Filtrar por Fecha
                  </label>
                  <select class="form-control input-sm" v-model="store.filters.dateField">
                    <option value="debt_date">Fecha de Préstamo</option>
                    <option value="due_date">Fecha de Vencimiento</option>
                  </select>
                </div>

                <div class="col-xs-6 col-sm-3 col-md-2" style="margin-bottom:4px;">
                  <label class="control-label" style="font-size:11.5px; color:#555; margin-bottom:3px; display:block;">
                    Desde
                  </label>
                  <input type="date" class="form-control input-sm" v-model="store.filters.dateFrom" />
                </div>

                <div class="col-xs-6 col-sm-3 col-md-2" style="margin-bottom:4px;">
                  <label class="control-label" style="font-size:11.5px; color:#555; margin-bottom:3px; display:block;">
                    Hasta
                  </label>
                  <input type="date" class="form-control input-sm" v-model="store.filters.dateTo" />
                </div>

                <div class="col-xs-12 col-sm-6 col-md-2" style="margin-bottom:4px;">
                  <label class="control-label" style="font-size:11.5px; color:#555; margin-bottom:3px; display:block;">
                    <i class="glyphicon glyphicon-percent"></i> Interés
                  </label>
                  <select class="form-control input-sm" v-model="store.filters.interestFilter">
                    <option value="">Todos</option>
                    <option value="WITH">Con interés</option>
                    <option value="NONE">Sin interés</option>
                  </select>
                </div>

                <div class="col-xs-12 col-sm-6 col-md-3" style="margin-bottom:4px;">
                  <label class="control-label" style="font-size:11.5px; color:#555; margin-bottom:3px; display:block;">
                    <i class="glyphicon glyphicon-usd"></i> Capital (S/)
                  </label>
                  <div style="display:flex; gap:6px; align-items:center;">
                    <input type="number" step="0.01" min="0" class="form-control input-sm" placeholder="Mín." v-model="store.filters.minAmount" />
                    <span class="text-muted">-</span>
                    <input type="number" step="0.01" min="0" class="form-control input-sm" placeholder="Máx." v-model="store.filters.maxAmount" />
                  </div>
                </div>
              </div>
            </div>
          </template>

          <template #cell-lender_name="{ row }">
            <span>{{ row.lender_name }}</span>
            <br />
            <small class="text-muted">
              <span class="label" :style="{ background: lenderTypeColor(row.lender_type), fontSize: '9px', padding: '1px 4px', marginRight: '3px' }">
                {{ lenderTypeLabel(row.lender_type) }}
              </span>
              <template v-if="row.document_number">
                <span class="label label-default" style="font-size:9px;padding:1px 4px;">{{ row.document_type }}</span>
                {{ row.document_number }}
              </template>
            </small>
          </template>
          <template #cell-account_name="{ row }">{{ row.account_name || '-' }}</template>
          <template #cell-debt_date="{ row }">{{ fmtDate(row.debt_date) }}</template>
          <template #cell-due_date="{ row }">{{ row.due_date ? fmtDate(row.due_date) : '-' }}</template>
          <template #cell-principal_amount="{ row }">S/ {{ fmtMoney(row.principal_amount) }}</template>
          <template #cell-interest="{ row }">{{ formatInterest(row) }}</template>
          <template #cell-total_payable="{ row }">S/ {{ fmtMoney(row.total_payable) }}</template>
          <template #cell-amount_paid="{ row }">S/ {{ fmtMoney(row.amount_paid) }}</template>
          <template #cell-balance_pending="{ row }">S/ {{ fmtMoney(row.balance_pending) }}</template>
          <template #cell-status="{ row }">
            <span class="label" :style="{ background: statusColor(row.status) }">{{ row.status }}</span>
          </template>
          <template #cell-actions="{ row }">
            <ActionDropdown>
              <li>
                <a href="#" @click.prevent="openPayDebt(row.id)">
                  <i class="glyphicon glyphicon-usd text-success"></i> Registrar Pago / Cuota
                </a>
              </li>
              <li class="divider"></li>
              <li class="dropdown-header">Cambiar Estado</li>
              <li v-if="row.status !== 'PAGADO'">
                <a href="#" @click.prevent="changeDebtStatus(row.id, 'PAGADO')">
                  <i class="glyphicon glyphicon-ok-sign text-success"></i> Marcar como PAGADO
                </a>
              </li>
              <li v-if="row.status !== 'PENDIENTE'">
                <a href="#" @click.prevent="changeDebtStatus(row.id, 'PENDIENTE')">
                  <i class="glyphicon glyphicon-time text-danger"></i> Marcar como PENDIENTE
                </a>
              </li>
              <li v-if="row.status !== 'PARCIAL' && row.balance_pending > 0 && row.amount_paid > 0">
                <a href="#" @click.prevent="changeDebtStatus(row.id, 'PARCIAL')">
                  <i class="glyphicon glyphicon-adjust text-warning"></i> Marcar como PARCIAL
                </a>
              </li>
              <li class="divider"></li>
              <li>
                <a href="#" @click.prevent="editDebt(row.id)">
                  <i class="glyphicon glyphicon-edit"></i> Editar
                </a>
              </li>
              <li class="divider"></li>
              <li>
                <a href="#" @click.prevent="removeDebt(row.id)">
                  <i class="glyphicon glyphicon-trash text-danger"></i> Eliminar
                </a>
              </li>
            </ActionDropdown>
          </template>
        </DataTable>
      </div>
    </div>

    <!-- ── TAB 2: LISTADO DE ACREEDORES / ENTIDADES ──────────────────────── -->
    <div v-show="currentTab === 'lenders'">
      <div class="panel panel-default">
        <DataTable
          title="Acreedores y Entidades Financieras"
          icon="glyphicon-briefcase text-primary"
          :columns="lenderColumns"
          :store="lenderStore"
        >
          <template #actions>
            <button class="btn btn-primary" @click="openAddLender">
              <i class="glyphicon glyphicon-plus-sign"></i> Agregar acreedor / entidad
            </button>
          </template>

          <template #cell-lender_type="{ row }">
            <span class="label" :style="{ background: lenderTypeColor(row.lender_type) }">
              {{ lenderTypeLabel(row.lender_type) }}
            </span>
          </template>
          <template #cell-document="{ row }">
            <span class="label label-info" style="font-size:10px;padding:2px 5px;margin-right:4px;">{{ row.document_type }}</span>
            {{ row.document_number }}
          </template>
          <template #cell-phone="{ row }">{{ row.phone || '-' }}</template>
          <template #cell-address="{ row }">{{ row.address || '-' }}</template>
          <template #cell-status="{ row }">
            <span :class="row.status === 1 ? 'label label-success' : 'label label-danger'">
              {{ row.status === 1 ? 'Disponible' : 'No disponible' }}
            </span>
          </template>
          <template #cell-actions="{ row }">
            <ActionDropdown :show-icon="false">
              <li><a href="#" @click.prevent="editLender(row.id)"><i class="glyphicon glyphicon-edit"></i> Editar</a></li>
              <li class="divider"></li>
              <li><a href="#" @click.prevent="removeLender(row.id)"><i class="glyphicon glyphicon-trash text-danger"></i> Eliminar</a></li>
            </ActionDropdown>
          </template>
        </DataTable>
      </div>
    </div>

    <!-- Add Debt Modal -->
    <AppModal v-model="showAdd" title="Registrar Préstamo Recibido" icon="glyphicon-plus-sign"
              header-color="#c0392b" size="lg" @closed="resetForm">
      <template #body>
        <div v-if="formError" class="alert alert-danger">{{ formError }}</div>
        <DebtForm
          ref="addFormRef"
          :accounts="accounts"
          :lenders="lenders"
          @request-new-lender="openAddLenderFromDebt"
        />
      </template>
      <template #footer>
        <button class="btn btn-default" @click="showAdd = false">Cancelar</button>
        <button class="btn btn-danger" :disabled="saving" @click="submitAdd">
          <i class="glyphicon glyphicon-floppy-disk"></i>
          {{ saving ? 'Guardando…' : 'Guardar' }}
        </button>
      </template>
    </AppModal>

    <!-- Edit Debt Modal -->
    <AppModal v-model="showEdit" title="Editar Préstamo Recibido" icon="glyphicon-edit"
              header-color="#2980b9" size="lg" @closed="resetForm">
      <template #body>
        <div v-if="formError" class="alert alert-danger">{{ formError }}</div>
        <DebtForm
          ref="editFormRef"
          :accounts="accounts"
          :lenders="lenders"
          :edit-id="editId"
          @request-new-lender="openAddLenderFromDebt"
        />
      </template>
      <template #footer>
        <button class="btn btn-default" @click="showEdit = false">Cancelar</button>
        <button class="btn btn-primary" :disabled="saving" @click="submitEdit">
          <i class="glyphicon glyphicon-floppy-disk"></i>
          {{ saving ? 'Guardando…' : 'Actualizar' }}
        </button>
      </template>
    </AppModal>

    <!-- Payment Modal -->
    <AppModal v-model="showPay" title="Registrar Pago de Préstamo Recibido" icon="glyphicon-usd"
              header-color="#27ae60" @closed="resetForm">
      <template #body>
        <div v-if="formError" class="alert alert-danger">{{ formError }}</div>
        <div v-if="payInfo" class="alert alert-info" style="padding:8px 12px;font-size:13px;">
          Acreedor: <strong>{{ payInfo.lender }}</strong>
          — Por pagar: <strong>S/ {{ payInfo.pending }}</strong>
        </div>
        <DebtPaymentForm ref="payFormRef" :accounts="accounts" />
      </template>
      <template #footer>
        <button class="btn btn-default" @click="showPay = false">Cancelar</button>
        <button class="btn btn-success" :disabled="saving" @click="submitPayment">
          <i class="glyphicon glyphicon-ok"></i>
          {{ saving ? 'Guardando…' : 'Registrar Pago' }}
        </button>
      </template>
    </AppModal>

    <!-- Add Lender Modal -->
    <AppModal v-model="showAddLender" title="Agregar Acreedor / Entidad" icon="glyphicon-plus-sign"
              header-color="#34495e" size="md" @closed="resetLenderForm">
      <template #body>
        <div v-if="lenderFormError" class="alert alert-danger">{{ lenderFormError }}</div>
        <LenderForm ref="addLenderRef" />
      </template>
      <template #footer>
        <button class="btn btn-default" @click="showAddLender = false">Cancelar</button>
        <button class="btn btn-primary" :disabled="savingLender" @click="submitAddLender">
          <i class="glyphicon glyphicon-floppy-disk"></i>
          {{ savingLender ? 'Guardando…' : 'Guardar' }}
        </button>
      </template>
    </AppModal>

    <!-- Edit Lender Modal -->
    <AppModal v-model="showEditLender" title="Editar Acreedor / Entidad" icon="glyphicon-edit"
              header-color="#2980b9" size="md" @closed="resetLenderForm">
      <template #body>
        <div v-if="lenderFormError" class="alert alert-danger">{{ lenderFormError }}</div>
        <LenderForm ref="editLenderRef" :edit-id="editLenderId" />
      </template>
      <template #footer>
        <button class="btn btn-default" @click="showEditLender = false">Cancelar</button>
        <button class="btn btn-primary" :disabled="savingLender" @click="submitEditLender">
          <i class="glyphicon glyphicon-floppy-disk"></i>
          {{ savingLender ? 'Guardando…' : 'Actualizar' }}
        </button>
      </template>
    </AppModal>

    <ImportModal ref="importRef" module="debt" @imported="onMutated" />
  </div>
</template>

<script setup>
import { ref, computed, inject, nextTick, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useDebtStore } from '@/stores/useDebtStore'
import { useLenderStore } from '@/stores/useLenderStore'
import { useAuthStore } from '@/stores/useAuthStore'
import { useDashboardStore } from '@/stores/useDashboardStore'
import { useReportStore } from '@/stores/useReportStore'
import { useAccountStore } from '@/stores/useAccountStore'
import { useFormOptionsStore } from '@/stores/useFormOptionsStore'
import DataTable       from '@/components/DataTable.vue'
import ActionDropdown  from '@/components/ActionDropdown.vue'
import AppModal        from '@/components/AppModal.vue'
import ImportModal     from '@/components/ImportModal.vue'
import DebtForm        from '@/components/forms/DebtForm.vue'
import DebtPaymentForm from '@/components/forms/DebtPaymentForm.vue'
import LenderForm      from '@/components/forms/LenderForm.vue'
import { fmtMoney, fmtDate } from '@/utils/format'
import * as api from '@/api'

const route          = useRoute()
const router         = useRouter()
const toast          = inject('toast')
const store          = useDebtStore()
const lenderStore    = useLenderStore()
const authStore      = useAuthStore()
const dashboardStore = useDashboardStore()
const reportStore    = useReportStore()
const accountStore   = useAccountStore()
const optionsStore   = useFormOptionsStore()

function interestNumericValue(row) {
  if (!row.interest_type || row.interest_type === 'NONE' || row.interest_type === 'NINGUNO') return 0
  if (row.interest_type === 'PERCENTAGE' || row.interest_type === 'PORCENTAJE') {
    return ((Number(row.principal_amount) || 0) * (Number(row.interest_value) || 0)) / 100
  }
  return Number(row.interest_value) || 0
}

const debtColumns = [
  { key: '_index',           label: '#' },
  { key: 'lender_name',      label: 'Acreedor / Entidad' },
  { key: 'concept',          label: 'Concepto' },
  { key: 'account_name',     label: 'Cuenta Destino' },
  { key: 'debt_date',        label: 'Fecha Préstamo' },
  { key: 'due_date',         label: 'Vencimiento' },
  { key: 'principal_amount', label: 'Capital' },
  { key: 'interest',         label: 'Interés',       sortValue: interestNumericValue },
  { key: 'total_payable',    label: 'Total a Pagar' },
  { key: 'amount_paid',      label: 'Pagado' },
  { key: 'balance_pending',  label: 'Por Pagar' },
  { key: 'status',           label: 'Estado' },
  { key: 'actions',          label: 'Acción' },
]

const lenderColumns = [
  { key: '_index',      label: '#' },
  { key: 'lender_type', label: 'Tipo' },
  { key: 'document',    label: 'Documento', sortValue: r => `${r.document_type || ''} ${r.document_number || ''}`.trim() },
  { key: 'full_name',   label: 'Nombre / Razón Social' },
  { key: 'phone',       label: 'Teléfono' },
  { key: 'address',     label: 'Dirección' },
  { key: 'status',      label: 'Estado' },
  { key: 'actions',     label: 'Opciones' },
]

function debtRowStyle(row) {
  if (row.status === 'PENDIENTE') return { backgroundColor: '#ffcccc' }
  if (row.status === 'PARCIAL')   return { backgroundColor: '#fff3cd' }
  if (row.status === 'PAGADO')    return { backgroundColor: '#e8d5f5' }
  return undefined
}

function statusColor(status) {
  if (status === 'PENDIENTE') return '#e74c3c'
  if (status === 'PARCIAL')   return '#f39c12'
  if (status === 'PAGADO')    return '#8e44ad'
  return '#777'
}

function lenderTypeLabel(type) {
  if (type === 'ENTIDAD_FINANCIERA') return 'Banco / Ent. Financiera'
  if (type === 'EMPRESA') return 'Empresa'
  return 'Persona'
}

function lenderTypeColor(type) {
  if (type === 'ENTIDAD_FINANCIERA') return '#2980b9'
  if (type === 'EMPRESA') return '#8e44ad'
  return '#16a085'
}

function formatInterest(row) {
  if (!row.interest_type || row.interest_type === 'NONE' || row.interest_type === 'NINGUNO') return '-'
  if (row.interest_type === 'PERCENTAGE' || row.interest_type === 'PORCENTAJE') {
    return `${(Number(row.interest_value) || 0).toFixed(1)}%`
  }
  return `S/ ${fmtMoney(row.interest_value)}`
}

// Tab navigation state
const currentTab = ref(
  route.query.tab === 'lenders' || route.meta.tab === 'lenders' ? 'lenders' : 'debts'
)

watch(() => [route.query.tab, route.meta.tab], ([qTab, mTab]) => {
  if (qTab === 'lenders' || mTab === 'lenders') {
    currentTab.value = 'lenders'
    lenderStore.load()
  } else if (qTab === 'debts' || !qTab) {
    currentTab.value = 'debts'
  }
})

function switchTab(tab) {
  currentTab.value = tab
  if (route.path === '/lenders' && tab === 'debts') {
    router.push('/debts')
  } else {
    router.replace({ query: { ...route.query, tab } })
  }
  if (tab === 'lenders') {
    lenderStore.load()
  }
}

// Debt Modals state
const showAdd     = ref(false)
const showEdit    = ref(false)
const showPay     = ref(false)
const saving      = ref(false)
const formError   = ref('')
const editId      = ref(null)
const payInfo     = ref(null)
const importRef   = ref(null)
const addFormRef  = ref(null)
const editFormRef = ref(null)
const payFormRef  = ref(null)

// Lender Modals state
const showAddLender   = ref(false)
const showEditLender  = ref(false)
const savingLender    = ref(false)
const lenderFormError = ref('')
const editLenderId    = ref(null)
const addLenderRef    = ref(null)
const editLenderRef   = ref(null)
const openedFromDebt  = ref(false)

const accounts = computed(() => optionsStore.accounts)
const lenders  = computed(() => optionsStore.lenders)

const lenderFilterOptions = computed(() => {
  const list = []
  const seenIds = new Set()
  const seenNames = new Set()

  for (const l of lenders.value) {
    const id = parseInt(l.id, 10)
    const name = String(l.full_name || '').trim()
    if (!name) continue
    seenIds.add(id)
    seenNames.add(name.toLowerCase())
    const doc = l.document_number ? ` (${l.document_type || 'DOC'} ${l.document_number})` : ''
    list.push({ value: `id:${id}|${name}`, label: `${name}${doc}` })
  }

  for (const m of store.rows) {
    const name = String(m.lender_name || '').trim()
    if (!name) continue
    if (m.lender_id && !seenIds.has(m.lender_id)) {
      seenIds.add(m.lender_id)
      seenNames.add(name.toLowerCase())
      list.push({ value: `id:${m.lender_id}|${name}`, label: name })
    } else if (!m.lender_id && !seenNames.has(name.toLowerCase())) {
      seenNames.add(name.toLowerCase())
      list.push({ value: `name:${name.toLowerCase()}`, label: name })
    }
  }

  return list.sort((a, b) => a.label.localeCompare(b.label, 'es'))
})

const accountFilterOptions = computed(() => {
  const list = []
  const seenIds = new Set()
  const seenNames = new Set()

  for (const a of accounts.value) {
    const id = String(a.id)
    const name = String(a.account_name || '').trim()
    if (!name) continue
    seenIds.add(id)
    seenNames.add(name.toLowerCase())
    list.push({ value: id, label: name })
  }

  for (const m of store.rows) {
    const name = String(m.account_name || '').trim()
    if (!name || name === '-') continue
    if (m.account_id && !seenIds.has(String(m.account_id))) {
      seenIds.add(String(m.account_id))
      seenNames.add(name.toLowerCase())
      list.push({ value: String(m.account_id), label: name })
    } else if (!m.account_id && !seenNames.has(name.toLowerCase())) {
      seenNames.add(name.toLowerCase())
      list.push({ value: `name:${name.toLowerCase()}`, label: name })
    }
  }

  return list.sort((a, b) => a.label.localeCompare(b.label, 'es'))
})

function toggleStatusQuick(status) {
  store.filters.status = store.filters.status === status ? '' : status
}

function toISODate(d) {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

function applyDatePreset(preset) {
  const now = new Date()
  if (preset === 'today') {
    const iso = toISODate(now)
    store.filters.dateFrom = iso
    store.filters.dateTo = iso
  } else if (preset === 'this_month') {
    store.filters.dateFrom = toISODate(new Date(now.getFullYear(), now.getMonth(), 1))
    store.filters.dateTo = toISODate(new Date(now.getFullYear(), now.getMonth() + 1, 0))
  } else if (preset === 'last_month') {
    store.filters.dateFrom = toISODate(new Date(now.getFullYear(), now.getMonth() - 1, 1))
    store.filters.dateTo = toISODate(new Date(now.getFullYear(), now.getMonth(), 0))
  } else if (preset === 'this_year') {
    store.filters.dateFrom = `${now.getFullYear()}-01-01`
    store.filters.dateTo = `${now.getFullYear()}-12-31`
  }
}

const debtStats = computed(() => {
  let recibido = 0, pagado = 0, pendiente = 0
  for (const m of store.filtered) {
    recibido  += m.total_payable || 0
    pagado    += m.amount_paid || 0
    pendiente += m.balance_pending || 0
  }
  return { recibido, pagado, pendiente }
})

function openAdd() {
  resetForm()
  showAdd.value = true
  nextTick(() => addFormRef.value?.reset())
}

function resetForm() { formError.value = '' }

function openAddLender() {
  lenderFormError.value = ''
  showAddLender.value = true
  nextTick(() => addLenderRef.value?.reset())
}

function openAddLenderFromDebt() {
  openedFromDebt.value = true
  openAddLender()
}

function resetLenderForm() {
  lenderFormError.value = ''
}

async function onMutated() {
  dashboardStore.invalidate()
  reportStore.invalidate()
  accountStore.invalidate()
  await Promise.all([
    store.reload(),
    optionsStore.reload(),
    authStore.refreshHeaderMetrics()
  ])
}

async function onLenderMutated() {
  await Promise.all([lenderStore.reload(), optionsStore.reload()])
}

// Submit Debt Handlers
async function submitAdd() {
  saving.value = true; formError.value = ''
  try {
    const fd = addFormRef.value?.getFormData()
    if (!fd) { formError.value = 'Complete los campos requeridos y verifique que el capital sea mayor a 0.'; return }
    const { data } = await api.createDebt(fd)
    if (data.success) { showAdd.value = false; await onMutated(); toast.success(data.messages) }
    else formError.value = data.messages
  } catch { formError.value = 'Error de comunicación.' }
  finally { saving.value = false }
}

async function submitEdit() {
  saving.value = true; formError.value = ''
  try {
    const fd = editFormRef.value?.getFormData()
    if (!fd) { formError.value = 'Complete los campos requeridos y verifique que el capital sea mayor a 0.'; return }
    const { data } = await api.editDebt(fd)
    if (data.success) { showEdit.value = false; await onMutated(); toast.success(data.messages) }
    else formError.value = data.messages
  } catch { formError.value = 'Error de comunicación.' }
  finally { saving.value = false }
}

async function submitPayment() {
  saving.value = true; formError.value = ''
  try {
    const fd = payFormRef.value?.getFormData()
    if (!fd) { formError.value = 'Seleccione la cuenta origen e ingrese un monto mayor a 0.'; return }
    const { data } = await api.addDebtPayment(fd)
    if (data.success) { showPay.value = false; await onMutated(); toast.success(data.messages) }
    else formError.value = data.messages
  } catch { formError.value = 'Error de comunicación.' }
  finally { saving.value = false }
}

// Submit Lender Handlers
async function submitAddLender() {
  savingLender.value = true
  lenderFormError.value = ''
  try {
    const fd = addLenderRef.value?.getFormData()
    if (!fd) { lenderFormError.value = 'Ingrese el documento y el nombre / razón social del acreedor.'; return }
    const { data } = await api.createLender(fd)
    if (data.success) {
      showAddLender.value = false
      await onLenderMutated()
      toast.success(data.messages)

      if (openedFromDebt.value) {
        const newLender = { id: data.lender_id, lender_type: data.lender_type, full_name: data.full_name }
        if (showAdd.value)       addFormRef.value?.selectLender(newLender)
        else if (showEdit.value) editFormRef.value?.selectLender(newLender)
        openedFromDebt.value = false
      }
    } else {
      lenderFormError.value = data.messages
    }
  } catch {
    lenderFormError.value = 'Error de comunicación al guardar el acreedor.'
  } finally {
    savingLender.value = false
  }
}

async function submitEditLender() {
  savingLender.value = true
  lenderFormError.value = ''
  try {
    const fd = editLenderRef.value?.getFormData()
    if (!fd) { lenderFormError.value = 'Ingrese el documento y el nombre / razón social del acreedor.'; return }
    const { data } = await api.editLender(fd)
    if (data.success) {
      showEditLender.value = false
      await Promise.all([onLenderMutated(), store.reload()])
      toast.success(data.messages)
    } else {
      lenderFormError.value = data.messages
    }
  } catch {
    lenderFormError.value = 'Error de comunicación al actualizar el acreedor.'
  } finally {
    savingLender.value = false
  }
}

// Action Handlers
async function editDebt(id) {
  const { data } = await api.getDebtData(id)
  if (data.success) {
    resetForm()
    editId.value = id
    showEdit.value = true
    await nextTick()
    editFormRef.value?.setData(data.data)
  } else {
    toast.danger(data.messages || 'No se pudo cargar el préstamo recibido.')
  }
}

async function removeDebt(id) {
  if (!confirm('¿Eliminar este préstamo recibido? Esta acción revertirá los ajustes en cuentas y no se puede deshacer.')) return
  const { data } = await api.removeDebt(id)
  if (data.success) { await onMutated(); toast.success(data.messages) }
  else toast.danger(data.messages)
}

async function changeDebtStatus(id, status) {
  const { data } = await api.updateDebtStatus(id, status)
  if (data.success) { await onMutated(); toast.success(data.messages) }
  else toast.danger(data.messages)
}

async function openPayDebt(id) {
  const { data } = await api.getDebtData(id)
  if (data.success) {
    resetForm()
    editId.value = id
    payInfo.value = {
      lender:  data.data.lender_name,
      pending: fmtMoney(data.data.balance_pending),
    }
    showPay.value = true
    await nextTick()
    payFormRef.value?.setDebtId(id)
  } else {
    toast.danger(data.messages || 'No se pudo cargar el préstamo recibido.')
  }
}

async function editLender(id) {
  const { data } = await api.getLenderData(id)
  if (data.success) {
    resetLenderForm()
    editLenderId.value = id
    showEditLender.value = true
    await nextTick()
    editLenderRef.value?.setData(data.data)
  } else {
    toast.danger(data.messages || 'No se pudo cargar el acreedor.')
  }
}

async function removeLender(id) {
  if (!confirm('¿Eliminar este acreedor / entidad? Esta acción no se puede deshacer.')) return
  const { data } = await api.removeLender(id)
  if (data.success) { await onLenderMutated(); toast.success(data.messages) }
  else toast.danger(data.messages)
}

onMounted(async () => {
  await Promise.all([
    store.load(),
    lenderStore.load(),
    optionsStore.load()
  ])
})
</script>
