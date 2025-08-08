export const useAuth = () => {
  const authStore = useAuthStore()
  
  return {
    user: computed(() => authStore.user),
    isAuthenticated: computed(() => authStore.isAuthenticated),
    login: authStore.login,
    logout: authStore.logout,
    fetchUser: authStore.fetchUser
  }
}