<template>
  <div>
    <AppHeader />

    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
      <div class="px-4 py-6 sm:px-0">
        <!-- En-tête -->
        <div class="flex justify-between items-center mb-6">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Interventions</h1>
            <p class="text-gray-600 dark:text-gray-400">Gérez toutes vos interventions techniques</p>
          </div>

          <Button
            label="Nouvelle intervention"
            icon="pi pi-plus"
            @click="$router.push('/interventions/create')"
            class="p-button-primary"
          />
        </div>

        <!-- Filtres rapides -->
        <Card class="mb-6">
          <template #content>
            <div class="flex flex-wrap items-end gap-4">
              <div class="flex-1 min-w-64">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Recherche
                </label>
                <InputText
                  v-model="searchTerm"
                  placeholder="Titre, description, client..."
                  class="w-full"
                  icon="pi pi-search"
                />
              </div>

              <div class="min-w-48">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Statut
                </label>
                <MultiSelect
                  v-model="statusFilter"
                  :options="statusOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Tous les statuts"
                  class="w-full"
                  display="chip"
                />
              </div>

              <div class="min-w-48">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Priorité
                </label>
                <Select
                  v-model="priorityFilter"
                  :options="priorityOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Toutes priorités"
                  class="w-full"
                />
              </div>

              <div class="min-w-48">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Technicien
                </label>
                <Select
                  v-model="technicienFilter"
                  :options="techniciens"
                  optionLabel="nom_complet"
                  optionValue="id"
                  placeholder="Tous techniciens"
                  class="w-full"
                />
              </div>
            </div>
          </template>
        </Card>

        <!-- Liste des interventions -->
        <Card>
          <template #content>
            <div v-if="loading" class="flex justify-center py-8">
              <ProgressSpinner style="width: 50px; height: 50px" strokeWidth="4" />
            </div>

            <div v-else>
              <DataTable
                :value="filteredInterventionsComputed"
                :paginator="true"
                :rows="10"
                :rowsPerPageOptions="[5, 10, 20, 50]"
                :totalRecords="filteredInterventionsComputed.length"
                paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                currentPageReportTemplate="Affichage de {first} à {last} sur {totalRecords} interventions"
                class="p-datatable-sm"
                sortField="date_creation"
                :sortOrder="-1"
                :globalFilterFields="['titre', 'description', 'client_nom', 'technicien_nom']"
              >
                <Column field="numero" header="N°" :sortable="true" style="width: 80px">
                  <template #body="{ data }">
                    <div class="text-sm font-mono text-gray-600">
                      #{{ data.numero || data.id }}
                    </div>
                  </template>
                </Column>

                <Column field="titre" header="Intervention" :sortable="true">
                  <template #body="{ data }">
                    <div>
                      <div class="text-sm font-medium text-gray-900">{{ data.titre }}</div>
                      <div class="text-sm text-gray-500 max-w-xs truncate">{{ data.description }}</div>
                    </div>
                  </template>
                </Column>

                <Column header="Client" :sortable="true" sortField="client_nom">
                  <template #body="{ data }">
                    <div class="flex items-center">
                      <Avatar
                        :label="data.client_nom?.[0] || 'C'"
                        class="mr-2"
                        size="small"
                        style="background-color: #10b981; color: white"
                        shape="circle"
                      />
                      <div class="text-sm text-gray-900">
                        {{ data.client_nom || 'Client non assigné' }}
                      </div>
                    </div>
                  </template>
                </Column>

                <Column header="Technicien" :sortable="true" sortField="technicien_nom">
                  <template #body="{ data }">
                    <div class="flex items-center">
                      <Avatar
                        :label="data.technicien_nom?.[0] || 'T'"
                        class="mr-2"
                        size="small"
                        style="background-color: #3b82f6; color: white"
                        shape="circle"
                      />
                      <div class="text-sm text-gray-900">
                        {{ data.technicien_nom || 'Non assigné' }}
                      </div>
                    </div>
                  </template>
                </Column>

                <Column header="Statut" :sortable="true" sortField="statut">
                  <template #body="{ data }">
                    <Badge
                      :value="data.statut"
                      :severity="getStatusSeverity(data.statut)"
                      size="small"
                    />
                  </template>
                </Column>

                <Column header="Priorité" :sortable="true" sortField="priorite">
                  <template #body="{ data }">
                    <Badge
                      :value="data.priorite"
                      :severity="getPrioritySeverity(data.priorite)"
                      size="small"
                    />
                  </template>
                </Column>

                <Column header="Dates" :sortable="true" sortField="date_creation">
                  <template #body="{ data }">
                    <div class="text-sm">
                      <div class="text-gray-900">
                        <i class="pi pi-calendar mr-1"></i>
                        {{ formatDate(data.date_creation) }}
                      </div>
                      <div v-if="data.date_intervention" class="text-gray-500">
                        <i class="pi pi-clock mr-1"></i>
                        {{ formatDate(data.date_intervention) }}
                      </div>
                    </div>
                  </template>
                </Column>

                <Column header="Actions" :sortable="false" style="width: 150px">
                  <template #body="{ data }">
                    <div class="flex space-x-1">
                      <Button
                        icon="pi pi-eye"
                        outlined
                        size="small"
                        @click="$router.push(`/interventions/${data.id}`)"
                        v-tooltip.top="'Voir les détails'"
                      />
                      <Button
                        icon="pi pi-pencil"
                        outlined
                        size="small"
                        @click="$router.push(`/interventions/${data.id}/edit`)"
                        v-tooltip.top="'Modifier'"
                      />
                      <Button
                        v-if="canComplete(data)"
                        icon="pi pi-check"
                        outlined
                        size="small"
                        severity="success"
                        @click="completeInterventionConfirm(data)"
                        v-tooltip.top="'Terminer'"
                      />
                    </div>
                  </template>
                </Column>

                <template #empty>
                  <div class="text-center py-8">
                    <i class="pi pi-wrench text-gray-400 text-4xl mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune intervention trouvée</h3>
                    <p class="text-sm text-gray-500 mb-4">Aucune intervention ne correspond à vos critères.</p>
                    <Button
                      label="Nouvelle intervention"
                      icon="pi pi-plus"
                      @click="$router.push('/interventions/create')"
                    />
                  </div>
                </template>
              </DataTable>
            </div>
          </template>
        </Card>
      </div>
    </main>

    <!-- Confirmation de finalisation -->
    <ConfirmDialog />
  </div>
