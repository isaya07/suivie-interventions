<template>
  <div>
    <AppHeader />

    <main class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
      <div class="px-4 py-6 sm:px-0">
        <!-- En-tête -->
        <div class="flex items-center justify-between mb-8">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Détails client</h1>
            <p class="text-gray-600 dark:text-gray-400">Informations détaillées du client</p>
          </div>

          <NuxtLink
            to="/clients"
            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Retour à la liste
          </NuxtLink>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-8">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        </div>

        <!-- Contenu client -->
        <div v-else-if="client" class="space-y-6">
          <!-- Informations principales -->
          <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900">Informations générales</h3>
            </div>
            <div class="px-6 py-4">
              <div class="flex items-center mb-6">
                <div class="h-20 w-20 rounded-full bg-blue-600 flex items-center justify-center text-white text-2xl font-medium">
                  {{ client.nom?.[0] || "C" }}
                </div>
                <div class="ml-6">
                  <h2 class="text-xl font-semibold text-gray-900">{{ client.nom }}</h2>
                  <p class="text-gray-600">{{ client.contact_principal || 'Pas de contact principal' }}</p>
                  <div class="flex flex-wrap gap-2 mt-2">
                    <span
                      v-if="client.latitude && client.longitude"
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                    >
                      📍 Géolocalisé
                    </span>
                    <span
                      v-else
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"
                    >
                      📍 Position manquante
                    </span>
                  </div>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    Email
                  </label>
                  <p class="text-sm text-gray-900">{{ client.email || 'Non renseigné' }}</p>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    Téléphone
                  </label>
                  <p class="text-sm text-gray-900">{{ client.telephone || 'Non renseigné' }}</p>
                </div>

                <div class="md:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    Adresse
                  </label>
                  <div v-if="client.adresse || client.ville || client.code_postal" class="text-sm text-gray-900">
                    <div v-if="client.adresse">{{ client.adresse }}</div>
                    <div v-if="client.ville || client.code_postal">
                      {{ client.ville }} {{ client.code_postal }}
                    </div>
                  </div>
                  <p v-else class="text-sm text-gray-500">Adresse non renseignée</p>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    Créé le
                  </label>
                  <p class="text-sm text-gray-900">{{ formatDate(client.created_at) }}</p>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    Mis à jour le
                  </label>
                  <p class="text-sm text-gray-900">{{ formatDate(client.updated_at) }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Coordonnées GPS -->
          <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900">🌍 Localisation GPS</h3>
            </div>
            <div class="px-6 py-4">
              <div v-if="client.latitude && client.longitude" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Latitude
                    </label>
                    <p class="text-sm text-gray-900 font-mono">{{ client.latitude }}</p>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Longitude
                    </label>
                    <p class="text-sm text-gray-900 font-mono">{{ client.longitude }}</p>
                  </div>
                </div>

                <!-- Liens vers les cartes -->
                <div class="flex flex-wrap gap-3 mt-4">
                  <a
                    :href="googleMapsUrl"
                    target="_blank"
                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                  >
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                    </svg>
                    Voir sur Google Maps
                  </a>

                  <a
                    :href="wazeUrl"
                    target="_blank"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                  >
                    🧭 Navigation Waze
                  </a>
                </div>
              </div>

              <div v-else class="text-center py-8 text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <p class="mt-2">Aucune coordonnée GPS renseignée</p>
                <p class="text-sm">Modifiez le client pour ajouter sa localisation</p>
              </div>
            </div>
          </div>

          <!-- Notes -->
          <div v-if="client.notes" class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900">📝 Notes</h3>
            </div>
            <div class="px-6 py-4">
              <div class="text-sm text-gray-900 whitespace-pre-wrap">{{ client.notes }}</div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex justify-end space-x-4">
            <NuxtLink
              to="/clients"
              class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors"
            >
              Retour
            </NuxtLink>

            <button
              @click="editClient"
              class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 transition-colors"
            >
              Modifier
            </button>
          </div>
        </div>

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
    </main>
  </div>
</template>

<script setup>
definePageMeta({
  middleware: ['auth']
})

const route = useRoute()
const router = useRouter()
const { fetchClient, loading } = useClients()

const clientId = route.params.id
const client = ref(null)

const googleMapsUrl = computed(() => {
  if (!client.value?.latitude || !client.value?.longitude) return '#'
  return `https://www.google.com/maps/search/?api=1&query=${client.value.latitude},${client.value.longitude}`
})

const wazeUrl = computed(() => {
  if (!client.value?.latitude || !client.value?.longitude) return '#'
  return `https://waze.com/ul?ll=${client.value.latitude}%2C${client.value.longitude}&navigate=yes`
})

const formatDate = (dateString) => {
  if (!dateString) return 'Non définie'
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const editClient = () => {
  router.push(`/clients/${clientId}/edit`)
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