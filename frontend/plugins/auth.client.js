export default defineNuxtPlugin(async () => {
  const authStore = useAuthStore();

  // Initialiser l'authentification côté client
  if (process.client) {
    await authStore.init();
  }
});
