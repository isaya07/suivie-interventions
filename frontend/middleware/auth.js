export default defineNuxtRouteMiddleware(async (to) => {
  // Utiliser directement le store sans composable dans le middleware
  const authStore = useAuthStore();

  // Vérifier si l'utilisateur est authentifié
  if (!authStore.isAuthenticated) {
    // Essayer de récupérer l'utilisateur si un token existe
    const hasUser = await authStore.fetchUser();

    if (!hasUser) {
      return navigateTo("/login");
    }
  }
});
