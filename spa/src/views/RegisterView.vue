<template>
  <AuthLayout :app-name="authStore.appName">
    <h2 class="auth-heading">Crear cuenta</h2>
    <p class="auth-subheading">
      Configura tu espacio personal en menos de un minuto con cuentas y categorías listas para usar.
    </p>

    <div v-if="errorMsg" class="auth-alert auth-alert-warning" role="alert">
      {{ errorMsg }}
    </div>

    <form class="auth-form" @submit.prevent="submitRegister">
      <div class="form-group">
        <label for="reg-fullname">Nombre completo</label>
        <input
          id="reg-fullname"
          type="text"
          class="form-control"
          v-model="form.full_name"
          placeholder="Ej: María Pérez"
          required
          autofocus
        />
      </div>

      <div class="row">
        <div class="col-sm-6">
          <div class="form-group">
            <label for="reg-username">Usuario</label>
            <input
              id="reg-username"
              type="text"
              class="form-control"
              v-model="form.username"
              placeholder="usuario_123"
              autocomplete="username"
              required
            />
          </div>
        </div>
        <div class="col-sm-6">
          <div class="form-group">
            <label for="reg-email">Correo (opcional)</label>
            <input
              id="reg-email"
              type="email"
              class="form-control"
              v-model="form.email"
              placeholder="correo@ejemplo.com"
              autocomplete="email"
            />
          </div>
        </div>
      </div>

      <div class="form-group">
        <label for="reg-password">Contraseña</label>
        <div class="input-group">
          <input
            id="reg-password"
            :type="showPass ? 'text' : 'password'"
            class="form-control"
            v-model="form.password"
            placeholder="Mínimo 6 caracteres"
            autocomplete="new-password"
            minlength="6"
            required
          />
          <span class="input-group-btn">
            <button
              type="button"
              class="btn btn-default"
              @click="showPass = !showPass"
              :title="showPass ? 'Ocultar contraseña' : 'Ver contraseña'"
              :aria-label="showPass ? 'Ocultar contraseña' : 'Ver contraseña'"
            >
              <i :class="showPass ? 'glyphicon glyphicon-eye-close' : 'glyphicon glyphicon-eye-open'"></i>
            </button>
          </span>
        </div>
      </div>

      <div class="form-group">
        <label for="reg-confirm">Confirmar contraseña</label>
        <div class="input-group">
          <input
            id="reg-confirm"
            :type="showConfirm ? 'text' : 'password'"
            class="form-control"
            v-model="form.confirm_password"
            placeholder="Repite la contraseña"
            autocomplete="new-password"
            minlength="6"
            required
          />
          <span class="input-group-btn">
            <button
              type="button"
              class="btn btn-default"
              @click="showConfirm = !showConfirm"
              :title="showConfirm ? 'Ocultar contraseña' : 'Ver contraseña'"
              :aria-label="showConfirm ? 'Ocultar contraseña' : 'Ver contraseña'"
            >
              <i :class="showConfirm ? 'glyphicon glyphicon-eye-close' : 'glyphicon glyphicon-eye-open'"></i>
            </button>
          </span>
        </div>
        <small
          v-if="form.confirm_password"
          :class="passwordsMatch ? 'text-success' : 'text-danger'"
          style="display:block; margin-top:4px; font-size:12px;"
        >
          {{ passwordsMatch ? 'Las contraseñas coinciden.' : 'Las contraseñas no coinciden.' }}
        </small>
      </div>

      <div style="margin-top:20px;">
        <button type="submit" class="auth-btn-primary" :disabled="submitting">
          {{ submitting ? 'Creando cuenta…' : 'Crear mi cuenta' }}
        </button>
      </div>

      <hr class="auth-divider" />
      <div class="auth-switch">
        <span>¿Ya tienes una cuenta?</span>
        <router-link to="/login">Iniciar sesión</router-link>
      </div>
    </form>
  </AuthLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/useAuthStore'
import AuthLayout from '@/components/AuthLayout.vue'

const router    = useRouter()
const authStore = useAuthStore()

const form = reactive({
  full_name: '',
  username: '',
  email: '',
  password: '',
  confirm_password: '',
})

const showPass    = ref(false)
const showConfirm = ref(false)
const submitting  = ref(false)
const errorMsg    = ref('')

const passwordsMatch = computed(() =>
  Boolean(form.confirm_password) && form.password === form.confirm_password
)

async function submitRegister() {
  errorMsg.value = ''
  if (form.password.length < 6) {
    errorMsg.value = 'La contraseña debe tener al menos 6 caracteres.'
    return
  }
  if (form.password !== form.confirm_password) {
    errorMsg.value = 'Las contraseñas no coinciden.'
    return
  }
  submitting.value = true
  try {
    const res = await authStore.register({
      full_name: form.full_name.trim(),
      username: form.username.trim(),
      email: form.email.trim(),
      password: form.password,
      confirm_password: form.confirm_password,
    })
    if (res?.success) {
      router.replace({ path: '/login', query: { registered: '1' } })
    } else {
      errorMsg.value = res?.messages || 'No se pudo completar el registro.'
    }
  } catch (e) {
    errorMsg.value = e.response?.data?.messages || 'Error de comunicación con el servidor.'
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  authStore.ensureSession()
})
</script>
