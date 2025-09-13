export default defineNuxtPlugin(() => {
  const config = useRuntimeConfig();

  const api = $fetch.create({
    baseURL: config.public.apiBaseUrl,

    onRequest({ request, options }) {
      // Ajouter le token d'authentification si disponible
      const token = useCookie("auth-token");
      if (token.value) {
        options.headers = {
          ...options.headers,
          Authorization: `Bearer ${token.value}`,
          "Content-Type": "application/json",
        };
      }
    },

    onResponseError({ response }) {
      if (response.status === 401) {
        // Token expiré, nettoyer et rediriger
        const token = useCookie("auth-token");
        token.value = null;

        // Éviter la boucle infinie si on est déjà sur la page login
        if (process.client && window.location.pathname !== "/login") {
          navigateTo("/login");
        }
      }
    },
  });

  return {
    provide: {
      api,
    },
  };
});
