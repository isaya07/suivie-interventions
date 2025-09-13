export default defineNuxtConfig({
  devtools: { enabled: true },

  modules: ["@pinia/nuxt", "@nuxtjs/tailwindcss"],

  // Configuration Pinia
  pinia: {
    autoImports: ["defineStore", "storeToRefs"],
  },

  runtimeConfig: {
    public: {
      apiBaseUrl: process.env.API_BASE_URL || "http://localhost/api",
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
