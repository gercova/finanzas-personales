<template>
  <AuthLayout :app-name="authStore.appName">
    <h2 class="auth-heading">Iniciar sesión</h2>
    <p class="auth-subheading">
      Ingresa tus credenciales para acceder a tu panel financiero.
    </p>

    <div v-if="successMsg" class="auth-alert auth-alert-success" role="alert">
      {{ successMsg }}
    </div>

    <div v-if="errorMsg" class="auth-alert auth-alert-warning" role="alert">
      {{ errorMsg }}
    </div>

    <form class="auth-form" @submit.prevent="submitLogin">
      <div class="form-group">
        <label for="login-username">Usuario</label>
        <input
          id="login-username"
          type="text"
          class="form-control"
          v-model="form.username"
          placeholder="Ingresa tu usuario"
          autocomplete="username"
          required
          autofocus
        />
      </div>

      <div class="form-group">
        <label for="login-password">Contraseña</label>
        <div class="input-group">
          <input
            id="login-password"
            :type="showPassword ? 'text' : 'password'"
            class="form-control"
            v-model="form.password"
            placeholder="Ingresa tu contraseña"
            autocomplete="current-password"
            required
          />
          <span class="input-group-btn">
            <button
              type="button"
              class="btn btn-default"
              @click="showPassword = !showPassword"
              :title="showPassword ? 'Ocultar contraseña' : 'Ver contraseña'"
              :aria-label="showPassword ? 'Ocultar contraseña' : 'Ver contraseña'"
            >
              <i :class="showPassword ? 'glyphicon glyphicon-eye-close' : 'glyphicon glyphicon-eye-open'"></i>
            </button>
          </span>
        </div>
      </div>

      <div style="margin-top:20px;">
        <button type="submit" class="auth-btn-primary" :disabled="submitting">
          {{ submitting ? 'Ingresando…' : 'Ingresar a mi cuenta' }}
        </button>
      </div>

      <template v-if="authStore.allowRegistration">
        <hr class="auth-divider" />
        <div class="auth-switch">
          <span>¿Aún no tienes una cuenta?</span>
          <router-link to="/register">Crear cuenta</router-link>
        </div>
      </template>
    </form>
  </AuthLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/useAuthStore'
import { useFormOptionsStore } from '@/stores/useFormOptionsStore'
import AuthLayout from '@/components/AuthLayout.vue'

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
    ? 'Cuenta creada exitosamente. Ya puedes iniciar sesión; tus cuentas y categorías iniciales fueron generadas automáticamente.'
    : ''
)

const errorMsg = computed(() => {
  if (localError.value) return localError.value
  const err = route.query.error || authStore.sessionReason
  if (err === 'timeout') return 'Tu sesión expiró por inactividad. Ingresa nuevamente.'
  if (err === 'hijack')  return 'Tu sesión se cerró por motivos de seguridad.'
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
