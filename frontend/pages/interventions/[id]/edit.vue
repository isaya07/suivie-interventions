<template>
  <div>
    <AppHeader />

    <main class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
      <div class="px-4 py-6 sm:px-0">
        <!-- En-tête -->
        <div class="flex items-center justify-between mb-8">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Modifier l'intervention</h1>
            <p class="text-gray-600">Modifiez les détails de l'intervention</p>
          </div>

          <NuxtLink
            to="/interventions"
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

        <!-- Formulaire -->
        <div v-else-if="intervention" class="bg-white shadow rounded-lg">
          <form @submit.prevent="handleSubmit" class="space-y-6 p-6">
            <!-- Informations générales -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label for="titre" class="block text-sm font-medium text-gray-700 mb-2">
                  Titre <span class="text-red-500">*</span>
                </label>
                <input
                  id="titre"
                  v-model="form.titre"
                  type="text"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  placeholder="Titre de l'intervention"
                />
              </div>

              <div>
                <label for="statut" class="block text-sm font-medium text-gray-700 mb-2">
                  Statut
                </label>
                <select
                  id="statut"
                  v-model="form.statut"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                  <option value="En attente">En attente</option>
                  <option value="En cours">En cours</option>
                  <option value="En pause">En pause</option>
                  <option value="Terminée">Terminée</option>
                  <option value="Annulée">Annulée</option>
                </select>
              </div>
            </div>

            <div>
              <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                Description
              </label>
              <textarea
                id="description"
                v-model="form.description"
                rows="4"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Description détaillée de l'intervention"
              ></textarea>
            </div>

            <!-- Client et Technicien -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label for="client_nom" class="block text-sm font-medium text-gray-700 mb-2">
                  Client
                </label>
                <input
                  id="client_nom"
                  v-model="form.client_nom"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  placeholder="Nom du client"
                />
              </div>

              <div>
                <label for="technicien_nom" class="block text-sm font-medium text-gray-700 mb-2">
                  Technicien
                </label>
                <input
                  id="technicien_nom"
                  v-model="form.technicien_nom"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  placeholder="Nom du technicien"
                />
              </div>
            </div>

            <!-- Priorité et Dates -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div>
                <label for="priorite" class="block text-sm font-medium text-gray-700 mb-2">
                  Priorité
                </label>
                <select
                  id="priorite"
                  v-model="form.priorite"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                  <option value="Basse">Basse</option>
                  <option value="Normale">Normale</option>
                  <option value="Haute">Haute</option>
                  <option value="Urgente">Urgente</option>
                </select>
              </div>

              <div>
                <label for="date_intervention" class="block text-sm font-medium text-gray-700 mb-2">
                  Date d'intervention
                </label>
                <input
                  id="date_intervention"
                  v-model="form.date_intervention"
                  type="datetime-local"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
              </div>

              <div>
                <label for="date_fin" class="block text-sm font-medium text-gray-700 mb-2">
                  Date de fin
                </label>
                <input
                  id="date_fin"
                  v-model="form.date_fin"
                  type="datetime-local"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
              </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
              <NuxtLink
                to="/interventions"
                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors"
              >
                Annuler
              </NuxtLink>

              <button
                type="submit"
                :disabled="saving"
                class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                <span v-if="saving">Sauvegarde...</span>
                <span v-else>Sauvegarder</span>
              </button>
            </div>
          </form>
        </div>

        <!-- Erreur -->
        <div v-else class="text-center py-8">
          <div class="text-red-600 mb-4">
            <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 18.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
          </div>
          <h3 class="text-lg font-medium text-gray-900 mb-2">Intervention non trouvée</h3>
          <p class="text-gray-500 mb-4">L'intervention demandée n'existe pas ou n'est plus accessible.</p>
          <NuxtLink to="/interventions" class="btn-primary">
            Retour à la liste
          </NuxtLink>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
definePageMeta({
  middleware: 'auth'
})

const route = useRoute()
const router = useRouter()
const { fetchIntervention, updateIntervention } = useInterventions()

const interventionId = route.params.id
const loading = ref(true)
const saving = ref(false)
const intervention = ref(null)

const form = ref({
  titre: '',
  description: '',
  client_nom: '',
  technicien_nom: '',
  statut: 'En attente',
  priorite: 'Normale',
  date_intervention: '',
  date_fin: ''
})

const handleSubmit = async () => {
  if (saving.value) return

  saving.value = true
  try {
    const result = await updateIntervention({
      id: interventionId,
      ...form.value
    })

    if (result.success) {
      await router.push('/interventions')
    } else {
      alert('Erreur lors de la mise à jour: ' + (result.message || 'Erreur inconnue'))
    }
  } catch (error) {
    console.error('Erreur lors de la mise à jour:', error)
    alert('Erreur lors de la mise à jour de l\'intervention')
  } finally {
    saving.value = false
  }
}

const formatDateTimeLocal = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')
  return `${year}-${month}-${day}T${hours}:${minutes}`
}

onMounted(async () => {
  try {
    const result = await fetchIntervention(interventionId)
    if (result) {
      intervention.value = result

      // Pré-remplir le formulaire
      form.value = {
        titre: result.titre || '',
        description: result.description || '',
        client_nom: result.client_nom || '',
        technicien_nom: result.technicien_nom || '',
        statut: result.statut || 'En attente',
        priorite: result.priorite || 'Normale',
        date_intervention: formatDateTimeLocal(result.date_intervention),
        date_fin: formatDateTimeLocal(result.date_fin)
      }
    }
  } catch (error) {
    console.error('Erreur lors du chargement de l\'intervention:', error)
  } finally {
    loading.value = false
  }
})
</script>