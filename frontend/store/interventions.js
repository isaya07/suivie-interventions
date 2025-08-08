export const useInterventionsStore = defineStore('interventions', () => {
  const interventions = ref([])
  const currentIntervention = ref(null)
  const loading = ref(false)
  const filters = ref({
    technicien_id: null,
    date: null,
    status: null
  })
  
  const { $api } = useNuxtApp()
  
  // Getters
  const filteredInterventions = computed(() => {
    return interventions.value.filter(intervention => {
      if (filters.value.technicien_id && intervention.technicien_id !== filters.value.technicien_id) return false
      if (filters.value.date && intervention.date !== filters.value.date) return false
      if (filters.value.status && intervention.status !== filters.value.status) return false
      return true
    })
  })
  
  const interventionsByStatus = computed(() => {
    return {
      enCours: interventions.value.filter(i => i.status === 1).length,
      terminees: interventions.value.filter(i => i.status === 2).length,
      total: interventions.value.length
    }
  })
  
  // Actions
  const fetchInterventions = async (params = {}) => {
    loading.value = true
    try {
      let url = '/interventions.php'
      const searchParams = new URLSearchParams()
      
      Object.entries(params).forEach(([key, value]) => {
        if (value) searchParams.append(key, value)
      })
      
      if (searchParams.toString()) {
        url += `?${searchParams.toString()}`
      }
      
      const response = await $api(url)
      
      if (response.success) {
        interventions.value = response.data
      }
    } catch (error) {
      console.error('Erreur lors de la récupération des interventions:', error)
    } finally {
      loading.value = false
    }
  }
  
  const fetchIntervention = async (id) => {
    loading.value = true
    try {
      const response = await $api(`/interventions.php?id=${id}`)
      if (response.success) {
        currentIntervention.value = response.data
      }
    } catch (error) {
      console.error('Erreur lors de la récupération de l\'intervention:', error)
    } finally {
      loading.value = false
    }
  }
  
  const createIntervention = async (interventionData) => {
    try {
      const response = await $api('/interventions.php', {
        method: 'POST',
        body: interventionData
      })
      
      if (response.success) {
        interventions.value.push(response.data)
        return { success: true, data: response.data }
      }
      
      return { success: false, message: response.message }
    } catch (error) {
      return { success: false, message: 'Erreur lors de la création' }
    }
  }
  
  const updateIntervention = async (interventionData) => {
    try {
      const response = await $api('/interventions.php', {
        method: 'PUT',
        body: interventionData
      })
      
      if (response.success) {
        const index = interventions.value.findIndex(i => i.id === interventionData.id)
        if (index !== -1) {
          interventions.value[index] = response.data
        }
        if (currentIntervention.value?.id === interventionData.id) {
          currentIntervention.value = response.data
        }
        return { success: true, data: response.data }
      }
      
      return { success: false, message: response.message }
    } catch (error) {
      return { success: false, message: 'Erreur lors de la mise à jour' }
    }
  }
  
  const setFilters = (newFilters) => {
    filters.value = { ...filters.value, ...newFilters }
  }
  
  const clearFilters = () => {
    filters.value = {
      technicien_id: null,
      date: null,
      status: null
    }
  }
  
  return {
    // State
    interventions: readonly(interventions),
    currentIntervention: readonly(currentIntervention),
    loading: readonly(loading),
    filters: readonly(filters),
    
    // Getters
    filteredInterventions,
    interventionsByStatus,
    
    // Actions
    fetchInterventions,
    fetchIntervention,
    createIntervention,
    updateIntervention,
    setFilters,
    clearFilters
  }
})