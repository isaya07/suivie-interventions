<template>
  <div>
    <AppHeader />
    
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
      <div class="px-4 py-6 sm:px-0">
        <!-- En-tête -->
        <div class="flex justify-between items-center mb-6">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Interventions</h1>
            <p class="text-gray-600">Gérez toutes vos interventions techniques</p>
          </div>
          
          <NuxtLink
            to="/interventions/create"
            class="btn-primary flex items-center"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Nouvelle intervention
          </NuxtLink>
        </div>
        
        <!-- Filtres -->
        <InterventionFilters />
        
        <!-- Liste des interventions -->
        <div v-if="loading" class="flex justify-center py-8">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        </div>
        
        <div v-else-if="filteredInterventions.length === 0" class="text-center py-8">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">Aucune intervention</h3>
          <p class="mt-1 text-sm text-gray-500">Commencez par créer une nouvelle intervention.</p>
          <div class="mt-6">
            <NuxtLink to="/interventions/create" class="btn-primary">
              Nouvelle intervention
            </NuxtLink>
          </div>
        </div>
        
        <div v-else class="space-y-4">
          <InterventionCard
            v-for="intervention in filteredInterventions"
            :key="intervention.id"
            :intervention="intervention"
            @complete="handleComplete"
          />
        </div>
        
        <!-- Pagination (si nécessaire) -->
        <div v-if="filteredInterventions.length > 0" class="mt-8 flex justify-between items-center">
          <p class="text-sm text-gray-700">
            Affichage de {{ filteredInterventions.length }} intervention(s)
          </p>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
definePageMeta({
  middleware: 'auth'
})

const { filteredInterventions, fetchInterventions, updateIntervention, loading } = useInterventions()

const handleComplete = async (interventionId) => {
  try {
    await updateIntervention({
      id: interventionId,
      status: 2,
      date_fin: new Date().toISOString().split('T')[0]
    })
  } catch (error) {
    console.error('Erreur lors de la finalisation:', error)
  }
}

onMounted(() => {
  fetchInterventions()
})
</script>
