import { defineStore } from "pinia";

export const useAuthStore = defineStore("auth", () => {
  const user = ref(null);
  const token = useCookie("auth-token", {
    maxAge: 60 * 60 * 24 * 7, // 7 jours
    secure: true,
    sameSite: "strict",
  });
  const isAuthenticated = computed(() => !!user.value && !!token.value);

  const { $api } = useNuxtApp();

  const login = async (credentials) => {
    try {
      const response = await $api("/auth.php?action=login", {
        method: "POST",
        body: credentials,
      });

      if (response.success) {
        user.value = response.user;
        token.value = response.token;
        return { success: true };
      }

      return { success: false, message: response.message };
    } catch (error) {
      console.error("Erreur de connexion:", error);
      return { success: false, message: "Erreur de connexion" };
    }
  };

  const logout = async () => {
    try {
      await $api("/auth.php?action=logout", { method: "POST" });
    } catch (error) {
      console.error("Erreur lors de la déconnexion:", error);
    } finally {
      user.value = null;
      token.value = null;
      await navigateTo("/login");
    }
  };

  const fetchUser = async () => {
    try {
      if (!token.value) return;

      const response = await $api("/auth.php?action=me");
      if (response.success) {
        user.value = response.user;
        return true;
      } else {
        // Token invalide
        token.value = null;
        user.value = null;
        return false;
      }
    } catch (error) {
      console.error("Erreur lors de la récupération de l'utilisateur:", error);
      token.value = null;
      user.value = null;
      return false;
    }
  };

  // Initialiser l'utilisateur si un token existe
  const init = async () => {
    if (token.value && !user.value) {
      await fetchUser();
    }
  };

  return {
    user: readonly(user),
    isAuthenticated,
    login,
    logout,
    fetchUser,
    init,
  };
});
