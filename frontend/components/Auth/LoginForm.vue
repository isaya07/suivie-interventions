<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          Connexion
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          Accédez à votre espace de gestion
        </p>
      </div>
      
      <form class="mt-8 space-y-6" @submit.prevent="handleLogin">
        <div class="space-y-4">
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700">
              Email
            </label>
            <InputText
              id="email"
              v-model="form.email"
              type="email"
              required
              :invalid="!!errors.email"
              placeholder="votre@email.com"
              class="w-full"
            />
            <p v-if="errors.email" class="mt-1 text-sm text-red-600">
              {{ errors.email }}
            </p>
          </div>
          
          <div>
            <label for="password" class="block text-sm font-medium text-gray-700">
              Mot de passe
            </label>
            <Password
              id="password"
              v-model="form.password"
              required
              :invalid="!!errors.password"
              placeholder="••••••••"
              :feedback="false"
              class="w-full"
            />
            <p v-if="errors.password" class="mt-1 text-sm text-red-600">
              {{ errors.password }}
            </p>
          </div>
        </div>
        
        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <Checkbox
              id="remember"
              v-model="form.remember"
              :binary="true"
            />
            <label for="remember" class="ml-2 block text-sm text-gray-900">
              Se souvenir de moi
            </label>
          </div>
        </div>
        
        <div>
          <Button
            type="submit"
            :disabled="loading"
            :loading="loading"
            loadingIcon="pi pi-spin pi-spinner"
            label="Se connecter"
            class="w-full"
          />
        </div>
        
        <Message v-if="error" severity="error" :closable="false">
          {{ error }}
        </Message>
      </form>
    </div>
  </div>
</template>

<script setup>
const { login } = useAuth()
const router = useRouter()

const loading = ref(false)
const error = ref('')
const errors = ref({})

const form = ref({
  email: '',
  password: '',
  remember: false
})

const validateForm = () => {
  errors.value = {}
  
  if (!form.value.email) {
    errors.value.email = 'L\'email est requis'
  } else if (!/\S+@\S+\.\S+/.test(form.value.email)) {
    errors.value.email = 'Format d\'email invalide'
  }
  
  if (!form.value.password) {
    errors.value.password = 'Le mot de passe est requis'
  } else if (form.value.password.length < 6) {
    errors.value.password = 'Le mot de passe doit contenir au moins 6 caractères'
  }
  
  return Object.keys(errors.value).length === 0
}

const handleLogin = async () => {
  if (!validateForm()) return
  
  loading.value = true
  error.value = ''
  
  try {
    const result = await login(form.value)
    
    if (result.success) {
      await router.push('/dashboard')
    } else {
      error.value = result.message || 'Erreur de connexion'
    }
  } catch (err) {
    error.value = 'Erreur de connexion'
  } finally {
    loading.value = false
  }
}
</script>