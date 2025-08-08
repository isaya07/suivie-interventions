<template>
  <div class="card hover:shadow-md transition-shadow duration-200">
    <div class="flex items-start justify-between">
      <div class="flex-1">
        <div class="flex items-center justify-between mb-2">
          <h3 class="text-lg font-medium text-gray-900">
            {{ intervention.titre }}
          </h3>
          <span
            :class="statusClasses"
            class="px-2 py-1 text-xs font-medium rounded-full"
          >
            {{ statusText }}
          </span>
        </div>
        
        <p class="text-sm text-gray-600 mb-3">
          {{ intervention.description }}
        </p>
        
        <div class="flex items-center text-sm text-gray-500 space-x-4">
          <div class="flex items-center">
            <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            {{ intervention.technicien_nom }}
          </div>
          
          <div class="flex items-center">
            <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a1 1 0 011-1h6a1 1 0 011 1v4h3a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2V9a2 2 0 012-2h3z" />
            </svg>
            {{ formatDate(intervention.date_creation) }}
          </div>
          
          <div v-if="intervention.date_fin" class="flex items-center">
            <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Terminée le {{ formatDate(intervention.date_fin) }}
          </div>
        </div>
      </div>
      
      <div class="ml-4 flex flex-col space-y-2">
        <NuxtLink
          :to="`/interventions/${intervention.id}`"
          class="text-blue-600 hover:text-blue-800 text-sm font-medium"
        >
          Voir détails
        </NuxtLink>
        
        <button
          v-if="intervention.status === 1"
          @click="$emit('complete', intervention.id)"
          class="text-green-600 hover:text-green-800 text-sm font-medium"
        >
          Marquer terminée
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  intervention: {
    type: Object,
    required: true
  }
})

defineEmits(['complete'])

const statusClasses = computed(() => {
  const status = props.intervention.status
  return {
    1: 'bg-yellow-100 text-yellow-800',
    2: 'bg-green-100 text-green-800'
  }[status] || 'bg-gray-100 text-gray-800'
})

const statusText = computed(() => {
  const status = props.intervention.status
  return {
    1: 'En cours',
    2: 'Terminée'
  }[status] || 'Inconnu'
})

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('fr-FR')
}
</script>
