<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <Card class="w-full max-w-md">
      <template #header>
        <div class="text-center pt-6 pb-2">
          <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100">
            <i class="pi pi-cog text-blue-600 text-xl"></i>
          </div>
          <h2 class="mt-4 text-2xl font-bold text-gray-900 dark:text-gray-100">
            Connexion
          </h2>
          <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            Accédez à votre espace de gestion
          </p>
        </div>
      </template>

      <template #content>
        <form @submit.prevent="handleLogin" class="space-y-6 p-6">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Email ou nom d'utilisateur
              </label>
              <InputText
                v-model="form.username"
                type="text"
                required
                placeholder="votre@email.com ou nom_utilisateur"
                class="w-full"
                :class="{ 'p-invalid': error }"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Mot de passe
              </label>
              <Password
                v-model="form.password"
                required
                placeholder="••••••••"
                class="w-full"
                :class="{ 'p-invalid': error }"
                :feedback="false"
                :toggleMask="true"
              />
            </div>
          </div>

          <Button
            type="submit"
            :loading="loading"
            :label="loading ? 'Connexion...' : 'Se connecter'"
            class="w-full p-button-lg"
            :disabled="loading"
          />

          <Message
            v-if="error"
            severity="error"
            :closable="false"
          >
            {{ error }}
          </Message>
        </form>
      </template>

      <template #footer>
        <div class="text-center text-xs text-gray-500 pb-4">
          © {{ new Date().getFullYear() }} Système de gestion des interventions
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup>
definePageMeta({
  layout: false,
  middleware: (to, from) => {
    const authStore = useAuthStore();

    if (authStore.isAuthenticated) {
      return navigateTo("/dashboard");
    }
  },
});

const authStore = useAuthStore();
const router = useRouter();

const loading = ref(false);
const error = ref("");

const form = ref({
  username: "",
  password: "",
});

const handleLogin = async () => {
  loading.value = true;
  error.value = "";

  try {
    const result = await authStore.login(form.value);

    if (result.success) {
      await router.push("/dashboard");
    } else {
      error.value = result.message || "Erreur de connexion";
    }
  } catch (err) {
    error.value = "Erreur de connexion";
    console.error(err);
  } finally {
    loading.value = false;
  }
};
</script>
