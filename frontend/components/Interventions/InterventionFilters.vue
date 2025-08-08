<template>
  <div class="card mb-6">
    <h3 class="text-lg font-medium text-gray-900 mb-4">Filtres</h3>
    
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Technicien
        </label>
        <select
          v-model="localFilters.technicien_id"
          class="form-input"
          @change="applyFilters"
        >
          <option value="">Tous les techniciens</option>
          <option
            v-for="technicien in techniciens"
            :key="technicien.id"
            :value="technicien.id"
          >
            {{ technicien.nom }}
          </option>
        </select>
      </div>
      
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Date
        </label>
        <input
          v-model="localFilters.date"
          type="date"
          class="form-input"
          @change="applyFilters"
        >
      </div>
      
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Statut
        </label>
        <select
          v-model="localFilters.status"
          class="form-input"
          @change="applyFilters"
        >
          <option value="">Tous les statuts</option>
          <option value="1">En cours</option>
          <option value="2">Terminées</option>
        </select>
      </div>
      
      <div class="flex items-end">
        <button
          @click="clearAllFilters"
          class="btn-secondary w-full"
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
        v-if="localFilters.date"
        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
      >
        {{ formatDate(localFilters.date) }}
        <button
          @click="clearFilter('date')"
          class="ml-1.5 inline-flex items-center justify-center h-4 w-4 rounded-full text-blue-400 hover:bg-blue-200 hover:text-blue-600"
        >
          ×
        </button>
      </span>
      <span
        v-if="localFilters.status"
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

const localFilters = ref({ ...filters.value })

const hasActiveFilters = computed(() => {
  return localFilters.value.technicien_id || 
         localFilters.value.date || 
         localFilters.value.status
})

const applyFilters = () => {
  setFilters(localFilters.value)
}

const clearAllFilters = () => {
  localFilters.value = {
    technicien_id: null,
    date: null,
    status: null
  }
  clearFilters()
}

const clearFilter = (filterKey) => {
  localFilters.value[filterKey] = null
  applyFilters()
}

const getTechnicienName = (id) => {
  const technicien = techniciens.value.find(t => t.id == id)
  return technicien?.nom || 'Inconnu'
}

const getStatusText = (status) => {
  return {
    '1': 'En cours',
    '2': 'Terminées'
  }[status] || 'Inconnu'
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('fr-FR')
}

onMounted(() => {
  fetchTechniciens()
})
</script>
