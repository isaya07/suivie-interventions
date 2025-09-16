/**
 * Composable d'authentification
 * Fournit une interface simplifiée pour accéder aux fonctionnalités d'auth
 * Gère l'initialisation automatique du store d'authentification
 *
 * @returns {Object} Interface d'authentification avec propriétés et méthodes réactives
 */
export const useAuth = () => {
  // Récupération du store d'authentification Pinia
  const authStore = useAuthStore();

  // Gestion de l'initialisation selon le contexte d'exécution
  if (process.client && getCurrentInstance()) {
    // Dans un composant Vue : initialiser via onMounted
    onMounted(() => {
      authStore.init();
    });
  } else if (process.client) {
    // En dehors d'un composant mais côté client : initialiser directement
    authStore.init();
  }

  // Interface publique du composable
  return {
    // Propriétés réactives (computed refs)
    user: computed(() => authStore.user),                    // Utilisateur connecté
    isAuthenticated: computed(() => authStore.isAuthenticated), // État de connexion

    // Méthodes d'authentification
    login: authStore.login,       // Connexion utilisateur
    logout: authStore.logout,     // Déconnexion utilisateur
    fetchUser: authStore.fetchUser, // Récupération des données utilisateur
  };
};
