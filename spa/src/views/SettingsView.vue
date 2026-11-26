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
            <div class="col-sm-5">
              <div class="input-group">
                <input
                  :type="showCurrentPass ? 'text' : 'password'"
                  class="form-control"
                  v-model="passwordForm.password"
                  placeholder="Ingrese su contraseña actual"
                  autocomplete="current-password"
                  required
                />
                <span class="input-group-btn">
                  <button
                    type="button"
                    class="btn btn-default"
                    @click="showCurrentPass = !showCurrentPass"
                    :title="showCurrentPass ? 'Ocultar contraseña' : 'Ver contraseña'"
                  >
                    <i :class="showCurrentPass ? 'glyphicon glyphicon-eye-close' : 'glyphicon glyphicon-eye-open'"></i>
                  </button>
                </span>
              </div>
            </div>
          </div>

          <div class="form-group" :class="{ 'has-warning': passwordForm.npassword && passwordStrength.score < 3, 'has-success': passwordStrength.score >= 3 }">
            <label class="col-sm-3 control-label">Nueva Contraseña <span class="text-danger">*</span></label>
            <div class="col-sm-5">
              <div class="input-group">
                <input
                  :type="showNewPass ? 'text' : 'password'"
                  class="form-control"
                  v-model="passwordForm.npassword"
                  minlength="8"
                  placeholder="Mínimo 8 caracteres"
                  autocomplete="new-password"
                  required
                />
                <span class="input-group-btn">
                  <button
                    type="button"
                    class="btn btn-default"
                    @click="showNewPass = !showNewPass"
                    :title="showNewPass ? 'Ocultar contraseña' : 'Ver contraseña'"
                  >
                    <i :class="showNewPass ? 'glyphicon glyphicon-eye-close' : 'glyphicon glyphicon-eye-open'"></i>
                  </button>
                </span>
              </div>

              <!-- Password Strength Meter -->
              <div v-if="passwordForm.npassword" style="margin-top: 10px; background: #fafafa; border: 1px solid #e5e5e5; border-radius: 4px; padding: 10px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                  <span style="font-size: 12px; font-weight: 600; color: #555;">Seguridad de la contraseña:</span>
                  <span :class="['label', passwordStrength.badgeClass]" style="font-size: 11px;">
                    {{ passwordStrength.label }}
                  </span>
                </div>
                <div class="progress" style="height: 8px; margin-bottom: 8px; background-color: #e0e0e0;">
                  <div
                    class="progress-bar"
                    :class="passwordStrength.barClass"
                    role="progressbar"
                    :style="{ width: passwordStrength.percent + '%', transition: 'width 0.3s ease' }"
                  ></div>
                </div>

                <!-- Password Rules Checklist -->
                <ul class="list-unstyled" style="font-size: 11px; margin-bottom: 0; line-height: 1.6;">
                  <li :class="rules.minLength ? 'text-success' : 'text-muted'">
                    <i :class="rules.minLength ? 'glyphicon glyphicon-ok' : 'glyphicon glyphicon-remove'" style="margin-right: 4px;"></i>
                    Mínimo 8 caracteres
                  </li>
                  <li :class="rules.hasUpper ? 'text-success' : 'text-muted'">
                    <i :class="rules.hasUpper ? 'glyphicon glyphicon-ok' : 'glyphicon glyphicon-remove'" style="margin-right: 4px;"></i>
                    Al menos una letra mayúscula (A-Z)
                  </li>
                  <li :class="rules.hasLower ? 'text-success' : 'text-muted'">
                    <i :class="rules.hasLower ? 'glyphicon glyphicon-ok' : 'glyphicon glyphicon-remove'" style="margin-right: 4px;"></i>
                    Al menos una letra minúscula (a-z)
                  </li>
                  <li :class="rules.hasNumber ? 'text-success' : 'text-muted'">
                    <i :class="rules.hasNumber ? 'glyphicon glyphicon-ok' : 'glyphicon glyphicon-remove'" style="margin-right: 4px;"></i>
                    Al menos un número (0-9)
                  </li>
                  <li :class="rules.hasSpecial ? 'text-success' : 'text-muted'">
                    <i :class="rules.hasSpecial ? 'glyphicon glyphicon-ok' : 'glyphicon glyphicon-remove'" style="margin-right: 4px;"></i>
                    Al menos un carácter especial (@, #, $, %, !, *, ?, &amp;, etc.)
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <div class="form-group" :class="{ 'has-error': passwordMismatch, 'has-success': passwordMatch }">
            <label class="col-sm-3 control-label">Confirmar Contraseña <span class="text-danger">*</span></label>
            <div class="col-sm-5">
              <div class="input-group">
                <input
                  :type="showConfirmPass ? 'text' : 'password'"
                  class="form-control"
                  v-model="passwordForm.cpassword"
                  minlength="8"
                  placeholder="Repita la nueva contraseña"
                  autocomplete="new-password"
                  required
                />
                <span class="input-group-btn">
                  <button
                    type="button"
                    class="btn btn-default"
                    @click="showConfirmPass = !showConfirmPass"
                    :title="showConfirmPass ? 'Ocultar contraseña' : 'Ver contraseña'"
                  >
                    <i :class="showConfirmPass ? 'glyphicon glyphicon-eye-close' : 'glyphicon glyphicon-eye-open'"></i>
                  </button>
                </span>
              </div>

              <!-- Match / Mismatch Feedback -->
              <div v-if="passwordMatch" class="text-success" style="margin-top: 6px; font-size: 12px; font-weight: 600;">
                <i class="glyphicon glyphicon-ok"></i> Las contraseñas coinciden correctamente.
              </div>
              <div v-else-if="passwordMismatch" class="text-danger" style="margin-top: 6px; font-size: 12px; font-weight: 600;">
                <i class="glyphicon glyphicon-remove"></i> Las contraseñas no coinciden.
              </div>
            </div>
          </div>

          <div class="form-group">
            <div class="col-sm-offset-3 col-sm-9">
              <button
                type="submit"
                class="btn btn-warning"
                :disabled="savingPassword || !canSubmitPassword"
              >
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

const showCurrentPass = ref(false)
const showNewPass     = ref(false)
const showConfirmPass = ref(false)

const rules = computed(() => {
  const p = passwordForm.npassword || ''
  return {
    minLength: p.length >= 8,
    hasUpper: /[A-Z]/.test(p),
    hasLower: /[a-z]/.test(p),
    hasNumber: /[0-9]/.test(p),
    hasSpecial: /[^A-Za-z0-9]/.test(p)
  }
})

const passwordStrength = computed(() => {
  const p = passwordForm.npassword || ''
  if (!p) {
    return {
      score: 0,
      percent: 0,
      label: 'Sin ingresar',
      barClass: '',
      badgeClass: 'label-default'
    }
  }

  let score = 0
  if (rules.value.minLength) score++
  if (rules.value.hasUpper) score++
  if (rules.value.hasLower) score++
  if (rules.value.hasNumber) score++
  if (rules.value.hasSpecial) score++

  if (score <= 1) {
    return {
      score,
      percent: Math.max(15, Math.min(25, (p.length / 8) * 20)),
      label: 'Muy Débil',
      barClass: 'progress-bar-danger',
      badgeClass: 'label-danger'
    }
  } else if (score === 2) {
    return {
      score,
      percent: 40,
      label: 'Débil',
      barClass: 'progress-bar-warning',
      badgeClass: 'label-warning'
    }
  } else if (score === 3) {
    return {
      score,
      percent: 60,
      label: 'Aceptable',
      barClass: 'progress-bar-info',
      badgeClass: 'label-info'
    }
  } else if (score === 4) {
    return {
      score,
      percent: 80,
      label: 'Fuerte',
      barClass: 'progress-bar-primary',
      badgeClass: 'label-primary'
    }
  } else {
    return {
      score,
      percent: 100,
      label: 'Muy Segura',
      barClass: 'progress-bar-success',
      badgeClass: 'label-success'
    }
  }
})

const passwordMatch = computed(() => {
  return (
    passwordForm.npassword.length >= 8 &&
    passwordForm.cpassword.length > 0 &&
    passwordForm.npassword === passwordForm.cpassword
  )
})

const passwordMismatch = computed(() => {
  return (
    passwordForm.cpassword.length > 0 &&
    passwordForm.npassword !== passwordForm.cpassword
  )
})

const canSubmitPassword = computed(() => {
  return (
    passwordForm.password.trim().length > 0 &&
    passwordForm.npassword.length >= 8 &&
    passwordForm.npassword === passwordForm.cpassword
  )
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
  if (!passwordForm.password) {
    toast.warning('Debe ingresar su contraseña actual.')
    return
  }
  if (passwordForm.npassword.length < 8) {
    toast.warning('La nueva contraseña debe tener al menos 8 caracteres.')
    return
  }
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
      showCurrentPass.value = false
      showNewPass.value = false
      showConfirmPass.value = false
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
