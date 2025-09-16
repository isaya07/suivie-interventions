<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
    <div class="container mx-auto px-4 py-16">
      <div class="text-center">
        <h1 class="text-5xl font-bold text-gray-900 mb-6">
          Gestion d'Interventions
        </h1>
        <p class="text-xl text-gray-600 mb-8 max-w-2xl mx-auto">
          Plateforme moderne pour la gestion des interventions techniques. 
          Suivez vos missions, gérez vos équipes et optimisez vos performances.
        </p>
        
        <div class="space-x-4">
          <NuxtLink
            to="/login"
            class="inline-flex items-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors"
          >
            Se connecter
          </NuxtLink>
          
          <NuxtLink
            to="/dashboard"
            class="inline-flex items-center px-8 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors"
          >
            Accéder au dashboard
          </NuxtLink>
        </div>
      </div>
      
      <div class="mt-20 grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="text-center">
          <Card>
            <template #content>
              <div class="w-16 h-16 bg-blue-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
              </div>
              <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Gestion des missions</h3>
              <p class="text-gray-600 dark:text-gray-400">Créez, assignez et suivez toutes vos interventions techniques.</p>
            </template>
          </Card>
        </div>
        
        <div class="text-center">
          <Card>
            <template #content>
              <div class="w-16 h-16 bg-green-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
              </div>
              <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Équipe</h3>
              <p class="text-gray-600 dark:text-gray-400">Gérez vos techniciens et optimisez leur planning.</p>
            </template>
          </Card>
        </div>
        
        <div class="text-center">
          <Card>
            <template #content>
              <div class="w-16 h-16 bg-purple-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
              </div>
              <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Reporting</h3>
              <p class="text-gray-600 dark:text-gray-400">Analysez les performances et générez des rapports détaillés.</p>
            </template>
          </Card>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
definePageMeta({
  layout: false
})
</script>