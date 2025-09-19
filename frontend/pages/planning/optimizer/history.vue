<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- En-tête de la page -->
    <div class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-4">
            <Button
              icon="pi pi-arrow-left"
              text
              rounded
              class="!text-gray-600 hover:!text-gray-800 dark:!text-gray-400 dark:hover:!text-gray-200"
              @click="$router.back()"
            />
            <div>
              <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 flex items-center">
                <i class="pi pi-chart-line mr-3 text-blue-600 dark:text-blue-400"></i>
                Historique des Optimisations
              </h1>
              <p class="text-gray-600 dark:text-gray-400 mt-1">
                Consulter l'historique complet des plannings optimisés
              </p>
            </div>
          </div>
          <div class="flex items-center space-x-3">
            <Button
              label="Nouvelle optimisation"
              icon="pi pi-plus"
              @click="$router.push('/planning/optimizer')"
              class="bg-blue-600 hover:bg-blue-700 text-white border-blue-600 hover:border-blue-700"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Contenu principal -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Filtres et statistiques -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <Card class="bg-white dark:bg-gray-800 shadow-sm">
          <template #content>
            <div class="flex items-center">
              <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900/20">
                <i class="pi pi-chart-bar text-blue-600 dark:text-blue-400 text-xl"></i>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Optimisations</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.total }}</p>
              </div>
            </div>
          </template>
        </Card>

        <Card class="bg-white dark:bg-gray-800 shadow-sm">
          <template #content>
            <div class="flex items-center">
              <div class="p-3 rounded-full bg-green-100 dark:bg-green-900/20">
                <i class="pi pi-check-circle text-green-600 dark:text-green-400 text-xl"></i>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Appliquées</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.applied }}</p>
              </div>
            </div>
          </template>
        </Card>

        <Card class="bg-white dark:bg-gray-800 shadow-sm">
          <template #content>
            <div class="flex items-center">
              <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900/20">
                <i class="pi pi-star text-purple-600 dark:text-purple-400 text-xl"></i>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Score Moyen</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.averageScore }}%</p>
              </div>
            </div>
          </template>
        </Card>

        <Card class="bg-white dark:bg-gray-800 shadow-sm">
          <template #content>
            <div class="flex items-center">
              <div class="p-3 rounded-full bg-orange-100 dark:bg-orange-900/20">
                <i class="pi pi-clock text-orange-600 dark:text-orange-400 text-xl"></i>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Économie Temps</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.timeSaved }}h</p>
              </div>
            </div>
          </template>
        </Card>
      </div>

      <!-- Filtres -->
      <Card class="bg-white dark:bg-gray-800 shadow-sm mb-6">
        <template #content>
          <div class="flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-[200px]">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Période
              </label>
              <DatePicker
                v-model="filters.dateRange"
                selectionMode="range"
                dateFormat="dd/mm/yy"
                placeholder="Sélectionner une période"
                class="w-full"
                :pt="{
                  input: { class: 'w-full p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100' }
                }"
              />
            </div>

            <div class="min-w-[150px]">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Statut
              </label>
              <Select
                v-model="filters.status"
                :options="statusOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Tous"
                class="w-full"
              />
            </div>

            <div class="flex items-end">
              <Button
                label="Réinitialiser"
                icon="pi pi-refresh"
                text
                @click="resetFilters"
              />
            </div>
          </div>
        </template>
      </Card>

      <!-- Tableau d'historique -->
      <Card class="bg-white dark:bg-gray-800 shadow-sm">
        <template #content>
          <DataTable
            :value="filteredPlannings"
            :loading="loading"
            :paginator="true"
            :rows="10"
            :rowsPerPageOptions="[5, 10, 20, 50]"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            currentPageReportTemplate="Affichage de {first} à {last} sur {totalRecords} plannings"
            class="w-full"
            dataKey="id"
            :pt="{
              table: { class: 'min-w-full' },
              thead: { class: 'bg-gray-50 dark:bg-gray-700' },
              tbody: { class: 'bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700' }
            }"
          >
            <Column field="nom" header="Nom du Planning">
              <template #body="{ data }">
                <div class="font-medium text-gray-900 dark:text-gray-100">
                  {{ data.nom }}
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                  ID: {{ data.id }}
                </div>
              </template>
            </Column>

            <Column field="periode" header="Période">
              <template #body="{ data }">
                <div class="text-sm">
                  <div class="text-gray-900 dark:text-gray-100">
                    {{ formatDate(data.date_debut) }}
                  </div>
                  <div class="text-gray-500 dark:text-gray-400">
                    au {{ formatDate(data.date_fin) }}
                  </div>
                </div>
              </template>
            </Column>

            <Column field="score_optimisation" header="Score">
              <template #body="{ data }">
                <div class="flex items-center">
                  <div class="flex-1">
                    <div class="flex items-center justify-between mb-1">
                      <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        {{ data.score_optimisation }}%
                      </span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                      <div
                        class="h-2 rounded-full transition-all duration-300"
                        :class="getScoreColor(data.score_optimisation)"
                        :style="{ width: data.score_optimisation + '%' }"
                      ></div>
                    </div>
                  </div>
                </div>
              </template>
            </Column>

            <Column field="nb_interventions" header="Interventions">
              <template #body="{ data }">
                <Badge
                  :value="data.nb_interventions"
                  severity="info"
                  class="text-sm"
                />
              </template>
            </Column>

            <Column field="profil_optimisation" header="Profil">
              <template #body="{ data }">
                <Badge
                  :value="data.profil_optimisation"
                  :severity="getProfilSeverity(data.profil_optimisation)"
                  class="text-xs"
                />
              </template>
            </Column>

            <Column field="statut" header="Statut">
              <template #body="{ data }">
                <Badge
                  :value="getStatutLabel(data.statut)"
                  :severity="getStatutSeverity(data.statut)"
                />
              </template>
            </Column>

            <Column field="date_creation" header="Créé le">
              <template #body="{ data }">
                <div class="text-sm text-gray-900 dark:text-gray-100">
                  {{ formatDateTime(data.date_creation) }}
                </div>
              </template>
            </Column>

            <Column header="Actions" :exportable="false">
              <template #body="{ data }">
                <div class="flex items-center space-x-2">
                  <Button
                    icon="pi pi-eye"
                    text
                    rounded
                    size="small"
                    class="!text-blue-600 hover:!text-blue-800 dark:!text-blue-400 dark:hover:!text-blue-200"
                    @click="viewPlanning(data)"
                    v-tooltip.top="'Voir détails'"
                  />
                  <Button
                    v-if="data.statut !== 'applique'"
                    icon="pi pi-check"
                    text
                    rounded
                    size="small"
                    class="!text-green-600 hover:!text-green-800 dark:!text-green-400 dark:hover:!text-green-200"
                    @click="applyPlanning(data)"
                    v-tooltip.top="'Appliquer ce planning'"
                  />
                  <Button
                    icon="pi pi-download"
                    text
                    rounded
                    size="small"
                    class="!text-purple-600 hover:!text-purple-800 dark:!text-purple-400 dark:hover:!text-purple-200"
                    @click="exportPlanning(data)"
                    v-tooltip.top="'Exporter PDF'"
                  />
                  <Button
                    icon="pi pi-copy"
                    text
                    rounded
                    size="small"
                    class="!text-orange-600 hover:!text-orange-800 dark:!text-orange-400 dark:hover:!text-orange-200"
                    @click="duplicatePlanning(data)"
                    v-tooltip.top="'Dupliquer'"
                  />
                </div>
              </template>
            </Column>
          </DataTable>
        </template>
      </Card>
    </div>

    <!-- Dialog de détails -->
    <Dialog
      v-model:visible="showDetailsDialog"
      modal
      header="Détails du Planning"
      :style="{ width: '80vw', maxWidth: '1200px' }"
      class="p-fluid"
    >
      <div v-if="selectedPlanning" class="space-y-6">
        <!-- Informations générales -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
              Informations Générales
            </h3>
            <div class="space-y-3">
              <div>
                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Nom</label>
                <p class="text-gray-900 dark:text-gray-100">{{ selectedPlanning.nom }}</p>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Période</label>
                <p class="text-gray-900 dark:text-gray-100">
                  {{ formatDate(selectedPlanning.date_debut) }} - {{ formatDate(selectedPlanning.date_fin) }}
                </p>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Profil d'optimisation</label>
                <p class="text-gray-900 dark:text-gray-100">{{ selectedPlanning.profil_optimisation }}</p>
              </div>
            </div>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
              Résultats
            </h3>
            <div class="space-y-3">
              <div>
                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Score d'optimisation</label>
                <p class="text-gray-900 dark:text-gray-100">{{ selectedPlanning.score_optimisation }}%</p>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Interventions</label>
                <p class="text-gray-900 dark:text-gray-100">{{ selectedPlanning.nb_interventions }}</p>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Statut</label>
                <Badge
                  :value="getStatutLabel(selectedPlanning.statut)"
                  :severity="getStatutSeverity(selectedPlanning.statut)"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Statistiques détaillées -->
        <div v-if="selectedPlanning.statistiques">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
            Statistiques Détaillées
          </h3>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
              <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                {{ selectedPlanning.statistiques.temps_total }}h
              </div>
              <div class="text-sm text-gray-600 dark:text-gray-400">Temps Total</div>
            </div>
            <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
              <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                {{ selectedPlanning.statistiques.distance_totale }}km
              </div>
              <div class="text-sm text-gray-600 dark:text-gray-400">Distance</div>
            </div>
            <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
              <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                {{ selectedPlanning.statistiques.cout_estime }}€
              </div>
              <div class="text-sm text-gray-600 dark:text-gray-400">Coût Estimé</div>
            </div>
            <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
              <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                {{ selectedPlanning.statistiques.techniciens }}
              </div>
              <div class="text-sm text-gray-600 dark:text-gray-400">Techniciens</div>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end space-x-3">
          <Button
            label="Fermer"
            icon="pi pi-times"
            text
            @click="showDetailsDialog = false"
          />
          <Button
            v-if="selectedPlanning && selectedPlanning.statut !== 'applique'"
            label="Appliquer ce planning"
            icon="pi pi-check"
            @click="applyPlanning(selectedPlanning)"
          />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

