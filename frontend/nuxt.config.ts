export default defineNuxtConfig({
  devtools: { enabled: true },

  modules: ["@pinia/nuxt", "@nuxtjs/tailwindcss"],

  // Configuration des composants
  components: [
    {
      path: '~/components',
      pathPrefix: false,
    },
  ],

  // Configuration Pinia
  pinia: {
    autoImports: ["defineStore", "storeToRefs"],
  },

  runtimeConfig: {
    public: {
      apiBaseUrl: process.env.API_BASE_URL || "http://localhost/suivie-interventions/backend/api",
    },
  },

  css: ["~/assets/css/main.css"],

  app: {
    head: {
      title: "Gestion Interventions",
      meta: [
        {
          name: "description",
          content: "Application de gestion des interventions techniques",
        },
      ],
    },
  },

  ssr: false, // Pour une SPA avec authentification
});
