<template>
  <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6 hover:shadow-lg transition-shadow">
    <!-- En-tête avec titre et statut -->
    <div class="flex justify-between items-start mb-4">
      <div class="flex-1">
        <h3 class="text-lg font-semibold text-gray-900 mb-1">
          {{ intervention.titre }}
        </h3>
        <p class="text-gray-600 text-sm line-clamp-2">
          {{ intervention.description }}
        </p>
      </div>

      <span
        :class="getStatusClasses(intervention.statut)"
        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ml-4"
      >
        {{ intervention.statut }}
      </span>
    </div>

    <!-- Informations principales -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
      <div class="flex items-center text-sm text-gray-600">
        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z"></path>
        </svg>
        <span class="font-medium">Client:</span>
        <span class="ml-1">{{ intervention.client_nom || 'Non assigné' }}</span>
      </div>

      <div class="flex items-center text-sm text-gray-600">
        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
        </svg>
        <span class="font-medium">Technicien:</span>
        <span class="ml-1">{{ intervention.technicien_nom || 'Non assigné' }}</span>
      </div>
    </div>

    <!-- Dates et priorité -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4 text-sm">
      <div class="flex items-center text-gray-600">
        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
        <span>{{ formatDate(intervention.date_creation) }}</span>
      </div>

      <div v-if="intervention.date_intervention" class="flex items-center text-gray-600">
        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <span>Prévue le {{ formatDate(intervention.date_intervention) }}</span>
      </div>

      <div class="flex items-center">
        <span
          :class="getPriorityClasses(intervention.priorite)"
          class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium"
        >
          {{ intervention.priorite }}
        </span>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex justify-between items-center pt-4 border-t border-gray-200">
      <div class="flex space-x-2">
        <NuxtLink
          :to="`/interventions/${intervention.id}`"
          class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors"
        >
          <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
          </svg>
          Voir
        </NuxtLink>

        <NuxtLink
          :to="`/interventions/${intervention.id}/edit`"
          class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors"
        >
          <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
          </svg>
          Modifier
        </NuxtLink>
      </div>

      <button
        v-if="canComplete"
        @click="$emit('complete', intervention.id)"
        class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md text-sm font-medium text-white bg-green-600 hover:bg-green-700 transition-colors"
      >
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        Terminer
      </button>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  intervention: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['complete'])

const canComplete = computed(() => {
  return props.intervention.statut === 'En cours'
})

const getStatusClasses = (statut) => {
  const classes = {
    'En cours': 'bg-yellow-100 text-yellow-800',
    'Terminée': 'bg-green-100 text-green-800',
    'En attente': 'bg-blue-100 text-blue-800',
    'En pause': 'bg-gray-100 text-gray-800',
    'Urgente': 'bg-red-100 text-red-800',
    'Annulée': 'bg-red-100 text-red-800'
  }
  return classes[statut] || 'bg-gray-100 text-gray-800'
}

const getPriorityClasses = (priorite) => {
  const classes = {
    'Haute': 'bg-red-100 text-red-800',
    'Urgente': 'bg-red-200 text-red-900',
    'Normale': 'bg-blue-100 text-blue-800',
    'Basse': 'bg-gray-100 text-gray-800'
  }
  return classes[priorite] || 'bg-gray-100 text-gray-800'
}

const formatDate = (dateString) => {
  if (!dateString) return 'Non définie'
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>