// Métadonnées de la page
definePageMeta({
  middleware: 'auth',
  layout: 'default'
})

// État réactif
const loading = ref(false)
const plannings = ref([])
const showDetailsDialog = ref(false)
const selectedPlanning = ref(null)

// Filtres
const filters = ref({
  dateRange: null,
  status: null
})

const statusOptions = [
  { label: 'Tous', value: null },
  { label: 'Brouillon', value: 'brouillon' },
  { label: 'Appliqué', value: 'applique' },
  { label: 'Archivé', value: 'archive' }
]

// Statistiques
const stats = computed(() => {
  const total = plannings.value.length
  const applied = plannings.value.filter(p => p.statut === 'applique').length
  const avgScore = total > 0
    ? Math.round(plannings.value.reduce((sum, p) => sum + p.score_optimisation, 0) / total)
    : 0
  const timeSaved = plannings.value.reduce((sum, p) => {
    return sum + (p.statistiques?.temps_economise || 0)
  }, 0)

  return {
    total,
    applied,
    averageScore: avgScore,
    timeSaved: Math.round(timeSaved)
  }
})

// Plannings filtrés
const filteredPlannings = computed(() => {
  let filtered = plannings.value

  if (filters.value.status) {
    filtered = filtered.filter(p => p.statut === filters.value.status)
  }

  if (filters.value.dateRange && filters.value.dateRange.length === 2) {
    const [start, end] = filters.value.dateRange
    filtered = filtered.filter(p => {
      const creationDate = new Date(p.date_creation)
      return creationDate >= start && creationDate <= end
    })
  }

  return filtered.sort((a, b) => new Date(b.date_creation) - new Date(a.date_creation))
})

