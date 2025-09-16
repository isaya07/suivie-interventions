<template>
  <Card class="mb-6">
    <template #content>
      <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Filtres</h3>
    
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Technicien
        </label>
        <Select
          v-model="localFilters.technicien_id"
          :options="techniciens"
          optionLabel="nom_complet"
          optionValue="id"
          placeholder="Tous les techniciens"
          class="w-full"
          @change="applyFilters"
        />
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
          <DatePicker
            v-model="localFilters.dateFrom"
            dateFormat="dd/mm/yy"
            class="w-full"
            @date-select="applyFilters"
          />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">
            À
          </label>
          <DatePicker
            v-model="localFilters.dateTo"
            dateFormat="dd/mm/yy"
            class="w-full"
            @date-select="applyFilters"
          />
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
        <Button
          @click="clearAllFilters"
          label="Réinitialiser"
          severity="secondary"
          class="w-full"
        />
      </div>
    </div>
    
    <div v-if="hasActiveFilters" class="mt-4 flex flex-wrap gap-2">
      <span class="text-sm text-gray-600">Filtres actifs:</span>
      <span
        v-if="localFilters.technicien_id"
        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
      >
        {{ getTechnicienName(localFilters.technicien_id) }}
        <Button
          @click="clearFilter('technicien_id')"
          icon="pi pi-times"
          text
          rounded
          size="small"
          class="ml-1.5 h-4 w-4"
        />
      </span>
      <span
        v-if="localFilters.dateFrom"
        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
      >
        Depuis: {{ formatDate(localFilters.dateFrom) }}
        <Button
          @click="clearFilter('dateFrom')"
          icon="pi pi-times"
          text
          rounded
          size="small"
          class="ml-1.5 h-4 w-4"
        />
      </span>
      <span
        v-if="localFilters.dateTo"
        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
      >
        Jusqu'au: {{ formatDate(localFilters.dateTo) }}
        <Button
          @click="clearFilter('dateTo')"
          icon="pi pi-times"
          text
          rounded
          size="small"
          class="ml-1.5 h-4 w-4"
        />
      </span>
      <span
        v-if="localFilters.status && localFilters.status.length > 0"
        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
      >
        {{ getStatusText(localFilters.status) }}
        <Button
          @click="clearFilter('status')"
          icon="pi pi-times"
          text
          rounded
          size="small"
          class="ml-1.5 h-4 w-4"
        />
      </span>
    </div>
    </template>
  </Card>
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
