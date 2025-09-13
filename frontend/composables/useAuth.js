export const useAuth = () => {
  const authStore = useAuthStore();

  // Initialiser le store si nécessaire
  onMounted(() => {
    authStore.init();
  });

  return {
    user: computed(() => authStore.user),
    isAuthenticated: computed(() => authStore.isAuthenticated),
    login: authStore.login,
    logout: authStore.logout,
    fetchUser: authStore.fetchUser,
  };
};
