<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="max-w-md w-full">
      <form
        @submit.prevent="handleLogin"
        class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4"
      >
        <h2 class="text-2xl font-bold text-center mb-6">Connexion</h2>

        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">
            Email ou nom d'utilisateur
          </label>
          <input
            v-model="form.username"
            type="text"
            required
            class="form-input"
            placeholder="votre@email.com ou nom_utilisateur"
          />
        </div>

        <div class="mb-6">
          <label class="block text-gray-700 text-sm font-bold mb-2">
            Mot de passe
          </label>
          <input
            v-model="form.password"
            type="password"
            required
            class="form-input"
            placeholder="••••••••"
          />
        </div>

        <div class="flex items-center justify-between">
          <button type="submit" :disabled="loading" class="btn-primary w-full">
            {{ loading ? "Connexion..." : "Se connecter" }}
          </button>
        </div>

        <div
          v-if="error"
          class="mt-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded"
        >
          {{ error }}
        </div>
      </form>
    </div>
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