</template>

<script setup>
// Configuration de la page - nécessite une authentification
definePageMeta({
  middleware: 'auth'
})

// Import des services Nuxt et composables
const { $confirm, $toast } = useNuxtApp();
const { filteredInterventions, fetchInterventions, updateIntervention, loading } = useInterventions();
const { techniciens, fetchTechniciens } = useUsers();

// État local pour les filtres de recherche et tri
const searchTerm = ref('');        // Terme de recherche textuelle
const statusFilter = ref([]);      // Filtres de statut (multiple selection)
const priorityFilter = ref('');    // Filtre de priorité (single selection)
const technicienFilter = ref('');  // Filtre par technicien assigné

// Configuration des options pour les filtres dropdown
const statusOptions = [
  { label: 'En attente', value: 'En attente' },
  { label: 'En cours', value: 'En cours' },
  { label: 'En pause', value: 'En pause' },
  { label: 'Terminée', value: 'Terminée' },
  { label: 'Annulée', value: 'Annulée' }
];

const priorityOptions = [
  { label: 'Basse', value: 'basse' },
  { label: 'Normale', value: 'normale' },
  { label: 'Haute', value: 'haute' },
  { label: 'Urgente', value: 'urgente' }
];

/**
 * Computed pour filtrer les interventions selon les critères sélectionnés
 * Applique les filtres de recherche, statut, priorité et technicien de manière cumulative
 */