// Méthodes
const loadPlannings = async () => {
  loading.value = true
  try {
    const { $api } = useNuxtApp()
    const response = await $api('/planning_optimizer.php?action=plannings')
    if (response.success) {
      plannings.value = response.data || []
    }
  } catch (error) {
    console.error('Erreur lors du chargement des plannings:', error)
    useToast().add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de charger l\'historique des plannings',
      life: 5000
    })
  } finally {
    loading.value = false
  }
}

const viewPlanning = (planning) => {
  selectedPlanning.value = planning
  showDetailsDialog.value = true
}

const applyPlanning = async (planning) => {
  try {
    const { $api } = useNuxtApp()
    const response = await $api('/planning_optimizer.php?action=appliquer', {
      method: 'POST',
      body: { planning_id: planning.id }
    })

    if (response.success) {
      useToast().add({
        severity: 'success',
        summary: 'Succès',
        detail: 'Planning appliqué avec succès',
        life: 3000
      })

      // Mettre à jour le statut
      planning.statut = 'applique'
      showDetailsDialog.value = false
    }
  } catch (error) {
    console.error('Erreur lors de l\'application du planning:', error)
    useToast().add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible d\'appliquer le planning',
      life: 5000
    })
  }
}

const exportPlanning = (planning) => {
  useToast().add({
    severity: 'info',
    summary: 'Export PDF',
    detail: 'Fonctionnalité en développement',
    life: 3000
  })
}

