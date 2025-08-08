<template>
  <div>
    <AppHeader />
    
    <main class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
      <div class="px-4 py-6 sm:px-0">
        <!-- En-tête -->
        <div class="mb-8">
          <nav class="flex" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-4">
              <li>
                <NuxtLink to="/interventions" class="text-gray-400 hover:text-gray-500">
                  Interventions
                </NuxtLink>
              </li>
              <li class="flex items-center">
                <svg class="flex-shrink-0 h-5 w-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
                <span class="ml-4 text-gray-500">Nouvelle intervention</span>
              </li>
            </ol>
          </nav>
          
          <h1 class="mt-4 text-2xl font-bold text-gray-900">Créer une intervention</h1>
          <p class="text-gray-600">Remplissez les informations pour créer une nouvelle intervention.</p>
        </div>
        
        <!-- Formulaire -->
        <div class="card">
          <InterventionForm
            @submit="handleSubmit"
            @cancel="handleCancel"
          />
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
definePageMeta({
  middleware: 'auth'
})

const router = useRouter()

const handleSubmit = (intervention) => {
  // Navigation vers la page de détail de l'intervention créée
  router.push(`/interventions/${intervention.id}`)
}

const handleCancel = () => {
  router.back()
}
</script>
