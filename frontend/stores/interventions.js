import { defineStore } from "pinia";

export const useInterventionsStore = defineStore("interventions", () => {
  const interventions = ref([]);
  const currentIntervention = ref(null);
  const loading = ref(false);
  const filters = ref({
    technicien_id: null,
    dateFrom: null,
    dateTo: null,
    status: [],
  });

  const { $api } = useNuxtApp();

  // Getters
  const filteredInterventions = computed(() => {
    return interventions.value.filter((intervention) => {
      if (
        filters.value.technicien_id &&
        intervention.technicien_id !== filters.value.technicien_id
      )
        return false;
      // Filtrage par plage de dates
      if (filters.value.dateFrom || filters.value.dateTo) {
        const interventionDate = new Date(intervention.date_intervention || intervention.date);
        if (filters.value.dateFrom && interventionDate < new Date(filters.value.dateFrom))
          return false;
        if (filters.value.dateTo && interventionDate > new Date(filters.value.dateTo))
          return false;
      }
      if (filters.value.status.length > 0 && !filters.value.status.includes(intervention.statut))
        return false;
      return true;
    });
  });

  const interventionsByStatus = computed(() => {
    return {
      enAttente: interventions.value.filter((i) => i.statut === 'En attente').length,
      enCours: interventions.value.filter((i) => i.statut === 'En cours').length,
      enPause: interventions.value.filter((i) => i.statut === 'En pause').length,
      terminees: interventions.value.filter((i) => i.statut === 'Terminée').length,
      annulees: interventions.value.filter((i) => i.statut === 'Annulée').length,
      total: interventions.value.length,
    };
  });

  // Actions
  const fetchInterventions = async (params = {}) => {
    loading.value = true;
    try {
      let url = "/intervention.php";
      const searchParams = new URLSearchParams();

      Object.entries(params).forEach(([key, value]) => {
        if (value) searchParams.append(key, value);
      });

      if (searchParams.toString()) {
        url += `?${searchParams.toString()}`;
      }

      const response = await $api(url);

      if (response.records) {
        // Mapper les champs du backend vers le format attendu par le frontend
        interventions.value = response.records.map(intervention => ({
          ...intervention,
          status: intervention.statut, // Mapper statut vers status
          technicien: intervention.technicien_nom || intervention.technicien
        }));
      } else {
        // Peut-être que les données sont directement dans response ?
        if (Array.isArray(response)) {
          interventions.value = response;
        } else if (response.data) {
          interventions.value = response.data;
        }
      }
    } catch (error) {
      console.error("❌ Erreur lors de la récupération des interventions:", error);
    } finally {
      loading.value = false;
    }
  };

  const fetchIntervention = async (id) => {
    loading.value = true;
    try {
      const response = await $api(`/intervention.php?id=${id}`);
      if (response.id) {
        currentIntervention.value = response;
      }
    } catch (error) {
      console.error("Erreur lors de la récupération de l'intervention:", error);
    } finally {
      loading.value = false;
    }
  };

  const createIntervention = async (interventionData) => {
    try {
      const response = await $api("/intervention.php", {
        method: "POST",
        body: interventionData,
      });

      if (response.success) {
        interventions.value.push(response.data);
        return { success: true, data: response.data };
      }

      return { success: false, message: response.message };
    } catch (error) {
      return { success: false, message: "Erreur lors de la création" };
    }
  };

  const updateIntervention = async (interventionData) => {
    try {
      const response = await $api("/intervention.php", {
        method: "PUT",
        body: interventionData,
      });

      if (response.success) {
        const index = interventions.value.findIndex(
          (i) => i.id === interventionData.id
        );
        if (index !== -1) {
          interventions.value[index] = response.data;
        }
        if (currentIntervention.value?.id === interventionData.id) {
          currentIntervention.value = response.data;
        }
        return { success: true, data: response.data };
      }

      return { success: false, message: response.message };
    } catch (error) {
      return { success: false, message: "Erreur lors de la mise à jour" };
    }
  };

  const setFilters = (newFilters) => {
    filters.value = { ...filters.value, ...newFilters };
  };

  const clearFilters = () => {
    filters.value = {
      technicien_id: null,
      dateFrom: null,
      dateTo: null,
      status: [],
    };
  };

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
    clearFilters,
  };
});
