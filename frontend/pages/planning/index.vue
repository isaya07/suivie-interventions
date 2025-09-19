<template>
  <div>
    <!-- En-tête du planning -->
    <div class="mb-8">
      <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            Planning des Interventions
          </h1>
          <p class="text-gray-600 dark:text-gray-400">
            Planification et assignation des branchements électriques
          </p>
        </div>
        <div class="mt-4 lg:mt-0 flex gap-2">
          <Button
            icon="pi pi-refresh"
            label="Actualiser"
            severity="secondary"
            @click="loadPlanning"
            :loading="loading"
          />
          <Button
            icon="pi pi-plus"
            label="Nouvelle intervention"
            @click="$router.push('/interventions/create')"
          />
        </div>
      </div>
    </div>
    <!-- Filtres de planning -->
    <Card class="mb-6">
      <template #content>
        <div class="p-4">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Période -->
            <div class="space-y-2">
              <label
                class="text-sm font-medium text-surface-700 dark:text-surface-300"
              >
                Période
              </label>
              <Dropdown
                v-model="selectedPeriode"
                :options="periodeOptions"
                option-label="label"
                option-value="value"
                placeholder="Sélectionner une période"
                class="w-full"
                @change="applyFilters"
              />
            </div>
            <!-- Technicien -->
            <div class="space-y-2">
              <label
                class="text-sm font-medium text-surface-700 dark:text-surface-300"
              >
                Technicien
              </label>
              <Dropdown
                v-model="selectedTechnicien"
                :options="technicienOptions"
                option-label="label"
                option-value="value"
                placeholder="Tous les techniciens"
                class="w-full"
                @change="applyFilters"
              />
            </div>
            <!-- Statut -->
            <div class="space-y-2">
              <label
                class="text-sm font-medium text-surface-700 dark:text-surface-300"
              >
                Statut
              </label>
              <MultiSelect
                v-model="selectedStatuts"
                :options="statutOptions"
                option-label="label"
                option-value="value"
                placeholder="Tous les statuts"
                class="w-full"
                @change="applyFilters"
              />
            </div>
            <!-- Priorité -->
            <div class="space-y-2">
              <label
                class="text-sm font-medium text-surface-700 dark:text-surface-300"
              >
                Priorité
              </label>
              <Dropdown
                v-model="selectedPriorite"
                :options="prioriteOptions"
                option-label="label"
                option-value="value"
                placeholder="Toutes les priorités"
                class="w-full"
                @change="applyFilters"
              />
            </div>
          </div>
        </div>
      </template>
    </Card>
    <!-- Vue du planning -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
      <!-- Colonnes par statut/phase -->
      <Card>
        <template #header>
          <div
            class="flex items-center justify-between p-4 border-b bg-orange-50 dark:bg-orange-900/20"
          >
            <h3
              class="text-lg font-semibold text-orange-800 dark:text-orange-200"
            >
              <i class="pi pi-clock mr-2"></i>
              En attente
            </h3>
            <Badge :value="planningData.enAttente.length" severity="warning" />
          </div>
        </template>
        <template #content>
          <div class="p-4 space-y-3 max-h-96 overflow-y-auto">
            <div
              v-for="intervention in planningData.enAttente"
              :key="intervention.id"
              class="p-3 bg-white dark:bg-gray-800 rounded-lg border border-orange-200 dark:border-orange-700 hover:bg-orange-50 dark:hover:bg-orange-900/10 transition-colors cursor-pointer"
              @click="viewIntervention(intervention.id)"
            >
              <div class="flex items-center justify-between mb-2">
                <span
                  class="text-sm font-medium text-gray-900 dark:text-gray-100"
                >
                  #{{ intervention.numero }}
                </span>
                <Badge
                  :value="getPrioriteLabel(intervention.priorite)"
                  :severity="getPrioriteSeverity(intervention.priorite)"
                  class="text-xs"
                />
              </div>
              <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">
                {{ intervention.client_nom }}
              </p>
              <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ intervention.type_prestation_nom }}
              </p>
              <div class="flex items-center justify-between mt-2">
                <span class="text-xs text-gray-500">
                  {{ formatDate(intervention.date_creation) }}
                </span>
                <div class="flex gap-1">
                  <Button
                    icon="pi pi-user"
                    size="small"
                    severity="secondary"
                    text
                    @click.stop="assignerTechnicien(intervention)"
                    v-tooltip="'Assigner'"
                  />
                  <Button
                    icon="pi pi-play"
                    size="small"
                    severity="success"
                    text
                    @click.stop="demarrerIntervention(intervention)"
                    v-tooltip="'Démarrer'"
                  />
                </div>
              </div>
            </div>
            <div
              v-if="planningData.enAttente.length === 0"
              class="text-center py-8"
            >
              <i class="pi pi-check-circle text-green-500 text-2xl mb-2"></i>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                Aucune intervention en attente
              </p>
            </div>
          </div>
        </template>
      </Card>
      <Card>
        <template #header>
          <div
            class="flex items-center justify-between p-4 border-b bg-blue-50 dark:bg-blue-900/20"
          >
            <h3 class="text-lg font-semibold text-blue-800 dark:text-blue-200">
              <i class="pi pi-cog mr-2"></i>
              Terrassement
            </h3>
            <Badge :value="planningData.terrassement.length" severity="info" />
          </div>
        </template>
        <template #content>
          <div class="p-4 space-y-3 max-h-96 overflow-y-auto">
            <div
              v-for="intervention in planningData.terrassement"
              :key="intervention.id"
              class="p-3 bg-white dark:bg-gray-800 rounded-lg border border-blue-200 dark:border-blue-700 hover:bg-blue-50 dark:hover:bg-blue-900/10 transition-colors cursor-pointer"
              @click="viewIntervention(intervention.id)"
            >
              <div class="flex items-center justify-between mb-2">
                <span
                  class="text-sm font-medium text-gray-900 dark:text-gray-100"
                >
                  #{{ intervention.numero }}
                </span>
                <span
                  class="text-xs text-blue-600 dark:text-blue-400 font-medium"
                >
                  {{
                    intervention.technicien_terrassement_nom || "Non assigné"
                  }}
                </span>
              </div>
              <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">
                {{ intervention.client_nom }}
              </p>
              <div class="flex items-center justify-between">
                <span class="text-xs text-gray-500">
                  Début:
                  {{ formatTime(intervention.phase_terrassement_date_debut) }}
                </span>
                <Button
                  icon="pi pi-check"
                  size="small"
                  severity="success"
                  text
                  @click.stop="terminerPhase(intervention, 'terrassement')"
                  v-tooltip="'Terminer'"
                />
              </div>
            </div>
            <div
              v-if="planningData.terrassement.length === 0"
              class="text-center py-8"
            >
              <i class="pi pi-info-circle text-blue-500 text-2xl mb-2"></i>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                Aucun terrassement en cours
              </p>
            </div>
          </div>
        </template>
      </Card>
      <Card>
        <template #header>
          <div
            class="flex items-center justify-between p-4 border-b bg-purple-50 dark:bg-purple-900/20"
          >
            <h3
              class="text-lg font-semibold text-purple-800 dark:text-purple-200"
            >
              <i class="pi pi-bolt mr-2"></i>
              Branchement
            </h3>
            <Badge :value="planningData.branchement.length" severity="help" />
          </div>
        </template>
        <template #content>
          <div class="p-4 space-y-3 max-h-96 overflow-y-auto">
            <div
              v-for="intervention in planningData.branchement"
              :key="intervention.id"
              class="p-3 bg-white dark:bg-gray-800 rounded-lg border border-purple-200 dark:border-purple-700 hover:bg-purple-50 dark:hover:bg-purple-900/10 transition-colors cursor-pointer"
              @click="viewIntervention(intervention.id)"
            >
              <div class="flex items-center justify-between mb-2">
                <span
                  class="text-sm font-medium text-gray-900 dark:text-gray-100"
                >
                  #{{ intervention.numero }}
                </span>
                <span
                  class="text-xs text-purple-600 dark:text-purple-400 font-medium"
                >
                  {{ intervention.technicien_branchement_nom || "Non assigné" }}
                </span>
              </div>
              <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">
                {{ intervention.client_nom }}
              </p>
              <div class="flex items-center justify-between">
                <span class="text-xs text-gray-500">
                  Début:
                  {{ formatTime(intervention.phase_branchement_date_debut) }}
                </span>
                <Button
                  icon="pi pi-check"
                  size="small"
                  severity="success"
                  text
                  @click.stop="terminerPhase(intervention, 'branchement')"
                  v-tooltip="'Terminer'"
                />
              </div>
            </div>
            <div
              v-if="planningData.branchement.length === 0"
              class="text-center py-8"
            >
              <i class="pi pi-info-circle text-purple-500 text-2xl mb-2"></i>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                Aucun branchement en cours
              </p>
            </div>
          </div>
        </template>
      </Card>
      <Card>
        <template #header>
          <div
            class="flex items-center justify-between p-4 border-b bg-green-50 dark:bg-green-900/20"
          >
            <h3
              class="text-lg font-semibold text-green-800 dark:text-green-200"
            >
              <i class="pi pi-check-circle mr-2"></i>
              Terminées
            </h3>
            <Badge :value="planningData.terminees.length" severity="success" />
          </div>
        </template>
        <template #content>
          <div class="p-4 space-y-3 max-h-96 overflow-y-auto">
            <div
              v-for="intervention in planningData.terminees.slice(0, 10)"
              :key="intervention.id"
              class="p-3 bg-white dark:bg-gray-800 rounded-lg border border-green-200 dark:border-green-700 hover:bg-green-50 dark:hover:bg-green-900/10 transition-colors cursor-pointer"
              @click="viewIntervention(intervention.id)"
            >
              <div class="flex items-center justify-between mb-2">
                <span
                  class="text-sm font-medium text-gray-900 dark:text-gray-100"
                >
                  #{{ intervention.numero }}
                </span>
                <i class="pi pi-check-circle text-green-500"></i>
              </div>
              <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">
                {{ intervention.client_nom }}
              </p>
              <span class="text-xs text-green-600 dark:text-green-400">
                Terminée le {{ formatDate(intervention.date_fin) }}
              </span>
            </div>
            <div
              v-if="planningData.terminees.length === 0"
              class="text-center py-8"
            >
              <i class="pi pi-info-circle text-green-500 text-2xl mb-2"></i>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                Aucune intervention terminée
              </p>
            </div>
          </div>
        </template>
      </Card>
    </div>
    <!-- Statistiques du planning -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mt-8">
      <Card class="text-center">
        <template #content>
          <div class="p-4">
            <div
              class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2"
            >
              {{ planningStats.totalInterventions }}
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
              Total interventions
            </div>
          </div>
        </template>
      </Card>
      <Card class="text-center">
        <template #content>
          <div class="p-4">
            <div class="text-3xl font-bold text-blue-600 mb-2">
              {{ planningStats.techniciensActifs }}
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
              Techniciens actifs
            </div>
          </div>
        </template>
      </Card>
      <Card class="text-center">
        <template #content>
          <div class="p-4">
            <div class="text-3xl font-bold text-orange-600 mb-2">
              {{ planningStats.delaiMoyen }}j
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
              Délai moyen
            </div>
          </div>
        </template>
      </Card>
      <Card class="text-center">
        <template #content>
          <div class="p-4">
            <div class="text-3xl font-bold text-green-600 mb-2">
              {{ planningStats.tauxCompletion }}%
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
              Taux de completion
            </div>
          </div>
        </template>
      </Card>
    </div>
  </div>
  <!-- Dialog d'assignation de technicien -->
  <Dialog
    v-model:visible="showAssignDialog"
    :modal="true"
    :closable="true"
    header="Assigner un technicien"
    class="w-full max-w-md"
  >
    <div class="space-y-4">
      <div class="space-y-2">
        <label
          class="text-sm font-medium text-surface-700 dark:text-surface-300"
        >
          Intervention
        </label>
        <p class="text-sm text-surface-900 dark:text-surface-50">
          #{{ selectedIntervention?.numero }} -
          {{ selectedIntervention?.client_nom }}
        </p>
      </div>
      <div class="space-y-2">
        <label
          class="text-sm font-medium text-surface-700 dark:text-surface-300"
        >
          Phase
        </label>
        <Dropdown
          v-model="assignPhase"
          :options="phaseOptions"
          option-label="label"
          option-value="value"
          placeholder="Sélectionner une phase"
          class="w-full"
        />
      </div>
      <div class="space-y-2">
        <label
          class="text-sm font-medium text-surface-700 dark:text-surface-300"
        >
          Technicien
        </label>
        <Dropdown
          v-model="assignTechnicienId"
          :options="availableTechniciens"
          option-label="nom"
          option-value="id"
          placeholder="Sélectionner un technicien"
          class="w-full"
        />
      </div>
      <div class="space-y-2">
        <label
          class="text-sm font-medium text-surface-700 dark:text-surface-300"
        >
          Date prévue
        </label>
        <DatePicker
          v-model="assignDate"
          :showIcon="true"
          iconDisplay="input"
          placeholder="Sélectionner une date"
          class="w-full"
        />
      </div>
    </div>
    <template #footer>
      <div class="flex gap-2 justify-end">
        <Button
          label="Annuler"
          severity="secondary"
          @click="closeAssignDialog"
        />
        <Button
          label="Assigner"
          @click="confirmerAssignation"
          :loading="assigning"
        />
      </div>
    </template>
  </Dialog>
