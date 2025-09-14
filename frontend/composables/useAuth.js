export const useAuth = () => {
  const authStore = useAuthStore();

  // Initialiser le store si nécessaire seulement dans un contexte de composant
  if (process.client && getCurrentInstance()) {
    onMounted(() => {
      authStore.init();
    });
  } else if (process.client) {
    // Si on n'est pas dans un composant mais qu'on est côté client, initialiser directement
    authStore.init();
  }

  return {
    user: computed(() => authStore.user),
    isAuthenticated: computed(() => authStore.isAuthenticated),
    login: authStore.login,
    logout: authStore.logout,
    fetchUser: authStore.fetchUser,
  };
};
