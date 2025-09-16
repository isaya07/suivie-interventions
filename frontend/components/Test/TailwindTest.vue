<!--
  Composant de test pour vérifier que Tailwind CSS v4 fonctionne
-->
<template>
  <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">
      Test Tailwind CSS v4
    </h2>

    <div class="space-y-3">
      <p class="text-sm text-gray-600 dark:text-gray-400">
        Texte small - text-sm
      </p>

      <p class="text-base font-medium text-blue-600">
        Texte base medium blue - text-base font-medium text-blue-600
      </p>

      <div class="flex items-center gap-2">
        <div class="w-4 h-4 bg-green-500 rounded"></div>
        <span class="text-green-700">Classe w-4 h-4 bg-green-500</span>
      </div>

      <div class="grid grid-cols-2 gap-4 mt-4">
        <div class="p-2 bg-surface-100 rounded">Surface 100</div>
        <div class="p-2 bg-primary-500 text-white rounded">Primary 500</div>
      </div>
    </div>
  </div>
</template>

<script setup>
// Composant de test simple
</script>

<style scoped>
/* Pas de styles CSS personnalisés - utility-first pur */
</style>