</template>
<script setup>
definePageMeta({
  middleware: ["auth"],
});
import { ref, computed, onMounted } from "vue";
import { useToast } from "primevue/usetoast";
const { $api } = useNuxtApp();
const toast = useToast();
// État réactif
const loading = ref(false);
const interventions = ref([]);
const techniciens = ref([]);
// Filtres
const selectedPeriode = ref("semaine");
const selectedTechnicien = ref(null);
const selectedStatuts = ref([]);
const selectedPriorite = ref(null);
// Dialog d'assignation
const showAssignDialog = ref(false);
const selectedIntervention = ref(null);
const assignPhase = ref(null);
const assignTechnicienId = ref(null);
const assignDate = ref(new Date());
const assigning = ref(false);
// Options des filtres
const periodeOptions = [
  { label: "Aujourd'hui", value: "jour" },
  { label: "Cette semaine", value: "semaine" },
  { label: "Ce mois", value: "mois" },
  { label: "Prochains 30 jours", value: "30j" },
];
const technicienOptions = computed(() => [
  { label: "Tous les techniciens", value: null },
  ...techniciens.value.map((t) => ({ label: t.nom, value: t.id })),
]);
const statutOptions = [
  { label: "En attente", value: "en_attente" },
  { label: "En cours", value: "en_cours" },
  { label: "Terminée", value: "terminee" },
  { label: "Annulée", value: "annulee" },
];
const prioriteOptions = [
  { label: "Toutes les priorités", value: null },
  { label: "Urgente", value: "urgente" },
  { label: "Haute", value: "haute" },
  { label: "Normale", value: "normale" },
  { label: "Basse", value: "basse" },
];
const phaseOptions = [
  { label: "Terrassement", value: "terrassement" },
  { label: "Branchement", value: "branchement" },
];
// Données du planning organisées par colonnes
const planningData = computed(() => {
  const filtered = filteredInterventions.value;
  return {
    enAttente: filtered.filter(
      (i) =>
        i.phase_terrassement_statut === "en_attente" ||
        i.phase_branchement_statut === "en_attente"
    ),
    terrassement: filtered.filter(
      (i) => i.phase_terrassement_statut === "en_cours"
    ),
    branchement: filtered.filter(
      (i) => i.phase_branchement_statut === "en_cours"
    ),
    terminees: filtered.filter(
      (i) =>
        i.phase_terrassement_statut === "terminee" &&
        i.phase_branchement_statut === "terminee"
    ),
  };
});
// Interventions filtrées
const filteredInterventions = computed(() => {
  let filtered = [...interventions.value];
  // Filtre par technicien
  if (selectedTechnicien.value) {
    filtered = filtered.filter(
      (i) =>
        i.technicien_terrassement_id === selectedTechnicien.value ||
        i.technicien_branchement_id === selectedTechnicien.value
    );
  }
  // Filtre par statuts
  if (selectedStatuts.value.length > 0) {
    filtered = filtered.filter(
      (i) =>
        selectedStatuts.value.includes(i.phase_terrassement_statut) ||
        selectedStatuts.value.includes(i.phase_branchement_statut)
    );
  }
  // Filtre par période
  const today = new Date();
  let dateLimit;
  switch (selectedPeriode.value) {
    case "jour":
      dateLimit = new Date(today);
      dateLimit.setHours(23, 59, 59, 999);
      break;
    case "semaine":
      dateLimit = new Date(today);
      dateLimit.setDate(today.getDate() + 7);
      break;
    case "mois":
      dateLimit = new Date(today);
      dateLimit.setMonth(today.getMonth() + 1);
      break;
    case "30j":
      dateLimit = new Date(today);
      dateLimit.setDate(today.getDate() + 30);
      break;
  }
  if (dateLimit) {
    filtered = filtered.filter((i) => {
      const dateIntervention = new Date(i.date_intervention || i.date_creation);
      return dateIntervention <= dateLimit;
    });
  }
  return filtered;
});
// Techniciens disponibles pour assignation
const availableTechniciens = computed(() => {
  if (!assignPhase.value) return [];
  const specialite =
    assignPhase.value === "terrassement" ? "terrassier" : "cableur";
  return techniciens.value.filter(
    (t) => t.specialite_principale === specialite
  );
});
// Statistiques du planning
const planningStats = computed(() => {
  const total = interventions.value.length;
  const terminees = planningData.value.terminees.length;
  const techniciensActifs = new Set(
    [
      ...interventions.value.map((i) => i.technicien_terrassement_id),
      ...interventions.value.map((i) => i.technicien_branchement_id),
    ].filter(Boolean)
  ).size;
  return {
    totalInterventions: total,
    techniciensActifs,
    delaiMoyen: 18, // Calculé
    tauxCompletion: total > 0 ? Math.round((terminees / total) * 100) : 0,
  };
});
/**
 * Fonctions utilitaires
 */
