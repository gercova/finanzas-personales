<template>
  <div class="container">
    <div class="row vertical">
      <div class="col-xs-12 col-sm-8 col-sm-offset-2 col-md-6 col-md-offset-3 col-lg-4 col-lg-offset-4">
        <div class="panel panel-info">
          <div class="panel-heading">
            <h3 class="panel-title">
              <i class="glyphicon glyphicon-piggy-bank"></i>
              {{ authStore.appName }} - Inicio de sesión
            </h3>
          </div>
          <div class="panel-body">
            <div v-if="successMsg" class="alert alert-success" role="alert">
              <i class="glyphicon glyphicon-ok-sign"></i> {{ successMsg }}
            </div>

            <div v-if="errorMsg" class="alert alert-warning" role="alert">
              <i class="glyphicon glyphicon-exclamation-sign"></i> {{ errorMsg }}
            </div>

            <form class="form-horizontal" @submit.prevent="submitLogin">
              <fieldset>
                <div class="form-group">
                  <label for="login-username" class="col-sm-3 control-label">Usuario</label>
                  <div class="col-sm-9">
                    <input
                      id="login-username"
                      type="text"
                      class="form-control"
                      v-model="form.username"
                      placeholder="Nombre de usuario"
                      autocomplete="username"
                      required
                      autofocus
                    />
                  </div>
                </div>

                <div class="form-group">
                  <label for="login-password" class="col-sm-3 control-label">Contraseña</label>
                  <div class="col-sm-9">
                    <div class="input-group">
                      <input
                        id="login-password"
                        :type="showPassword ? 'text' : 'password'"
                        class="form-control"
                        v-model="form.password"
                        placeholder="Contraseña"
                        autocomplete="current-password"
                        required
                      />
                      <span class="input-group-btn">
                        <button
                          type="button"
                          class="btn btn-default"
                          @click="showPassword = !showPassword"
                          :title="showPassword ? 'Ocultar contraseña' : 'Ver contraseña'"
                        >
                          <i :class="showPassword ? 'glyphicon glyphicon-eye-close' : 'glyphicon glyphicon-eye-open'"></i>
                        </button>
                      </span>
                    </div>
                  </div>
                </div>

                <div class="form-group">
                  <div class="col-sm-offset-3 col-sm-9">
                    <button type="submit" class="btn btn-default" :disabled="submitting">
                      <i class="glyphicon glyphicon-log-in"></i>
                      {{ submitting ? ' Ingresando…' : ' Ingresar' }}
                    </button>
                  </div>
                </div>

                <hr style="margin:15px 0;" />
                <div class="text-center">
                  <span class="text-muted">¿No tienes una cuenta?</span>
                  <router-link to="/register" class="btn btn-link" style="padding:0 4px;font-weight:600;">
                    Regístrate aquí
                  </router-link>
                </div>
              </fieldset>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/useAuthStore'
import { useFormOptionsStore } from '@/stores/useFormOptionsStore'

const route        = useRoute()
const router       = useRouter()
const authStore    = useAuthStore()
const optionsStore = useFormOptionsStore()

const form = reactive({
  username: '',
  password: '',
})

const showPassword = ref(false)
const submitting   = ref(false)
const localError   = ref('')

const successMsg = computed(() =>
  route.query.registered === '1'
    ? 'Cuenta creada exitosamente. Ya puede iniciar sesión con su usuario y contraseña (sus cuentas y categorías iniciales se generaron automáticamente).'
    : ''
)

const errorMsg = computed(() => {
  if (localError.value) return localError.value
  const err = route.query.error || authStore.sessionReason
  if (err === 'timeout') return 'Su sesión ha expirado por inactividad. Por favor ingrese nuevamente.'
  if (err === 'hijack')  return 'Su sesión se cerró por motivos de seguridad.'
  return ''
})

async function submitLogin() {
  localError.value = ''
  if (!form.username.trim() || !form.password) {
    localError.value = 'Se requiere nombre de usuario y contraseña.'
    return
  }
  submitting.value = true
  try {
    const res = await authStore.login(form.username.trim(), form.password)
    if (res?.success) {
      await optionsStore.load(true)
      const dest = typeof route.query.redirect === 'string' && route.query.redirect.startsWith('/')
        ? route.query.redirect
        : '/dashboard'
      router.replace(dest)
    } else {
      localError.value = res?.messages || 'No se pudo iniciar sesión.'
    }
  } catch (e) {
    localError.value = e.response?.data?.messages || 'Error de comunicación con el servidor.'
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  authStore.ensureSession()
})
</script>
