<template>
  <div>
    <AppHeader />
    
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
      <div class="px-4 py-6 sm:px-0">
        <!-- En-tête du dashboard -->
        <div class="mb-8">
          <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
          <p class="text-gray-600">Vue d'ensemble de vos interventions</p>
        </div>
        
        <!-- Statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
              <div class="p-2 bg-blue-100 rounded-lg">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-2xl font-semibold text-gray-900">{{ stats.total }}</p>
                <p class="text-gray-600">Total interventions</p>
              </div>
            </div>
          </div>
          
          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
              <div class="p-2 bg-yellow-100 rounded-lg">
                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-2xl font-semibold text-gray-900">{{ stats.enCours }}</p>
                <p class="text-gray-600">En cours</p>
              </div>
            </div>
          </div>
          
          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
              <div class="p-2 bg-green-100 rounded-lg">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-2xl font-semibold text-gray-900">{{ stats.terminees }}</p>
                <p class="text-gray-600">Terminées</p>
              </div>
            </div>
          </div>
          
          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
              <div class="p-2 bg-purple-100 rounded-lg">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-2xl font-semibold text-gray-900">{{ techniciens.length }}</p>
                <p class="text-gray-600">Techniciens</p>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Actions rapides -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Interventions récentes -->
          <div class="lg:col-span-2 bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
              <div class="flex items-center justify-between">
                <h2 class="text-lg font-medium text-gray-900">Interventions récentes</h2>
                <NuxtLink to="/interventions" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                  Voir tout
                </NuxtLink>
              </div>
            </div>
            <div class="p-6">
              <div v-if="loading" class="flex justify-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
              </div>
              
              <div v-else-if="recentInterventions.length === 0" class="text-center text-gray-500 py-4">
                Aucune intervention récente
              </div>
              
              <div v-else class="space-y-4">
                <div
                  v-for="intervention in recentInterventions"
                  :key="intervention.id"
                  class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50"
                >
                  <div>
                    <h3 class="font-medium text-gray-900">{{ intervention.titre }}</h3>
                    <p class="text-sm text-gray-600">{{ intervention.technicien_nom }}</p>
                    <span
                      :class="getStatusClasses(intervention.status)"
                      class="inline-block px-2 py-1 text-xs font-medium rounded-full mt-1"
                    >
                      {{ getStatusText(intervention.status) }}
                    </span>
                  </div>
                  <NuxtLink
                    :to="`/interventions/${intervention.id}`"
                    class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                  >
                    Voir
                  </NuxtLink>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Actions rapides -->
          <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
              <h2 class="text-lg font-medium text-gray-900">Actions rapides</h2>
            </div>
            <div class="p-6 space-y-4">
              <NuxtLink
                to="/interventions/create"
                class="block w-full text-center bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors"
              >
                Nouvelle intervention
              </NuxtLink>
              
              <NuxtLink
                to="/interventions?status=1"
                class="block w-full text-center bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg transition-colors"
              >
                Interventions en cours
              </NuxtLink>
              
              <NuxtLink
                v-if="user?.role === 'admin'"
                to="/users"
                class="block w-full text-center bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition-colors"
              >
                Gérer les utilisateurs
              </NuxtLink>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
definePageMeta({
  middleware: 'auth'
})

const { user } = useAuth()
const { interventions, interventionsByStatus, fetchInterventions, loading } = useInterventions()
const { techniciens, fetchTechniciens } = useUsers()

const stats = computed(() => interventionsByStatus.value)
const recentInterventions = computed(() => interventions.value.slice(0, 5))

const getStatusClasses = (status) => {
  return {
    1: 'bg-yellow-100 text-yellow-800',
    2: 'bg-green-100 text-green-800'
  }[status] || 'bg-gray-100 text-gray-800'
}

const getStatusText = (status) => {
  return {
    1: 'En cours',
    2: 'Terminée'
  }[status] || 'Inconnu'
}

onMounted(async () => {
  await Promise.all([
    fetchInterventions(),
    fetchTechniciens()
  ])
})
</script>
