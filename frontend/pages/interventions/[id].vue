<template>
  <div>
    <div>
    <div>
      <!-- En-tête -->
        <div class="mb-8">
          <nav class="flex mb-4" aria-label="Breadcrumb">
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
                <span class="ml-4 text-gray-500">{{ intervention?.titre || 'Chargement...' }}</span>
              </li>
            </ol>
          </nav>
          <div v-if="intervention" class="flex justify-between items-start">
            <div>
              <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ intervention.titre }}</h1>
              <div class="flex items-center mt-2 space-x-4">
                <span
                  :class="statusClasses"
                  class="px-3 py-1 text-sm font-medium rounded-full"
                >
                  {{ statusText }}
                </span>
                <span class="text-sm text-gray-500">
                  Créée le {{ formatDate(intervention.date_creation) }}
                </span>
              </div>
            </div>
            <div class="flex space-x-3">
              <button
                v-if="intervention.status === 1"
                @click="completeIntervention"
                class="btn-primary"
              >
                Marquer terminée
              </button>
              <button
                @click="showEditModal = true"
                class="btn-secondary"
              >
                Modifier
              </button>
            </div>
          </div>
        </div>
        <!-- Contenu principal -->
        <div v-if="loading" class="flex justify-center py-8">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        </div>
        <div v-else-if="!intervention" class="text-center py-8">
          <h3 class="text-lg font-medium text-gray-900">Intervention non trouvée</h3>
          <p class="text-gray-500">L'intervention demandée n'existe pas.</p>
        </div>
        <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Détails de l'intervention -->
          <div class="lg:col-span-2 space-y-6">
            <Card>
              <template #content>
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Détails</h2>
              <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <dt class="text-sm font-medium text-gray-500">Description</dt>
                  <dd class="mt-1 text-sm text-gray-900">{{ intervention.description }}</dd>
                </div>
                <div>
                  <dt class="text-sm font-medium text-gray-500">Technicien assigné</dt>
                  <dd class="mt-1 text-sm text-gray-900">{{ intervention.technicien_nom }}</dd>
                </div>
                <div v-if="intervention.priorite">
                  <dt class="text-sm font-medium text-gray-500">Priorité</dt>
                  <dd class="mt-1">
                    <span
                      :class="priorityClasses"
                      class="px-2 py-1 text-xs font-medium rounded-full"
                    >
                      {{ intervention.priorite }}
                    </span>
                  </dd>
                </div>
                <div v-if="intervention.lieu">
                  <dt class="text-sm font-medium text-gray-500">Lieu</dt>
                  <dd class="mt-1 text-sm text-gray-900">{{ intervention.lieu }}</dd>
                </div>
                <div v-if="intervention.date_prevue">
                  <dt class="text-sm font-medium text-gray-500">Date prévue</dt>
                  <dd class="mt-1 text-sm text-gray-900">{{ formatDate(intervention.date_prevue) }}</dd>
                </div>
                <div v-if="intervention.date_fin">
                  <dt class="text-sm font-medium text-gray-500">Date de fin</dt>
                  <dd class="mt-1 text-sm text-gray-900">{{ formatDate(intervention.date_fin) }}</dd>
                </div>
              </dl>
              </template>
            </Card>
            <!-- Fichiers -->
            <Card>
              <template #content>
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Fichiers</h2>
                <FileUpload :intervention-id="intervention.id" />
              </template>
            </Card>
          </div>
          <!-- Sidebar -->
          <div class="space-y-6">
            <Card>
              <template #content>
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Informations</h3>
              <dl class="space-y-3">
                <div>
                  <dt class="text-sm font-medium text-gray-500">ID</dt>
                  <dd class="text-sm text-gray-900">#{{ intervention.id }}</dd>
                </div>
                <div>
                  <dt class="text-sm font-medium text-gray-500">Statut</dt>
                  <dd>
                    <span :class="statusClasses" class="px-2 py-1 text-xs font-medium rounded-full">
                      {{ statusText }}
                    </span>
                  </dd>
                </div>
                <div>
                  <dt class="text-sm font-medium text-gray-500">Créée le</dt>
                  <dd class="text-sm text-gray-900">{{ formatDate(intervention.date_creation) }}</dd>
                </div>
                <div v-if="intervention.date_modification">
                  <dt class="text-sm font-medium text-gray-500">Modifiée le</dt>
                  <dd class="text-sm text-gray-900">{{ formatDate(intervention.date_modification) }}</dd>
                </div>
              </dl>
              </template>
            </Card>
          </div>
        </div>
      </div>
    </div>
    <!-- Modal d'édition -->
    <div
      v-if="showEditModal"
      class="fixed inset-0 z-50 overflow-y-auto"
      aria-labelledby="modal-title"
      role="dialog"
      aria-modal="true"
    >
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showEditModal = false"></div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
              Modifier l'intervention
            </h3>
            <InterventionForm
              :intervention="intervention"
              @submit="handleEdit"
              @cancel="showEditModal = false"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
definePageMeta({
  middleware: 'auth'
})
const route = useRoute()
const { currentIntervention: intervention, fetchIntervention, updateIntervention, loading } = useInterventions()
const showEditModal = ref(false)
const statusClasses = computed(() => {
  const status = intervention.value?.status
  return {
    1: 'bg-yellow-100 text-yellow-800',
    2: 'bg-green-100 text-green-800'
  }[status] || 'bg-gray-100 text-gray-800'
})
const statusText = computed(() => {
  const status = intervention.value?.status
  return {
    1: 'En cours',
    2: 'Terminée'
  }[status] || 'Inconnu'
})
const priorityClasses = computed(() => {
  const priorite = intervention.value?.priorite
  return {
    'basse': 'bg-gray-100 text-gray-800',
    'normale': 'bg-blue-100 text-blue-800',
    'haute': 'bg-orange-100 text-orange-800',
    'urgente': 'bg-red-100 text-red-800'
  }[priorite] || 'bg-gray-100 text-gray-800'
})
const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('fr-FR')
}
const completeIntervention = async () => {
  await updateIntervention({
    id: intervention.value.id,
    status: 2,
    date_fin: new Date().toISOString().split('T')[0]
  })
}
const handleEdit = async (updatedIntervention) => {
  await updateIntervention(updatedIntervention)
  showEditModal.value = false
}
onMounted(() => {
  const id = route.params.id
  fetchIntervention(id)
})
</script>