const duplicatePlanning = async (planning) => {
  try {
    const { $api } = useNuxtApp()
    const response = await $api('/planning_optimizer.php?action=dupliquer', {
      method: 'POST',
      body: { planning_id: planning.id }
    })

    if (response.success) {
      useToast().add({
        severity: 'success',
        summary: 'Succès',
        detail: 'Planning dupliqué avec succès',
        life: 3000
      })
      await loadPlannings()
    }
  } catch (error) {
    console.error('Erreur lors de la duplication:', error)
    useToast().add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de dupliquer le planning',
      life: 5000
    })
  }
}

const resetFilters = () => {
  filters.value = {
    dateRange: null,
    status: null
  }
}

// Utilitaires de formatage
const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('fr-FR')
}

const formatDateTime = (datetime) => {
  if (!datetime) return ''
  return new Date(datetime).toLocaleString('fr-FR')
}

const getScoreColor = (score) => {
  if (score >= 80) return 'bg-green-500'
  if (score >= 60) return 'bg-yellow-500'
  return 'bg-red-500'
}

const getProfilSeverity = (profil) => {
  const map = {
    'equilibre': 'info',
    'temps': 'warning',
    'cout': 'success',
    'urgence': 'danger'
  }
  return map[profil] || 'secondary'
}

const getStatutSeverity = (statut) => {
  const map = {
    'brouillon': 'secondary',
    'applique': 'success',
    'archive': 'warning'
  }
  return map[statut] || 'secondary'
}

const getStatutLabel = (statut) => {
  const map = {
    'brouillon': 'Brouillon',
    'applique': 'Appliqué',
    'archive': 'Archivé'
  }
  return map[statut] || statut
}

// Lifecycle
onMounted(() => {
  // Charger des données de démonstration si pas de données
  plannings.value = [
    {
      id: 1,
      nom: 'Optimisation Semaine 42-2024',
      date_debut: '2024-10-14',
      date_fin: '2024-10-18',
      score_optimisation: 87,
      nb_interventions: 24,
      profil_optimisation: 'equilibre',
      statut: 'applique',
      date_creation: '2024-10-13T10:30:00',
      statistiques: {
        temps_total: 156,
        distance_totale: 485,
        cout_estime: 2340,
        techniciens: 6,
        temps_economise: 23
      }
    },
    {
      id: 2,
      nom: 'Planning Urgent Secteur Nord',
      date_debut: '2024-10-15',
      date_fin: '2024-10-16',
      score_optimisation: 92,
      nb_interventions: 12,
      profil_optimisation: 'urgence',
      statut: 'applique',
      date_creation: '2024-10-14T14:15:00',
      statistiques: {
        temps_total: 78,
        distance_totale: 156,
        cout_estime: 890,
        techniciens: 3,
        temps_economise: 18
      }
    },
    {
      id: 3,
      nom: 'Optimisation Coût Semaine 43',
      date_debut: '2024-10-21',
      date_fin: '2024-10-25',
      score_optimisation: 75,
      nb_interventions: 31,
      profil_optimisation: 'cout',
      statut: 'brouillon',
      date_creation: '2024-10-20T09:45:00',
      statistiques: {
        temps_total: 198,
        distance_totale: 423,
        cout_estime: 1890,
        techniciens: 7,
        temps_economise: 31
      }
    }
  ]

  // Décommenter pour charger les vraies données
  // loadPlannings()
})
</script>

<style scoped>
/* Styles personnalisés pour les composants PrimeVue */
:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background: rgb(249, 250, 251) !important;
}

.dark :deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background: rgb(55, 65, 81) !important;
}

:deep(.p-paginator) {
  background: transparent !important;
  border: none !important;
  padding: 1rem 0 !important;
}

:deep(.p-dialog .p-dialog-header) {
  background: white !important;
  border-bottom: 1px solid rgb(229, 231, 235) !important;
}

.dark :deep(.p-dialog .p-dialog-header) {
  background: rgb(31, 41, 55) !important;
  border-bottom: 1px solid rgb(75, 85, 99) !important;
}

:deep(.p-dialog .p-dialog-content) {
  background: white !important;
}

.dark :deep(.p-dialog .p-dialog-content) {
  background: rgb(31, 41, 55) !important;
}
</style>