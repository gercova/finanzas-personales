<template>
  <div>
    <ol class="breadcrumb">
      <li><a href="#" @click.prevent="$router.push('/dashboard')">Inicio</a></li>
      <li class="active">Configuración</li>
    </ol>

    <!-- Section 1: Monthly Spending Quota (Azure style) -->
    <div class="panel panel-default">
      <div class="panel-heading">
        <i class="glyphicon glyphicon-stats text-primary" style="margin-right:6px;"></i>
        <strong>Límite Mensual de Gasto (Cuota de Consumo)</strong>
      </div>
      <div class="panel-body">
        <div class="alert alert-info" style="margin-bottom:18px;">
          <i class="glyphicon glyphicon-info-sign"></i>
          El sistema supervisa tu gasto mensual de forma similar a las <strong>cuotas de consumo de Azure</strong>.
          Cuando tu gasto alcance el umbral de advertencia (amarillo) o el umbral crítico (rojo), recibirás alertas en la barra superior y en el panel principal.
        </div>

        <!-- Visual Progress Box -->
        <div style="background:#f9f9f9; padding:15px; border-radius:6px; border:1px solid #e3e3e3; margin-bottom:20px;">
          <div class="row">
            <div class="col-sm-6">
              <strong>Mes evaluado:</strong> <code>{{ quotaForm.month_year }}</code><br>
              <strong>Gasto acumulado:</strong>
              <span :class="'text-' + quota.bar_type" style="font-weight:700;">
                S/ {{ fmt(quota.spent_this_month) }}
              </span>
              de <strong>S/ {{ fmt(quotaForm.monthly_limit) }}</strong>
            </div>
            <div class="col-sm-6 text-right">
              <span :class="'label label-' + quota.bar_type" style="font-size:13px; padding:4px 9px;">
                Consumido: {{ quota.pct_spent }}%
              </span>
            </div>
          </div>
          <div class="progress" style="margin-top:10px; margin-bottom:0; height:18px;">
            <div
              :class="'progress-bar progress-bar-' + quota.bar_type + ' progress-bar-striped active'"
              role="progressbar"
              :style="{ width: Math.min(quota.pct_spent, 100) + '%' }"
            >
              {{ quota.pct_spent }}%
            </div>
          </div>
        </div>

        <form @submit.prevent="submitQuota" class="form-horizontal">
          <div class="form-group">
            <label class="col-sm-3 control-label">Mes / Año (YYYY-MM)</label>
            <div class="col-sm-4">
              <input type="month" class="form-control" v-model="quotaForm.month_year" required />
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-3 control-label">Límite Mensual (S/)</label>
            <div class="col-sm-4">
              <input type="number" step="0.01" min="1" class="form-control" v-model="quotaForm.monthly_limit" required />
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-3 control-label">Umbral de Advertencia (%)</label>
            <div class="col-sm-3">
              <div class="input-group">
                <input type="number" min="1" max="99" class="form-control" v-model="quotaForm.warning_threshold" required />
                <span class="input-group-addon">%</span>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-3 control-label">Umbral Crítico (%)</label>
            <div class="col-sm-3">
              <div class="input-group">
                <input type="number" min="1" max="100" class="form-control" v-model="quotaForm.critical_threshold" required />
                <span class="input-group-addon">%</span>
              </div>
            </div>
          </div>

          <div class="form-group">
            <div class="col-sm-offset-3 col-sm-9">
              <button type="submit" class="btn btn-primary" :disabled="savingQuota">
                <i class="glyphicon glyphicon-floppy-disk"></i>
                {{ savingQuota ? 'Guardando…' : 'Guardar Límite de Gasto' }}
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Section 2: Change Username -->
    <div class="panel panel-default">
      <div class="panel-heading">
        <i class="glyphicon glyphicon-user text-success" style="margin-right:6px;"></i>
        <strong>Cambiar Nombre de Usuario</strong>
      </div>
      <div class="panel-body">
        <form @submit.prevent="submitUsername" class="form-horizontal">
          <div class="form-group">
            <label class="col-sm-3 control-label">Usuario Actual</label>
            <div class="col-sm-4">
              <input type="text" class="form-control" :value="user?.username || ''" disabled />
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-3 control-label">Nuevo Usuario <span class="text-danger">*</span></label>
            <div class="col-sm-4">
              <input type="text" class="form-control" v-model="usernameForm.newUsername" minlength="3" placeholder="Mínimo 3 caracteres" required />
            </div>
          </div>

          <div class="form-group">
            <div class="col-sm-offset-3 col-sm-9">
              <button type="submit" class="btn btn-success" :disabled="savingUsername">
                <i class="glyphicon glyphicon-ok-sign"></i>
                {{ savingUsername ? 'Actualizando…' : 'Actualizar Nombre de Usuario' }}
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Section 3: Change Password -->
    <div class="panel panel-default">
      <div class="panel-heading">
        <i class="glyphicon glyphicon-lock text-warning" style="margin-right:6px;"></i>
        <strong>Cambiar Contraseña</strong>
      </div>
      <div class="panel-body">
        <form @submit.prevent="submitPassword" class="form-horizontal">
          <div class="form-group">
            <label class="col-sm-3 control-label">Contraseña Actual <span class="text-danger">*</span></label>
            <div class="col-sm-4">
              <input type="password" class="form-control" v-model="passwordForm.password" required />
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-3 control-label">Nueva Contraseña <span class="text-danger">*</span></label>
            <div class="col-sm-4">
              <input type="password" class="form-control" v-model="passwordForm.npassword" minlength="4" required />
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-3 control-label">Confirmar Contraseña <span class="text-danger">*</span></label>
            <div class="col-sm-4">
              <input type="password" class="form-control" v-model="passwordForm.cpassword" minlength="4" required />
            </div>
          </div>

          <div class="form-group">
            <div class="col-sm-offset-3 col-sm-9">
              <button type="submit" class="btn btn-warning" :disabled="savingPassword">
                <i class="glyphicon glyphicon-refresh"></i>
                {{ savingPassword ? 'Guardando…' : 'Cambiar Contraseña' }}
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, inject } from 'vue'
import { useSettingStore } from '@/stores/useSettingStore'
import { useDashboardStore } from '@/stores/useDashboardStore'
import * as api from '@/api'

