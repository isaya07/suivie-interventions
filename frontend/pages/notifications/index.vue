<template>
  <div>
      <!-- En-tête -->
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-surface-900 dark:text-surface-50 mb-2">
          Centre de Notifications
        </h1>
        <p class="text-surface-600 dark:text-surface-400">
          Gestion des notifications et alertes du système
        </p>
      </div>
      <!-- Centre de notifications intégré -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <NotificationCenter :show-as-page="true" />
      </div>
      <!-- Liens rapides -->
      <div class="mt-6 flex gap-4">
        <NuxtLink
          to="/notifications/history"
          class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
        >
          <i class="pi pi-history mr-2"></i>
          Voir l'historique complet
        </NuxtLink>
      </div>
  </div>
</template>
<script setup>
// Métadonnées de la page
definePageMeta({
  title: 'Centre de Notifications',
  requiresAuth: true
})
</script>