const filteredInterventionsComputed = computed(() => {
  let filtered = filteredInterventions.value;

  // Filtrage par terme de recherche (titre, description, client, technicien)
  if (searchTerm.value) {
    const term = searchTerm.value.toLowerCase();
    filtered = filtered.filter(
      (intervention) =>
        intervention.titre?.toLowerCase().includes(term) ||
        intervention.description?.toLowerCase().includes(term) ||
        intervention.client_nom?.toLowerCase().includes(term) ||
        intervention.technicien_nom?.toLowerCase().includes(term)
    );
  }

  // Filtrage par statut (sélection multiple)
  if (statusFilter.value && statusFilter.value.length > 0) {
    filtered = filtered.filter(intervention =>
      statusFilter.value.includes(intervention.statut)
    );
  }

  // Filtrage par priorité (sélection unique)
  if (priorityFilter.value) {
    filtered = filtered.filter(intervention =>
      intervention.priorite === priorityFilter.value
    );
  }

  // Filtrage par technicien assigné
  if (technicienFilter.value) {
    filtered = filtered.filter(intervention =>
      intervention.technicien_id == technicienFilter.value
    );
  }

  return filtered;
});

/**
 * Détermine la couleur/severité du badge de statut pour PrimeVue
 * @param {string} statut - Statut de l'intervention
 * @returns {string} - Nom de la severité PrimeVue
 */
const getStatusSeverity = (statut) => {
  return {
    'En attente': 'info',
    'En cours': 'warning',
    'En pause': 'secondary',
    'Terminée': 'success',
    'Annulée': 'danger',
    'Urgente': 'danger'
  }[statut] || 'secondary';
};

/**
 * Détermine la couleur/severité du badge de priorité pour PrimeVue
 * @param {string} priorite - Niveau de priorité
 * @returns {string} - Nom de la severité PrimeVue
 */
const getPrioritySeverity = (priorite) => {
  return {
    'basse': 'secondary',
    'normale': 'info',
    'haute': 'warning',
    'urgente': 'danger'
  }[priorite] || 'secondary';
};

/**
 * Formate une date ISO en format français localisé
 * @param {string} dateString - Date au format ISO
 * @returns {string} - Date formatée ou message par défaut
 */
const formatDate = (dateString) => {
  if (!dateString) return 'Non définie';
  const date = new Date(dateString);
  return date.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  });
};

/**
 * Détermine si une intervention peut être marquée comme terminée
 * @param {Object} intervention - Objet intervention
 * @returns {boolean} - True si l'intervention peut être terminée
 */
const canComplete = (intervention) => {
  return intervention.statut === 'En cours';
};

const completeInterventionConfirm = (intervention) => {
  $confirm.require({
    message: `Êtes-vous sûr de vouloir terminer l'intervention "${intervention.titre}" ?`,
    header: 'Finaliser l\'intervention',
    icon: 'pi pi-check-circle',
    accept: async () => {
      const result = await handleComplete(intervention.id);
      if (result.success) {
        $toast.add({
          severity: 'success',
          summary: 'Succès',
          detail: 'Intervention terminée avec succès',
          life: 3000
        });
      } else {
        $toast.add({
          severity: 'error',
          summary: 'Erreur',
          detail: result.message || 'Erreur lors de la finalisation',
          life: 3000
        });
      }
    }
  });
};

const handleComplete = async (interventionId) => {
  try {
    return await updateIntervention({
      id: interventionId,
      statut: 'Terminée',
      date_fin: new Date().toISOString().split('T')[0]
    });
  } catch (error) {
    console.error('Erreur lors de la finalisation:', error);
    return { success: false, message: 'Erreur lors de la finalisation' };
  }
};

// Override filteredInterventions to use our computed version
const filteredInterventionsToUse = filteredInterventionsComputed;

onMounted(() => {
  fetchInterventions();
  fetchTechniciens();
});
</script>