const toast          = inject('toast')
const store          = useSettingStore()
const dashboardStore = useDashboardStore()

const user  = computed(() => store.user)
const quota = computed(() => store.quota)

const savingQuota    = ref(false)
const savingUsername = ref(false)
const savingPassword = ref(false)

const quotaForm = reactive({
  month_year: new Date().toISOString().slice(0, 7),
  monthly_limit: 4000,
  warning_threshold: 75,
  critical_threshold: 90
})

const usernameForm = reactive({
  newUsername: ''
})

const passwordForm = reactive({
  password: '',
  npassword: '',
  cpassword: ''
})

function fmt(n) {
  const num = parseFloat(n) || 0
  return num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')
}

async function submitQuota() {
  savingQuota.value = true
  try {
    const fd = new FormData()
    fd.append('month_year', quotaForm.month_year)
    fd.append('monthly_limit', quotaForm.monthly_limit)
    fd.append('warning_threshold', quotaForm.warning_threshold)
    fd.append('critical_threshold', quotaForm.critical_threshold)

    const { data } = await api.updateSpendingLimit(fd)
    if (data.success) {
      toast.success(data.messages)
      await store.reload()
      dashboardStore.invalidate()
    } else {
      toast.danger(data.messages)
    }
  } catch (e) {
    toast.danger('Error al actualizar límite de gasto.')
  } finally {
    savingQuota.value = false
  }
}

async function submitUsername() {
  if (!usernameForm.newUsername.trim()) {
    toast.warning('Ingrese el nuevo nombre de usuario.')
    return
  }
  savingUsername.value = true
  try {
    const fd = new FormData()
    fd.append('username', usernameForm.newUsername.trim())
    const { data } = await api.changeUsername(fd)
    if (data.success) {
      toast.success(data.messages)
      usernameForm.newUsername = ''
      await store.reload()
    } else {
      toast.danger(data.messages)
    }
  } catch (e) {
    toast.danger('Error al actualizar el usuario.')
  } finally {
    savingUsername.value = false
  }
}

async function submitPassword() {
  if (passwordForm.npassword !== passwordForm.cpassword) {
    toast.warning('La nueva contraseña y la confirmación no coinciden.')
    return
  }
  savingPassword.value = true
  try {
    const fd = new FormData()
    fd.append('password', passwordForm.password)
    fd.append('npassword', passwordForm.npassword)
    fd.append('cpassword', passwordForm.cpassword)
    const { data } = await api.changePassword(fd)
    if (data.success) {
      toast.success(data.messages)
      passwordForm.password = ''
      passwordForm.npassword = ''
      passwordForm.cpassword = ''
    } else {
      toast.danger(data.messages)
    }
  } catch (e) {
    toast.danger('Error al actualizar la contraseña.')
  } finally {
    savingPassword.value = false
  }
}

onMounted(async () => {
  await store.load()
  if (quota.value) {
    quotaForm.month_year         = quota.value.month_year || quotaForm.month_year
    quotaForm.monthly_limit      = quota.value.monthly_limit || quotaForm.monthly_limit
    quotaForm.warning_threshold  = quota.value.warning_threshold || quotaForm.warning_threshold
    quotaForm.critical_threshold = quota.value.critical_threshold || quotaForm.critical_threshold
  }
})
</script>
