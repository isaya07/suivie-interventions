export const useInterventions = () => {
  const interventionsStore = useInterventionsStore()
  
  return {
    // State
    interventions: computed(() => interventionsStore.interventions),
    currentIntervention: computed(() => interventionsStore.currentIntervention),
    loading: computed(() => interventionsStore.loading),
    filters: computed(() => interventionsStore.filters),
    
    // Getters
    filteredInterventions: computed(() => interventionsStore.filteredInterventions),
    interventionsByStatus: computed(() => interventionsStore.interventionsByStatus),
    
    // Actions
    fetchInterventions: interventionsStore.fetchInterventions,
    fetchIntervention: interventionsStore.fetchIntervention,
    createIntervention: interventionsStore.createIntervention,
    updateIntervention: interventionsStore.updateIntervention,
    setFilters: interventionsStore.setFilters,
    clearFilters: interventionsStore.clearFilters
  }
}