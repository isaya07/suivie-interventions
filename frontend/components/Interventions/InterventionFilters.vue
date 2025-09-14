<template>
  <div class="bg-white rounded-lg shadow p-6 mb-6">
    <h3 class="text-lg font-medium text-gray-900 mb-4">Filtres</h3>
    
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Technicien
        </label>
        <select
          v-model="localFilters.technicien_id"
          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          @change="applyFilters"
        >
          <option value="">Tous les techniciens</option>
          <option
            v-for="technicien in techniciens"
            :key="technicien.id"
            :value="technicien.id"
          >
            {{ technicien.nom_complet }}
          </option>
        </select>
      </div>
      
      <div class="space-y-3">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            📅 Période d'intervention
          </label>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">
            De
          </label>
          <input
            v-model="localFilters.dateFrom"
            type="date"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            @change="applyFilters"
          >
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">
            À
          </label>
          <input
            v-model="localFilters.dateTo"
            type="date"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            @change="applyFilters"
          >
        </div>
      </div>
      
      <div>
        <MultiSelect
          v-model="localFilters.status"
          :options="statusOptions"
          label="Statut"
          placeholder="Sélectionner les statuts..."
          @update:modelValue="applyFilters"
        />
      </div>
      
      <div class="flex items-end">
        <button
          @click="clearAllFilters"
          class="w-full bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg transition-colors"
        >
          Réinitialiser
        </button>
      </div>
    </div>
    
    <div v-if="hasActiveFilters" class="mt-4 flex flex-wrap gap-2">
      <span class="text-sm text-gray-600">Filtres actifs:</span>
      <span
        v-if="localFilters.technicien_id"
        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
      >
        {{ getTechnicienName(localFilters.technicien_id) }}
        <button
          @click="clearFilter('technicien_id')"
          class="ml-1.5 inline-flex items-center justify-center h-4 w-4 rounded-full text-blue-400 hover:bg-blue-200 hover:text-blue-600"
        >
          ×
        </button>
      </span>
      <span
        v-if="localFilters.dateFrom"
        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
      >
        Depuis: {{ formatDate(localFilters.dateFrom) }}
        <button
          @click="clearFilter('dateFrom')"
          class="ml-1.5 inline-flex items-center justify-center h-4 w-4 rounded-full text-blue-400 hover:bg-blue-200 hover:text-blue-600"
        >
          ×
        </button>
      </span>
      <span
        v-if="localFilters.dateTo"
        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
      >
        Jusqu'au: {{ formatDate(localFilters.dateTo) }}
        <button
          @click="clearFilter('dateTo')"
          class="ml-1.5 inline-flex items-center justify-center h-4 w-4 rounded-full text-blue-400 hover:bg-blue-200 hover:text-blue-600"
        >
          ×
        </button>
      </span>
      <span
        v-if="localFilters.status && localFilters.status.length > 0"
        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
      >
        {{ getStatusText(localFilters.status) }}
        <button
          @click="clearFilter('status')"
          class="ml-1.5 inline-flex items-center justify-center h-4 w-4 rounded-full text-blue-400 hover:bg-blue-200 hover:text-blue-600"
        >
          ×
        </button>
      </span>
    </div>
  </div>
</template>

<script setup>
const { filters, setFilters, clearFilters } = useInterventions()
const { techniciens, fetchTechniciens } = useUsers()

const localFilters = ref({
  ...filters.value,
  dateFrom: filters.value.dateFrom || null,
  dateTo: filters.value.dateTo || null,
  status: filters.value.status || []
})

// Options pour le statut
const statusOptions = [
  'En attente',
  'En cours',
  'En pause',
  'Terminée',
  'Annulée'
]

const hasActiveFilters = computed(() => {
  return localFilters.value.technicien_id ||
         localFilters.value.dateFrom ||
         localFilters.value.dateTo ||
         (localFilters.value.status && localFilters.value.status.length > 0)
})

const applyFilters = () => {
  // Validation : s'assurer que dateFrom <= dateTo
  if (localFilters.value.dateFrom && localFilters.value.dateTo) {
    const dateFrom = new Date(localFilters.value.dateFrom)
    const dateTo = new Date(localFilters.value.dateTo)

    if (dateFrom > dateTo) {
      // Échanger les dates si dateFrom > dateTo
      const temp = localFilters.value.dateFrom
      localFilters.value.dateFrom = localFilters.value.dateTo
      localFilters.value.dateTo = temp
    }
  }

  setFilters(localFilters.value)
}

const clearAllFilters = () => {
  localFilters.value = {
    technicien_id: null,
    dateFrom: null,
    dateTo: null,
    status: []
  }
  clearFilters()
}

const clearFilter = (filterKey) => {
  if (filterKey === 'status') {
    localFilters.value[filterKey] = []
  } else {
    localFilters.value[filterKey] = null
  }
  applyFilters()
}

const getTechnicienName = (id) => {
  const technicien = techniciens.value.find(t => t.id == id)
  return technicien?.nom_complet || 'Inconnu'
}

const getStatusText = (statusArray) => {
  if (!statusArray || statusArray.length === 0) return 'Aucun'

  // Les statuts sont maintenant directement les noms complets
  return statusArray.join(', ')
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('fr-FR')
}

onMounted(() => {
  fetchTechniciens()
})
</script>
