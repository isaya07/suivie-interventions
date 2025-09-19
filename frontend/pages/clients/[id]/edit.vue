<template>
  <div>
        <!-- En-tête -->
        <div class="flex items-center justify-between mb-8">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">
              {{ client ? `Modifier ${client.nom}` : 'Modifier le client' }}
            </h1>
            <p class="text-gray-600">Modifiez les informations du client</p>
          </div>
          <NuxtLink
            :to="`/clients/${clientId}`"
            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Annuler
          </NuxtLink>
        </div>
        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-8">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        </div>
        <!-- Formulaire d'édition -->
        <Card v-else-if="client">
          <template #content>
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Informations du client</h3>
            </div>
            <div class="px-6 py-6">
            <ClientForm
              :client="client"
              @submit="handleClientSubmit"
              @cancel="handleCancel"
            />
            </div>
          </template>
        </Card>
        <!-- Erreur -->
        <div v-else class="text-center py-8">
          <div class="text-red-600 mb-4">
            <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 18.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
          </div>
          <h3 class="text-lg font-medium text-gray-900 mb-2">Client non trouvé</h3>
          <p class="text-gray-500 mb-4">Le client demandé n'existe pas ou n'est plus accessible.</p>
          <NuxtLink to="/clients" class="btn-primary">
            Retour à la liste
          </NuxtLink>
        </div>
  </div>
</template>
<script setup>
definePageMeta({
  middleware: ['auth']
})
const route = useRoute()
const router = useRouter()
const { fetchClient, updateClient, loading } = useClients()
const clientId = route.params.id
const client = ref(null)
const handleClientSubmit = async (clientData) => {
  const result = await updateClient(clientData)
  if (result.success) {
    // Redirect to client detail page
    router.push(`/clients/${clientId}`)
  } else {
    alert(`Erreur: ${result.message}`)
  }
}
const handleCancel = () => {
  router.push(`/clients/${clientId}`)
}
onMounted(async () => {
  try {
    const result = await fetchClient(clientId)
    if (result) {
      client.value = result
    }
  } catch (error) {
    console.error('Erreur lors du chargement du client:', error)
  }
})
</script>