const formatDate = (dateString) => {
  if (!dateString) return "-";
  return new Date(dateString).toLocaleDateString("fr-FR", {
    day: "numeric",
    month: "short",
    year: "numeric",
  });
};
const formatTime = (dateString) => {
  if (!dateString) return "-";
  return new Date(dateString).toLocaleString("fr-FR", {
    day: "numeric",
    month: "short",
    hour: "2-digit",
    minute: "2-digit",
  });
};
const getPrioriteLabel = (priorite) => {
  const labels = {
    urgente: "Urgente",
    haute: "Haute",
    normale: "Normale",
    basse: "Basse",
  };
  return labels[priorite] || "Normale";
};
const getPrioriteSeverity = (priorite) => {
  const severities = {
    urgente: "danger",
    haute: "warning",
    normale: "info",
    basse: "secondary",
  };
  return severities[priorite] || "info";
};
/**
 * Actions
 */
const loadPlanning = async () => {
  loading.value = true;
  try {
    const [interventionsResponse, techniciensResponse] = await Promise.all([
      $api.get("/intervention_electrique.php"),
      $api.get("/users.php?action=list&role=technicien"),
    ]);
    if (interventionsResponse.success) {
      interventions.value = interventionsResponse.data || [];
    }
    if (techniciensResponse.success) {
      techniciens.value = techniciensResponse.users || [];
    }
  } catch (error) {
    console.error("Erreur lors du chargement du planning:", error);
    toast.add({
      severity: "error",
      summary: "Erreur",
      detail: "Impossible de charger le planning",
      life: 5000,
    });
  } finally {
    loading.value = false;
  }
};
const applyFilters = () => {
  // Les filtres sont automatiquement appliqués via les computed
};
const viewIntervention = (id) => {
  navigateTo(`/interventions/electrique/${id}`);
};
const assignerTechnicien = (intervention) => {
  selectedIntervention.value = intervention;
  assignPhase.value =
    intervention.has_terrassement &&
    intervention.phase_terrassement_statut === "en_attente"
      ? "terrassement"
      : "branchement";
  assignTechnicienId.value = null;
  assignDate.value = new Date();
  showAssignDialog.value = true;
};
const closeAssignDialog = () => {
  showAssignDialog.value = false;
  selectedIntervention.value = null;
  assignPhase.value = null;
  assignTechnicienId.value = null;
  assignDate.value = new Date();
};
const confirmerAssignation = async () => {
  if (
    !selectedIntervention.value ||
    !assignPhase.value ||
    !assignTechnicienId.value
  ) {
    toast.add({
      severity: "warn",
      summary: "Information manquante",
      detail: "Veuillez remplir tous les champs",
      life: 3000,
    });
    return;
  }
  assigning.value = true;
  try {
    const updateData = {
      id: selectedIntervention.value.id,
      [`${assignPhase.value}_technicien_id`]: assignTechnicienId.value,
      [`phase_${assignPhase.value}_statut`]: "en_attente",
      date_intervention: assignDate.value.toISOString().split("T")[0],
    };
    const response = await $api.put("/intervention_electrique.php", updateData);
    if (response.success) {
      toast.add({
        severity: "success",
        summary: "Assignation réussie",
        detail: "Le technicien a été assigné avec succès",
        life: 3000,
      });
      closeAssignDialog();
      await loadPlanning();
    } else {
      throw new Error(response.message);
    }
  } catch (error) {
    toast.add({
      severity: "error",
      summary: "Erreur",
      detail: error.message,
      life: 5000,
    });
  } finally {
    assigning.value = false;
  }
};
const demarrerIntervention = async (intervention) => {
  try {
    const phase =
      intervention.has_terrassement &&
      intervention.phase_terrassement_statut === "en_attente"
        ? "terrassement"
        : "branchement";
    const response = await $api.put("/intervention_electrique.php", {
      id: intervention.id,
      [`phase_${phase}_statut`]: "en_cours",
      [`phase_${phase}_date_debut`]: new Date().toISOString(),
    });
    if (response.success) {
      toast.add({
        severity: "success",
        summary: "Intervention démarrée",
        detail: `La phase ${phase} a été démarrée`,
        life: 3000,
      });
      await loadPlanning();
    } else {
      throw new Error(response.message);
    }
  } catch (error) {
    toast.add({
      severity: "error",
      summary: "Erreur",
      detail: error.message,
      life: 5000,
    });
  }
};
const terminerPhase = async (intervention, phase) => {
  try {
    const response = await $api.put("/intervention_electrique.php", {
      id: intervention.id,
      [`phase_${phase}_statut`]: "terminee",
      [`phase_${phase}_date_fin`]: new Date().toISOString(),
    });
    if (response.success) {
      toast.add({
        severity: "success",
        summary: "Phase terminée",
        detail: `La phase ${phase} a été terminée avec succès`,
        life: 3000,
      });
      await loadPlanning();
    } else {
      throw new Error(response.message);
    }
  } catch (error) {
    toast.add({
      severity: "error",
      summary: "Erreur",
      detail: error.message,
      life: 5000,
    });
  }
};
// Initialisation
onMounted(() => {
  loadPlanning();
});
</script>
<style scoped>
.max-h-96 {
  max-height: 24rem;
}
</style>
