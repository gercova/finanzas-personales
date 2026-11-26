<template>
  <div class="container">
    <div class="row vertical">
      <div class="col-xs-12 col-sm-8 col-sm-offset-2 col-md-6 col-md-offset-3 col-lg-5 col-lg-offset-3">
        <div class="panel panel-success">
          <div class="panel-heading">
            <h3 class="panel-title">
              <i class="glyphicon glyphicon-user"></i>
              Crear Cuenta en {{ authStore.appName }}
            </h3>
          </div>
          <div class="panel-body">
            <div v-if="errorMsg" class="alert alert-warning" role="alert">
              <i class="glyphicon glyphicon-exclamation-sign"></i> {{ errorMsg }}
            </div>

            <form class="form-horizontal" @submit.prevent="submitRegister">
              <div class="form-group">
                <label for="reg-fullname" class="col-sm-4 control-label">Nombre completo</label>
                <div class="col-sm-8">
                  <input
                    id="reg-fullname"
                    type="text"
                    class="form-control"
                    v-model="form.full_name"
                    placeholder="Ej: María Pérez"
                    required
                  />
                </div>
              </div>

              <div class="form-group">
                <label for="reg-username" class="col-sm-4 control-label">Usuario</label>
                <div class="col-sm-8">
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

              <div class="form-group">
                <label for="reg-email" class="col-sm-4 control-label">Correo (opcional)</label>
                <div class="col-sm-8">
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

              <div class="form-group">
                <label for="reg-password" class="col-sm-4 control-label">Contraseña</label>
                <div class="col-sm-8">
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
                      <button type="button" class="btn btn-default" @click="showPass = !showPass">
                        <i :class="showPass ? 'glyphicon glyphicon-eye-close' : 'glyphicon glyphicon-eye-open'"></i>
                      </button>
                    </span>
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label for="reg-confirm" class="col-sm-4 control-label">Confirmar</label>
                <div class="col-sm-8">
                  <div class="input-group">
                    <input
                      id="reg-confirm"
                      :type="showConfirm ? 'text' : 'password'"
                      class="form-control"
                      v-model="form.confirm_password"
                      placeholder="Repita la contraseña"
                      autocomplete="new-password"
                      minlength="6"
                      required
                    />
                    <span class="input-group-btn">
                      <button type="button" class="btn btn-default" @click="showConfirm = !showConfirm">
                        <i :class="showConfirm ? 'glyphicon glyphicon-eye-close' : 'glyphicon glyphicon-eye-open'"></i>
                      </button>
                    </span>
                  </div>
                </div>
              </div>

              <div class="form-group">
                <div class="col-sm-offset-4 col-sm-8">
                  <button type="submit" class="btn btn-success btn-block" :disabled="submitting">
                    <i class="glyphicon glyphicon-ok-sign"></i>
                    {{ submitting ? ' Creando cuenta…' : ' Crear mi cuenta' }}
                  </button>
                </div>
              </div>
            </form>

            <hr style="margin:15px 0;" />
            <div class="text-center">
              <span class="text-muted">¿Ya tienes una cuenta?</span>
              <router-link to="/login" class="btn btn-link" style="padding:0 4px;font-weight:600;">
                Iniciar sesión
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/useAuthStore'

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
