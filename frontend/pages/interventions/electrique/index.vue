<!--
  Page de liste des branchements électriques
  Affiche tous les branchements électriques avec filtrage,
  tri et actions rapides. Interface optimisée pour la gestion
  des phases et du suivi du temps.
-->
<template>
  <div>
    <!-- En-tête de page -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
      <div>
        <h1 class="text-3xl font-bold text-surface-900 dark:text-surface-50 mb-2">
          Branchements Électriques
        </h1>
        <p class="text-surface-600 dark:text-surface-400">
          Gestion des branchements avec phases terrassement et raccordement
        </p>
      </div>
      <div class="flex gap-3 mt-4 sm:mt-0">
        <Button
          label="Nouveau branchement"
          icon="pi pi-plus"
          @click="createIntervention"
        />
        <Button
          label="Tableau de bord"
          icon="pi pi-chart-pie"
          severity="secondary"
          @click="$router.push('/interventions/electrique/dashboard')"
        />
      </div>
    </div>
    <!-- Filtres et recherche -->
    <Card class="mb-6">
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
          <!-- Recherche -->
          <div class="space-y-2 lg:col-span-2">
            <label class="text-sm font-medium text-surface-700 dark:text-surface-300">
              Recherche
            </label>
            <InputText
              v-model="searchTerm"
              placeholder="Numéro, client, technicien..."
              class="w-full"
              @input="applyFilters"
            />
          </div>
          <!-- Statut -->
          <div class="space-y-2">
            <label class="text-sm font-medium text-surface-700 dark:text-surface-300">
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
          <!-- Type réglementaire Enedis -->
          <div class="space-y-2">
            <label class="text-sm font-medium text-surface-700 dark:text-surface-300">
              Type réglementaire
            </label>
            <MultiSelect
              v-model="selectedTypesReglementaires"
              :options="typeReglementaireOptions"
              option-label="label"
              option-value="value"
              placeholder="Type 1/Type 2"
              class="w-full"
              @change="applyFilters"
            />
          </div>
          <!-- Mode de pose -->
          <div class="space-y-2">
            <label class="text-sm font-medium text-surface-700 dark:text-surface-300">
              Mode de pose
            </label>
            <MultiSelect
              v-model="selectedModesPose"
              :options="modePoseOptions"
              option-label="label"
              option-value="value"
              placeholder="Tous les modes"
              class="w-full"
              @change="applyFilters"
            />
          </div>
          <!-- Technicien -->
          <div class="space-y-2">
            <label class="text-sm font-medium text-surface-700 dark:text-surface-300">
              Technicien
            </label>
            <MultiSelect
              v-model="selectedTechniciens"
              :options="technicienOptions"
              option-label="label"
              option-value="value"
              placeholder="Tous les techniciens"
              class="w-full"
              @change="applyFilters"
            />
          </div>
        </div>
        <!-- Filtres avancés (repliables) -->
        <div class="mt-4 pt-4 border-t border-surface-200 dark:border-surface-700">
          <Button
            :label="showAdvancedFilters ? 'Masquer les filtres avancés' : 'Filtres avancés'"
            :icon="showAdvancedFilters ? 'pi pi-chevron-up' : 'pi pi-chevron-down'"
            severity="secondary"
            text
            size="small"
            @click="showAdvancedFilters = !showAdvancedFilters"
          />
          <div v-if="showAdvancedFilters" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
            <!-- Distance LR -->
            <div class="space-y-2">
              <label class="text-sm font-medium text-surface-700 dark:text-surface-300">
                Liaison Réseau (LR)
              </label>
              <div class="flex gap-2">
                <InputNumber
                  v-model="filtresAvances.lrMin"
                  placeholder="Min (m)"
                  mode="decimal"
                  :min="0"
                  :max="1000"
                  class="flex-1"
                  @input="applyFilters"
                />
                <InputNumber
                  v-model="filtresAvances.lrMax"
                  placeholder="Max (m)"
                  mode="decimal"
                  :min="0"
                  :max="1000"
                  class="flex-1"
                  @input="applyFilters"
                />
              </div>
            </div>
            <!-- Distance DI -->
            <div class="space-y-2">
              <label class="text-sm font-medium text-surface-700 dark:text-surface-300">
                Dérivation Individuelle (DI)
              </label>
              <div class="flex gap-2">
                <InputNumber
                  v-model="filtresAvances.diMin"
                  placeholder="Min (m)"
                  mode="decimal"
                  :min="0"
                  :max="100"
                  class="flex-1"
                  @input="applyFilters"
                />
                <InputNumber
                  v-model="filtresAvances.diMax"
                  placeholder="Max (m)"
                  mode="decimal"
                  :min="0"
                  :max="100"
                  class="flex-1"
                  @input="applyFilters"
                />
              </div>
            </div>
            <!-- Indicateurs de retard -->
            <div class="space-y-2">
              <label class="text-sm font-medium text-surface-700 dark:text-surface-300">
                Retards
              </label>
              <MultiSelect
                v-model="selectedRetards"
                :options="retardOptions"
                option-label="label"
                option-value="value"
                placeholder="Tous"
                class="w-full"
                @change="applyFilters"
              />
            </div>
            <!-- Période de création -->
            <div class="space-y-2">
              <label class="text-sm font-medium text-surface-700 dark:text-surface-300">
                Période de création
              </label>
              <Dropdown
                v-model="selectedPeriode"
                :options="periodeOptions"
                option-label="label"
                option-value="value"
                placeholder="Toutes les périodes"
                class="w-full"
                @change="applyFilters"
              />
            </div>
          </div>
        </div>
        <!-- Actions rapides -->
        <div class="flex flex-wrap gap-2 mt-4 pt-4 border-t border-surface-200 dark:border-surface-700">
          <Button
            :label="refreshing ? 'Actualisation...' : 'Actualiser'"
            :icon="refreshing ? 'pi pi-spin pi-spinner' : 'pi pi-refresh'"
            severity="secondary"
            size="small"
            :loading="refreshing"
            @click="refreshData"
          />
          <Button
            label="Auto-actualisation"
            :icon="autoRefresh ? 'pi pi-pause' : 'pi pi-play'"
            :severity="autoRefresh ? 'info' : 'secondary'"
            size="small"
            @click="toggleAutoRefresh"
          />
          <span v-if="lastRefresh" class="text-xs text-surface-600 dark:text-surface-400 flex items-center">
            <i class="pi pi-clock mr-1"></i>
            Dernière MAJ: {{ formatTime(lastRefresh) }}
          </span>
          <Button
            label="Exporter CSV"
            icon="pi pi-download"
            severity="secondary"
            size="small"
            @click="exportToCsv"
          />
          <Button
            label="Réinitialiser filtres"
            icon="pi pi-filter-slash"
            severity="secondary"
            size="small"
            @click="resetFilters"
          />
        </div>
      </template>
    </Card>
    <!-- Indicateur de chargement -->
    <div v-if="loading" class="flex justify-center items-center py-12">
      <ProgressSpinner />
    </div>
    <!-- Tableau des interventions -->
    <Card v-else>
      <template #content>
        <DataTable
          :value="filteredInterventions"
          :paginator="true"
          :rows="20"
          :rows-per-page-options="[10, 20, 50]"
          paginator-template="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
          current-page-report-template="{first} à {last} sur {totalRecords} branchements"
          :sort-field="'date_creation'"
          :sort-order="-1"
          responsive-layout="scroll"
          striped-rows
          class="w-full"
          :empty-message="emptyMessage"
        >
          <!-- Colonne Numéro -->
          <Column field="numero" header="N°" sortable class="min-w-[100px]">
            <template #body="{ data }">
              <div data-label="N°">
                <NuxtLink
                  :to="`/interventions/electrique/${data.id}`"
                  class="font-mono font-bold text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-200"
                >
                  #{{ data.numero }}
                </NuxtLink>
              </div>
            </template>
          </Column>
          <!-- Colonne Client -->
          <Column field="client_nom" header="Client" sortable class="min-w-[200px]">
            <template #body="{ data }">
              <div data-label="Client">
                <div class="font-medium text-surface-900 dark:text-surface-50">
                  {{ data.client_nom }}
                </div>
                <div class="text-sm text-surface-600 dark:text-surface-400">
                  {{ data.type_prestation_nom }}
                </div>
                <div class="flex items-center gap-1 mt-1">
                  <Badge
                    :value="getTypeReglementaireLabel(data?.type_reglementaire)"
                    :severity="getTypeReglementaireSeverity(data?.type_reglementaire)"
                    class="text-xs"
                  />
                  <i :class="getModePoseIcon(data?.mode_pose)" class="text-xs text-surface-400" v-tooltip="getModePoseLabel(data?.mode_pose)"></i>
                </div>
              </div>
            </template>
          </Column>
          <!-- Colonne Liaison Réseau (LR) -->
          <Column header="LR" sortable field="longueur_liaison_reseau" class="min-w-[80px]">
            <template #body="{ data }">
              <div class="text-center" data-label="LR">
                <span class="font-mono font-bold text-blue-600 dark:text-blue-400">
                  {{ data?.longueur_liaison_reseau || 0 }}m
                </span>
              </div>
            </template>
          </Column>
          <!-- Colonne Dérivation Individuelle (DI) -->
          <Column header="DI" sortable field="longueur_derivation_individuelle" class="min-w-[80px]">
            <template #body="{ data }">
              <div class="text-center" data-label="DI">
                <span class="font-mono font-bold" :class="getDIClass(data)">
                  {{ data?.longueur_derivation_individuelle || 0 }}m
                </span>
                <span v-if="data?.type_reglementaire === 'type_1' && (data?.longueur_derivation_individuelle || 0) > 30"
                      class="text-orange-500 text-xs block" v-tooltip="'DI > 30m : devrait être Type 2'">⚠️ Type</span>
              </div>
            </template>
          </Column>
          <!-- Colonne Distance Totale -->
          <Column header="Total" sortable field="distance_raccordement" class="min-w-[90px]">
            <template #body="{ data }">
              <div class="text-center">
                <span class="font-mono font-bold text-surface-900 dark:text-surface-50">
                  {{ data?.distance_raccordement || 0 }}m
                </span>
                <div v-if="hasDistanceInconsistency(data)" class="text-xs text-red-500" v-tooltip="'Incohérence: LR + DI ≠ Total'">
                  ⚠️ Calc.
                </div>
              </div>
            </template>
          </Column>
          <!-- Colonne Phases -->
          <Column header="Phases" class="min-w-[200px]">
            <template #body="{ data }">
              <div class="space-y-1">
                <!-- Phase Terrassement -->
                <div v-if="data?.has_terrassement" class="flex items-center gap-2">
                  <Badge
                    value="Terrassement"
                    :severity="getStatutSeverity(data?.phase_terrassement_statut)"
                    class="text-xs"
                  />
                  <span v-if="data?.technicien_terrassement_nom" class="text-xs text-surface-600 dark:text-surface-400">
                    {{ data.technicien_terrassement_nom }}
                  </span>
                </div>
                <!-- Phase Branchement -->
                <div class="flex items-center gap-2">
                  <Badge
                    value="Branchement"
                    :severity="getStatutSeverity(data?.phase_branchement_statut)"
                    class="text-xs"
                  />
                  <span v-if="data?.technicien_branchement_nom" class="text-xs text-surface-600 dark:text-surface-400">
                    {{ data.technicien_branchement_nom }}
                  </span>
                </div>
              </div>
            </template>
          </Column>
          <!-- Colonne Temps/Coût -->
          <Column header="Temps & Coût" class="min-w-[150px]">
            <template #body="{ data }">
              <div class="text-center">
                <div class="font-mono font-bold text-surface-900 dark:text-surface-50">
                  {{ formatDuration(data?.duree_totale_heures) }}
                </div>
                <div class="text-sm font-medium text-surface-900 dark:text-surface-50">
                  {{ formatCurrency(data?.cout_total_reel) }}
                </div>
                <div v-if="data?.ecart_pourcentage" class="text-xs" :class="getEcartClass(data.ecart_pourcentage)">
                  {{ data.ecart_pourcentage > 0 ? '+' : '' }}{{ data.ecart_pourcentage }}%
                </div>
              </div>
            </template>
          </Column>
          <!-- Colonne Délais avec DelayIndicator -->
          <Column header="Délais" class="min-w-[140px]">
            <template #body="{ data }">
              <DelayIndicator
                :delay-data="getDelayData(data)"
                mode="progress"
                :suggested-actions="getDelayActions(data)"
                @action="handleDelayAction"
              />
            </template>
          </Column>
          <!-- Colonne Statut global -->
          <Column header="Statut" class="min-w-[120px]">
            <template #body="{ data }">
              <div data-label="Statut">
                <Badge
                  :value="getStatutGlobal(data) || 'N/A'"
                  :severity="getStatutSeverity(getStatutGlobal(data)) || 'secondary'"
                  class="font-medium"
                />
              </div>
            </template>
          </Column>
          <!-- Colonne Date -->
          <Column field="date_creation" header="Créée le" sortable class="min-w-[150px]">
            <template #body="{ data }">
              <div class="text-sm">
                <div class="font-medium">{{ formatDate(data?.date_creation) }}</div>
                <div class="text-surface-600 dark:text-surface-400">
                  {{ formatTime(data?.date_creation) }}
                </div>
              </div>
            </template>
          </Column>
          <!-- Colonne Actions -->
          <Column header="Actions" class="min-w-[120px]">
            <template #body="{ data }">
              <div class="flex gap-1">
                <Button
                  icon="pi pi-eye"
                  severity="secondary"
                  size="small"
                  v-tooltip="'Voir détails'"
                  @click="viewIntervention(data?.id)"
                />
                <Button
                  v-if="canEdit(data)"
                  icon="pi pi-play"
                  severity="success"
                  size="small"
                  v-tooltip="'Démarrer session'"
                  @click="startSession(data)"
                />
                <Button
                  v-if="canComplete(data)"
                  icon="pi pi-check"
                  severity="success"
                  size="small"
                  v-tooltip="'Terminer'"
                  @click="completeIntervention(data.id)"
                />
              </div>
            </template>
          </Column>
          <!-- Template pour état vide -->
          <template #empty>
            <div class="text-center p-8">
              <i class="pi pi-bolt text-4xl text-surface-400 dark:text-surface-600 mb-4"></i>
              <h3 class="text-lg font-medium text-surface-700 dark:text-surface-300 mb-2">
                Aucun branchement électrique
              </h3>
              <p class="text-surface-600 dark:text-surface-400 mb-4">
                {{ emptyMessage }}
              </p>
              <Button
                label="Créer un branchement"
                icon="pi pi-plus"
                @click="createIntervention"
              />
            </div>
          </template>
        </DataTable>
      </template>
    </Card>
  </div>
</template>
<script setup>
/**
 * Page de liste des branchements électriques
 *
 * Affiche tous les branchements électriques avec filtrage avancé,
 * actions rapides et navigation vers les détails.
 */
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useToast } from 'primevue/usetoast'
// Configuration de la page
definePageMeta({
  title: 'Branchements Électriques',
  middleware: 'auth',
  layout: 'default'
})
const router = useRouter()
const { hasPermission } = useAuth()
const toast = useToast()
const { $api } = useNuxtApp()
// État réactif
const interventions = ref([])
const loading = ref(true)
const refreshing = ref(false)
const autoRefresh = ref(false)
const lastRefresh = ref(null)
const refreshInterval = ref(null)
const searchTerm = ref('')
const selectedStatuts = ref([])
const selectedTypes = ref([])
const selectedTechniciens = ref([])
// Nouveaux filtres Enedis
const selectedTypesReglementaires = ref([])
const selectedModesPose = ref([])
const selectedRetards = ref([])
const selectedPeriode = ref('')
const showAdvancedFilters = ref(false)
// Filtres avancés pour les distances
const filtresAvances = ref({
  lrMin: null,
  lrMax: null,
  diMin: null,
  diMax: null
})
// Options pour les filtres
const statutOptions = [
  { label: 'En attente', value: 'en_attente' },
  { label: 'En cours', value: 'en_cours' },
  { label: 'Terminée', value: 'terminee' },
  { label: 'Annulée', value: 'annulee' }
]
const typeOptions = [
  { label: 'Aérien Type 1', value: 'AERIEN_TYPE_1' },
  { label: 'Aérien Type 2', value: 'AERIEN_TYPE_2' },
  { label: 'Souterrain Type 1', value: 'SOUTERRAIN_TYPE_1' },
  { label: 'Souterrain Type 2', value: 'SOUTERRAIN_TYPE_2' },
  { label: 'Aérosouterrain Type 1', value: 'AEROSOUTERRAIN_TYPE_1' },
  { label: 'Aérosouterrain Type 2', value: 'AEROSOUTERRAIN_TYPE_2' },
  { label: 'Souterrain sur Boîte Type 1', value: 'SOUTERRAIN_BOITE_TYPE_1' },
  { label: 'Souterrain sur Boîte Type 2', value: 'SOUTERRAIN_BOITE_TYPE_2' },
  { label: 'DI Seule Type 1', value: 'DI_SEULE_TYPE_1' },
  { label: 'DI Seule Type 2', value: 'DI_SEULE_TYPE_2' }
]
// Nouveaux filtres Enedis
const typeReglementaireOptions = [
  { label: 'Type 1 (DI ≤ 30m)', value: 'type_1' },
  { label: 'Type 2 (DI > 30m)', value: 'type_2' }
]
const modePoseOptions = [
  { label: 'Aérien', value: 'aerien' },
  { label: 'Souterrain', value: 'souterrain' },
  { label: 'Aérosouterrain', value: 'aerosouterrain' },
  { label: 'Souterrain sur boîte', value: 'souterrain_boite' },
  { label: 'DI seule', value: 'di_seule' }
]
const retardOptions = [
  { label: 'En retard', value: 'retard' },
  { label: 'À risque', value: 'risque' },
  { label: 'Dans les temps', value: 'ok' }
]
const periodeOptions = [
  { label: 'Aujourd\'hui', value: 'today' },
  { label: 'Cette semaine', value: 'week' },
  { label: 'Ce mois', value: 'month' },
  { label: 'Ce trimestre', value: 'quarter' },
  { label: 'Cette année', value: 'year' }
]
// Options dynamiques pour les techniciens
const technicienOptions = computed(() => {
  const techniciens = new Set()
  interventions.value.forEach(intervention => {
    if (intervention.technicien_branchement_nom) {
      techniciens.add(intervention.technicien_branchement_nom)
    }
    if (intervention.technicien_terrassement_nom) {
      techniciens.add(intervention.technicien_terrassement_nom)
    }
  })
  return Array.from(techniciens).map(nom => ({ label: nom, value: nom }))
})
// Interventions filtrées
const filteredInterventions = computed(() => {
  let filtered = [...interventions.value]
  // Filtrage par recherche
  if (searchTerm.value) {
    const term = searchTerm.value.toLowerCase()
    filtered = filtered.filter(intervention =>
      intervention.numero?.toString().includes(term) ||
      intervention.client_nom?.toLowerCase().includes(term) ||
      intervention.technicien_branchement_nom?.toLowerCase().includes(term) ||
      intervention.technicien_terrassement_nom?.toLowerCase().includes(term)
    )
  }
  // Filtrage par statut
  if (selectedStatuts.value.length > 0) {
    filtered = filtered.filter(intervention => {
      const statutGlobal = getStatutGlobal(intervention)
      return selectedStatuts.value.includes(statutGlobal.toLowerCase().replace(' ', '_'))
    })
  }
  // Filtrage par type de prestation
  if (selectedTypes.value.length > 0) {
    filtered = filtered.filter(intervention =>
      selectedTypes.value.includes(intervention.type_prestation_code)
    )
  }
  // Filtrage par type réglementaire
  if (selectedTypesReglementaires.value.length > 0) {
    filtered = filtered.filter(intervention =>
      selectedTypesReglementaires.value.includes(intervention.type_reglementaire)
    )
  }
  // Filtrage par mode de pose
  if (selectedModesPose.value.length > 0) {
    filtered = filtered.filter(intervention =>
      selectedModesPose.value.includes(intervention.mode_pose)
    )
  }
  // Filtrage par retards
  if (selectedRetards.value.length > 0) {
    filtered = filtered.filter(intervention => {
      const delaiStatus = getDelaiStatus(intervention).status
      return selectedRetards.value.includes(delaiStatus)
    })
  }
  // Filtrage par période
  if (selectedPeriode.value) {
    const now = new Date()
    const creationDate = new Date(intervention.date_creation)
    filtered = filtered.filter(intervention => {
      const creationDate = new Date(intervention.date_creation)
      switch (selectedPeriode.value) {
        case 'today':
          return creationDate.toDateString() === now.toDateString()
        case 'week':
          const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000)
          return creationDate >= weekAgo
        case 'month':
          return creationDate.getMonth() === now.getMonth() && creationDate.getFullYear() === now.getFullYear()
        case 'quarter':
          const currentQuarter = Math.floor(now.getMonth() / 3)
          const creationQuarter = Math.floor(creationDate.getMonth() / 3)
          return currentQuarter === creationQuarter && creationDate.getFullYear() === now.getFullYear()
        case 'year':
          return creationDate.getFullYear() === now.getFullYear()
        default:
          return true
      }
    })
  }
  // Filtrage par distances LR/DI
  if (filtresAvances.value.lrMin !== null || filtresAvances.value.lrMax !== null) {
    filtered = filtered.filter(intervention => {
      const lr = intervention.longueur_liaison_reseau || 0
      return (filtresAvances.value.lrMin === null || lr >= filtresAvances.value.lrMin) &&
             (filtresAvances.value.lrMax === null || lr <= filtresAvances.value.lrMax)
    })
  }
  if (filtresAvances.value.diMin !== null || filtresAvances.value.diMax !== null) {
    filtered = filtered.filter(intervention => {
      const di = intervention.longueur_derivation_individuelle || 0
      return (filtresAvances.value.diMin === null || di >= filtresAvances.value.diMin) &&
             (filtresAvances.value.diMax === null || di <= filtresAvances.value.diMax)
    })
  }
  // Filtrage par technicien
  if (selectedTechniciens.value.length > 0) {
    filtered = filtered.filter(intervention =>
      selectedTechniciens.value.includes(intervention.technicien_branchement_nom) ||
      selectedTechniciens.value.includes(intervention.technicien_terrassement_nom)
    )
  }
  return filtered
})
// Message pour état vide
const emptyMessage = computed(() => {
  if (searchTerm.value || selectedStatuts.value.length || selectedTypes.value.length || selectedTechniciens.value.length) {
    return 'Aucune intervention ne correspond aux filtres sélectionnés'
  }
  return 'Commencez par créer votre premier branchement électrique'
})
/**
 * Applique les filtres (fonction appelée automatiquement)
 */
const applyFilters = () => {
  // Les filtres sont appliqués automatiquement via les computed
}
/**
 * Actualise les données avec feedback visuel
 */
const refreshData = async () => {
  if (refreshing.value) return
  refreshing.value = true
  try {
    await loadInterventions(false) // false = ne pas afficher le spinner principal
    lastRefresh.value = new Date().toISOString()
    toast.add({
      severity: 'success',
      summary: 'Données actualisées',
      detail: `${interventions.value.length} branchements chargés`,
      life: 2000
    })
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Erreur d\'actualisation',
      detail: 'Impossible d\'actualiser les données',
      life: 3000
    })
  } finally {
    refreshing.value = false
  }
}
/**
 * Active/désactive l'actualisation automatique
 */
const toggleAutoRefresh = () => {
  autoRefresh.value = !autoRefresh.value
  if (autoRefresh.value) {
    // Démarrer l'actualisation automatique toutes les 30 secondes
    refreshInterval.value = setInterval(() => {
      refreshData()
    }, 30000)
    toast.add({
      severity: 'info',
      summary: 'Auto-actualisation activée',
      detail: 'Les données seront actualisées toutes les 30 secondes',
      life: 3000
    })
  } else {
    // Arrêter l'actualisation automatique
    if (refreshInterval.value) {
      clearInterval(refreshInterval.value)
      refreshInterval.value = null
    }
    toast.add({
      severity: 'info',
      summary: 'Auto-actualisation désactivée',
      detail: 'Actualisation manuelle uniquement',
      life: 2000
    })
  }
}
/**
 * Réinitialise tous les filtres
 */
const resetFilters = () => {
  searchTerm.value = ''
  selectedStatuts.value = []
  selectedTypes.value = []
  selectedTechniciens.value = []
  selectedTypesReglementaires.value = []
  selectedModesPose.value = []
  selectedRetards.value = []
  selectedPeriode.value = ''
  filtresAvances.value = {
    lrMin: null,
    lrMax: null,
    diMin: null,
    diMax: null
  }
  showAdvancedFilters.value = false
}
/**
 * Navigue vers la page de création
 */
const createIntervention = () => {
  router.push('/interventions/electrique/create')
}
/**
 * Navigue vers les détails d'une intervention
 */
const viewIntervention = (id) => {
  router.push(`/interventions/electrique/${id}`)
}
/**
 * Démarre une session de travail rapide
 */
const startSession = (intervention) => {
  // Pour l'instant, redirige vers les détails
  // TODO: Implémenter un modal de démarrage rapide
  router.push(`/interventions/electrique/${intervention.id}`)
}
/**
 * Termine une intervention
 */
const completeIntervention = async (id) => {
  // Pour l'instant, redirige vers les détails
  // TODO: Implémenter la terminaison rapide
  router.push(`/interventions/electrique/${id}`)
}
/**
 * Exporte les données en CSV
 */
const exportToCsv = () => {
  const headers = [
    'Numéro', 'Client', 'Type', 'Statut', 'Durée (h)', 'Coût total',
    'Écart (%)', 'Technicien Branchement', 'Technicien Terrassement', 'Date création'
  ]
  const csvContent = [
    headers.join(';'),
    ...filteredInterventions.value.map(intervention => [
      intervention.numero,
      intervention.client_nom,
      intervention.type_prestation_nom,
      getStatutGlobal(intervention),
      intervention.duree_totale_heures || 0,
      intervention.cout_total_reel || 0,
      intervention.ecart_pourcentage || 0,
      intervention.technicien_branchement_nom || '',
      intervention.technicien_terrassement_nom || '',
      formatDate(intervention.date_creation)
    ].map(field => `"${field}"`).join(';'))
  ].join('\n')
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  const url = URL.createObjectURL(blob)
  link.setAttribute('href', url)
  link.setAttribute('download', `branchements_electriques_${Date.now()}.csv`)
  link.style.visibility = 'hidden'
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  toast.add({
    severity: 'success',
    summary: 'Export réussi',
    detail: 'Le fichier CSV a été téléchargé',
    life: 3000
  })
}
/**
 * Détermine le statut global d'une intervention
 */
const getStatutGlobal = (intervention) => {
  const branchementOk = intervention.phase_branchement_statut === 'terminee'
  const terrassementOk = !intervention.has_terrassement ||
                        intervention.phase_terrassement_statut === 'terminee'
  if (branchementOk && terrassementOk) return 'Terminée'
  if (intervention.phase_branchement_statut === 'en_cours' ||
      intervention.phase_terrassement_statut === 'en_cours') {
    return 'En cours'
  }
  if (intervention.phase_branchement_statut === 'annulee' ||
      intervention.phase_terrassement_statut === 'annulee') {
    return 'Annulée'
  }
  return 'En attente'
}
/**
 * Retourne la severity PrimeVue selon le statut
 */
const getStatutSeverity = (statut) => {
  const severities = {
    'en_attente': 'warning',
    'en_cours': 'info',
    'terminee': 'success',
    'terminée': 'success',
    'annulee': 'danger',
    'annulée': 'danger',
    'non_applicable': 'secondary'
  }
  return severities[statut?.toLowerCase()] || 'secondary'
}
/**
 * Retourne les classes CSS pour l'affichage de l'écart
 */
const getEcartClass = (ecart) => {
  if (ecart > 10) return 'text-red-600 dark:text-red-400'
  if (ecart > 0) return 'text-orange-600 dark:text-orange-400'
  if (ecart < -5) return 'text-green-600 dark:text-green-400'
  return 'text-surface-600 dark:text-surface-400'
}
/**
 * Vérifie si l'utilisateur peut éditer une intervention
 */
const canEdit = (intervention) => {
  return hasPermission('technicien') &&
         ['en_attente', 'en_cours'].includes(getStatutGlobal(intervention).toLowerCase().replace(' ', '_'))
}
/**
 * Prépare les données de délai pour DelayIndicator
 */
const getDelayData = (intervention) => {
  const dateCreation = new Date(intervention.date_creation)
  const maintenant = new Date()
  // Calculer la date d'échéance selon le type réglementaire
  const delaiJours = intervention.type_reglementaire === 'type_1' ? 21 : 30
  const dateEcheance = new Date(dateCreation)
  dateEcheance.setDate(dateEcheance.getDate() + delaiJours)
  // Date cible pour planification (80% du délai)
  const dateCible = new Date(dateCreation)
  dateCible.setDate(dateCible.getDate() + Math.floor(delaiJours * 0.8))
  return {
    startDate: dateCreation.toISOString(),
    endDate: dateEcheance.toISOString(),
    targetDate: dateCible.toISOString(),
    type: intervention.type_reglementaire,
    status: getStatutGlobal(intervention)
  }
}
/**
 * Actions suggérées pour les délais
 */
const getDelayActions = (intervention) => {
  const actions = []
  const delaiStatus = getDelaiStatus(intervention)
  if (delaiStatus?.status === 'retard') {
    actions.push(
      { label: 'Prioriser', icon: 'pi pi-arrow-up', type: 'prioritize', severity: 'danger' },
      { label: 'Assigner', icon: 'pi pi-user-plus', type: 'assign', severity: 'warning' }
    )
  } else if (delaiStatus?.status === 'alerte') {
    actions.push(
      { label: 'Planifier', icon: 'pi pi-calendar', type: 'schedule', severity: 'warning' }
    )
  }
  actions.push(
    { label: 'Voir', icon: 'pi pi-eye', type: 'view', severity: 'secondary' }
  )
  return actions
}
/**
 * Gestion des actions de délai
 */
const handleDelayAction = (actionType, delayData) => {
  switch (actionType) {
    case 'prioritize':
      // Logique de priorisation
      console.log('Prioriser intervention', delayData)
      break
    case 'assign':
      // Logique d'assignation
      console.log('Assigner intervention', delayData)
      break
    case 'schedule':
      // Logique de planification
      navigateTo('/planning')
      break
    case 'view':
      // Voir détails
      if (delayData.interventionId) {
        navigateTo(`/interventions/electrique/${delayData.interventionId}`)
      }
      break
    default:
      console.log('Action délai non définie:', actionType)
  }
}
/**
 * Vérifie si l'utilisateur peut terminer une intervention
 */
const canComplete = (intervention) => {
  return hasPermission('manager') &&
         intervention.phase_branchement_statut === 'terminee' &&
         (!intervention.has_terrassement || intervention.phase_terrassement_statut === 'terminee')
}
/**
 * Formate une durée en heures
 */
const formatDuration = (hours) => {
  if (!hours) return '00:00'
  const h = Math.floor(hours)
  const m = Math.round((hours - h) * 60)
  return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}`
}
/**
 * Formate un montant en euros
 */
const formatCurrency = (amount) => {
  if (!amount) return '-'
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR'
  }).format(amount)
}
/**
 * Formate une date
 */
const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('fr-FR')
}
/**
 * Formate une heure
 */
const formatTime = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleTimeString('fr-FR', {
    hour: '2-digit',
    minute: '2-digit'
  })
}
/**
 * Nouvelles fonctions pour les spécifications Enedis
 */
/**
 * Retourne le label du type réglementaire
 */
const getTypeReglementaireLabel = (type) => {
  switch (type) {
    case 'type_1': return 'Type 1'
    case 'type_2': return 'Type 2'
    default: return 'N/A'
  }
}
/**
 * Retourne la classe CSS pour la colonne DI selon le type
 */
const getDIClass = (intervention) => {
  const di = intervention?.longueur_derivation_individuelle || 0
  const type = intervention?.type_reglementaire
  if (type === 'type_1' && di > 30) {
    return 'text-orange-600 dark:text-orange-400' // Alerte: DI > 30m en Type 1
  }
  if (type === 'type_2' && di <= 30) {
    return 'text-blue-600 dark:text-blue-400' // Info: DI ≤ 30m en Type 2
  }
  if (type === 'type_1') {
    return 'text-green-600 dark:text-green-400' // OK: Type 1 avec DI ≤ 30m
  }
  if (type === 'type_2') {
    return 'text-purple-600 dark:text-purple-400' // OK: Type 2 avec DI > 30m
  }
  return 'text-surface-900 dark:text-surface-50' // Par défaut
}
/**
 * Vérifie s'il y a une incohérence dans les distances
 */
const hasDistanceInconsistency = (intervention) => {
  const lr = intervention?.longueur_liaison_reseau || 0
  const di = intervention?.longueur_derivation_individuelle || 0
  const total = intervention?.distance_raccordement || 0
  const calculatedTotal = lr + di
  const tolerance = 2 // Tolérance de 2m
  return Math.abs(calculatedTotal - total) > tolerance
}
/**
 * Retourne la severity pour le type réglementaire
 */
const getTypeReglementaireSeverity = (type) => {
  switch (type) {
    case 'type_1': return 'info'
    case 'type_2': return 'warning'
    default: return 'secondary'
  }
}
/**
 * Retourne l'icône pour le mode de pose
 */
const getModePoseIcon = (mode) => {
  switch (mode) {
    case 'aerien': return 'pi pi-cloud'
    case 'souterrain': return 'pi pi-arrow-down-right'
    case 'aerosouterrain': return 'pi pi-arrow-right-arrow-left'
    case 'souterrain_boite': return 'pi pi-box'
    case 'di_seule': return 'pi pi-home'
    default: return 'pi pi-question-circle'
  }
}
/**
 * Retourne le label pour le mode de pose
 */
const getModePoseLabel = (mode) => {
  switch (mode) {
    case 'aerien': return 'Aérien'
    case 'souterrain': return 'Souterrain'
    case 'aerosouterrain': return 'Aérosouterrain'
    case 'souterrain_boite': return 'Souterrain sur boîte'
    case 'di_seule': return 'DI seule'
    default: return 'Non défini'
  }
}
/**
 * Calcule et retourne le statut des délais pour une intervention
 */
const getDelaiStatus = (intervention) => {
  if (!intervention.date_reception_dossier) {
    return { label: 'N/A', severity: 'secondary', jours: 0, status: 'ok' }
  }
  const now = new Date()
  const reception = new Date(intervention.date_reception_dossier)
  const joursEcoules = Math.floor((now - reception) / (1000 * 60 * 60 * 24))
  // Logique de délais basée sur la phase actuelle
  const phase = getPhaseActuelle(intervention)
  // Seuils de délais par phase (en jours)
  const seuils = {
    'Réception': { ok: 2, alerte: 5 },
    'Étude technique': { ok: 7, alerte: 14 },
    'Validation devis': { ok: 10, alerte: 20 },
    'Terrassement': { ok: 25, alerte: 35 },
    'Câblage': { ok: 30, alerte: 40 },
    'Mise en service': { ok: 35, alerte: 45 },
    'Terminée': { ok: Infinity, alerte: Infinity }
  }
  const seuil = seuils[phase] || { ok: 30, alerte: 45 }
  if (joursEcoules <= seuil.ok) {
    return { label: 'OK', severity: 'success', jours: joursEcoules, status: 'ok' }
  } else if (joursEcoules <= seuil.alerte) {
    return { label: 'Risque', severity: 'warning', jours: joursEcoules, status: 'risque' }
  } else {
    return { label: 'Retard', severity: 'danger', jours: joursEcoules, status: 'retard' }
  }
}
/**
 * Détermine la phase actuelle d'une intervention
 */
const getPhaseActuelle = (intervention) => {
  if (intervention.date_mise_en_service) return 'Terminée'
  if (intervention.date_realisation_cablage) return 'Mise en service'
  if (intervention.date_realisation_terrassement) return 'Câblage'
  if (intervention.date_validation_devis) return 'Terrassement'
  if (intervention.date_etude_technique) return 'Validation devis'
  if (intervention.date_reception_dossier) return 'Étude technique'
  return 'Réception'
}
/**
 * Calcule le pourcentage de progression d'une intervention
 */
const getProgressionPourcentage = (intervention) => {
  const etapes = [
    'date_reception_dossier',
    'date_etude_technique',
    'date_validation_devis',
    'date_realisation_terrassement',
    'date_realisation_cablage',
    'date_mise_en_service'
  ]
  let completees = 0
  etapes.forEach(etape => {
    if (intervention[etape]) completees++
  })
  return Math.round((completees / etapes.length) * 100)
}
/**
 * Retourne la classe CSS pour la barre de progression
 */
const getProgressBarClass = (intervention) => {
  const delaiStatus = getDelaiStatus(intervention)
  switch (delaiStatus.status) {
    case 'ok': return 'bg-green-500'
    case 'risque': return 'bg-yellow-500'
    case 'retard': return 'bg-red-500'
    default: return 'bg-surface-400'
  }
}
/**
 * Retourne l'icône pour l'indicateur de délai
 */
const getDelaiIcon = (intervention) => {
  const delaiStatus = getDelaiStatus(intervention)
  switch (delaiStatus.status) {
    case 'ok': return 'pi pi-check-circle text-green-500'
    case 'risque': return 'pi pi-exclamation-triangle text-yellow-500'
    case 'retard': return 'pi pi-times-circle text-red-500'
    default: return 'pi pi-info-circle text-surface-400'
  }
}
/**
 * Retourne la classe CSS pour le texte du délai
 */
const getDelaiTextClass = (intervention) => {
  const delaiStatus = getDelaiStatus(intervention)
  switch (delaiStatus.status) {
    case 'ok': return 'text-green-600 dark:text-green-400'
    case 'risque': return 'text-yellow-600 dark:text-yellow-400'
    case 'retard': return 'text-red-600 dark:text-red-400'
    default: return 'text-surface-600 dark:text-surface-400'
  }
}
/**
 * Retourne la classe CSS pour le texte de progression
 */
const getProgressTextClass = (intervention) => {
  const progression = getProgressionPourcentage(intervention)
  if (progression >= 80) return 'text-green-600 dark:text-green-400'
  if (progression >= 50) return 'text-blue-600 dark:text-blue-400'
  if (progression >= 25) return 'text-yellow-600 dark:text-yellow-400'
  return 'text-red-600 dark:text-red-400'
}
/**
 * Retourne la classe CSS pour la phase selon l'urgence
 */
const getPhaseUrgenceClass = (intervention) => {
  const delaiStatus = getDelaiStatus(intervention)
  switch (delaiStatus.status) {
    case 'ok': return 'text-surface-600 dark:text-surface-400'
    case 'risque': return 'text-yellow-700 dark:text-yellow-300 font-medium'
    case 'retard': return 'text-red-700 dark:text-red-300 font-bold'
    default: return 'text-surface-600 dark:text-surface-400'
  }
}
/**
 * Charge les interventions électriques depuis l'API
 */
const loadInterventions = async (showLoading = true) => {
  if (showLoading) {
    loading.value = true
  }
  // S'assurer que nous sommes côté client avant de faire l'appel API
  if (!process.client) {
    if (showLoading) {
      loading.value = false
    }
    return
  }
  // Charger les données depuis l'API
  try {
    const response = await $api.get('/intervention_electrique.php')
    if (response && response.success) {
      interventions.value = response.data || []
    } else {
      console.error('Erreur API:', response?.message || 'Pas de données')
      toast.add({
        severity: 'error',
        summary: 'Erreur de chargement',
        detail: response?.message || 'Impossible de charger les branchements',
        life: 5000
      })
      // Données de test en cas d'échec API
      interventions.value = [
        {
          id: 1,
          numero: 'BR-2024-001',
          titre: 'Branchement souterrain - Maison Dupuis',
          client_nom: 'Maison Dupuis',
          type_prestation_nom: 'Branchement Souterrain Type 2',
          type_prestation_code: 'SOUTERRAIN_TYPE_2',
          has_terrassement: true,
          phase_branchement_statut: 'en_attente',
          phase_terrassement_statut: 'en_cours',
          technicien_branchement_nom: 'Michel Martin',
          technicien_terrassement_nom: 'Jean Dupont',
          duree_totale_heures: null,
          cout_total_reel: null,
          ecart_pourcentage: null,
          date_creation: new Date().toISOString(),
          priorite: 'normale',
          // Nouveaux champs Enedis
          type_reglementaire: 'type_2',
          mode_pose: 'souterrain',
          longueur_liaison_reseau: 15,
          longueur_derivation_individuelle: 35,
          distance_raccordement: 50,
          date_reception_dossier: new Date(Date.now() - 3 * 24 * 60 * 60 * 1000).toISOString(),
          date_etude_technique: new Date(Date.now() - 1 * 24 * 60 * 60 * 1000).toISOString(),
          date_validation_devis: null,
          date_realisation_terrassement: null,
          date_realisation_cablage: null,
          date_mise_en_service: null
        },
        {
          id: 2,
          numero: 'BR-2024-002',
          titre: 'Branchement aérien - Villa Martin',
          client_nom: 'Villa Martin',
          type_prestation_nom: 'Branchement Aérien Type 1',
          type_prestation_code: 'AERIEN_TYPE_1',
          has_terrassement: false,
          phase_branchement_statut: 'terminee',
          phase_terrassement_statut: 'non_applicable',
          technicien_branchement_nom: 'Louis Durand',
          technicien_terrassement_nom: null,
          duree_totale_heures: 5.5,
          cout_total_reel: 247.50,
          ecart_pourcentage: -2.5,
          date_creation: new Date(Date.now() - 86400000).toISOString(),
          priorite: 'haute'
        },
        {
          id: 3,
          numero: 'BR-2024-003',
          titre: 'Branchement aérosouterrain - Résidence Bernard',
          client_nom: 'Résidence Bernard',
          type_prestation_nom: 'Branchement Aérosouterrain Type 2',
          type_prestation_code: 'AEROSOUTERRAIN_TYPE_2',
          has_terrassement: true,
          phase_branchement_statut: 'en_attente',
          phase_terrassement_statut: 'en_attente',
          technicien_branchement_nom: 'Michel Martin',
          technicien_terrassement_nom: 'Pierre Bernard',
          duree_totale_heures: null,
          cout_total_reel: null,
          ecart_pourcentage: null,
          date_creation: new Date(Date.now() - 172800000).toISOString(),
          priorite: 'normale'
        },
        {
          id: 4,
          numero: 'BR-2024-004',
          titre: 'Branchement sur boîte - Pavillon Durand',
          client_nom: 'Pavillon Durand',
          type_prestation_nom: 'Souterrain sur Boîte Type 1',
          type_prestation_code: 'SOUTERRAIN_BOITE_TYPE_1',
          has_terrassement: true,
          phase_branchement_statut: 'en_cours',
          phase_terrassement_statut: 'terminee',
          technicien_branchement_nom: 'Louis Durand',
          technicien_terrassement_nom: 'Jean Dupont',
          duree_totale_heures: 6.75,
          cout_total_reel: 406.50,
          ecart_pourcentage: 3.2,
          date_creation: new Date(Date.now() - 259200000).toISOString(),
          priorite: 'basse'
        },
        {
          id: 5,
          numero: 'BR-2024-005',
          titre: 'DI Seule - Lotissement Moreau',
          client_nom: 'Lotissement Moreau',
          type_prestation_nom: 'DI Seule Type 2',
          type_prestation_code: 'DI_SEULE_TYPE_2',
          has_terrassement: true,
          phase_branchement_statut: 'terminee',
          phase_terrassement_statut: 'terminee',
          technicien_branchement_nom: 'Michel Martin',
          technicien_terrassement_nom: 'Pierre Bernard',
          duree_totale_heures: 10.5,
          cout_total_reel: 416.50,
          ecart_pourcentage: -5.1,
          date_creation: new Date(Date.now() - 432000000).toISOString(),
          priorite: 'urgente'
        }
      ]
    }
  } catch (error) {
    console.error('Erreur lors du chargement des interventions:', error)
    // Vérifier si c'est une erreur d'authentification
    if (error?.status === 401 || error?.statusCode === 401) {
      toast.add({
        severity: 'info',
        summary: 'Authentification requise',
        detail: 'Veuillez vous connecter pour accéder aux données réelles. Affichage des données de test.',
        life: 5000
      })
    } else {
      toast.add({
        severity: 'warn',
        summary: 'Mode hors ligne',
        detail: 'Impossible de se connecter au serveur. Affichage des données de test.',
        life: 5000
      })
    }
    // En cas d'erreur, initialiser avec un tableau vide
    interventions.value = []
  } finally {
    if (showLoading) {
      loading.value = false
    }
  }
}
// Initialisation
onMounted(() => {
  // Charger les interventions au montage du composant
  loadInterventions()
  // Marquer la première actualisation
  lastRefresh.value = new Date().toISOString()
})
// Nettoyage
onUnmounted(() => {
  // Nettoyer l'interval d'auto-actualisation
  if (refreshInterval.value) {
    clearInterval(refreshInterval.value)
    refreshInterval.value = null
  }
})
</script>
<style scoped>
/**
 * Styles CSS personnalisés pour la page de liste
 */
.branchements-electriques-page {
  max-width: 80rem;
  margin-left: auto;
  margin-right: auto;
  padding: 1rem;
}
/* Animation d'entrée */
.branchements-electriques-page {
  animation: fadeInUp 0.6s ease-out;
}
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
/* Responsive */
@media (max-width: 768px) {
  .branchements-electriques-page {
    padding: 0.5rem;
  }
  /* Masquer certaines colonnes sur mobile */
  :deep(.p-datatable .p-column-header),
  :deep(.p-datatable .p-datatable-tbody td) {
    &:nth-child(4),  /* LR */
    &:nth-child(5),  /* DI */
    &:nth-child(6),  /* Total */
    &:nth-child(8)   /* Temps & Coût */ {
      display: none;
    }
  }
  /* Adapter la largeur du tableau sur mobile */
  :deep(.p-datatable-wrapper) {
    overflow-x: auto;
  }
  /* Style mobile pour les cartes de filtres */
  .grid.grid-cols-1.md\\:grid-cols-2.lg\\:grid-cols-6 {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  /* Boutons plus grands sur mobile */
  :deep(.p-button) {
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
  }
  /* Labels des colonnes mobiles */
  :deep(.p-datatable-tbody td) {
    position: relative;
    padding-left: 40%;
    min-height: 3rem;
    &::before {
      content: attr(data-label);
      position: absolute;
      left: 0.75rem;
      top: 0.75rem;
      font-weight: 600;
      font-size: 0.75rem;
      color: var(--surface-600);
      width: 35%;
    }
  }
}
@media (max-width: 640px) {
  /* Mode très petit écran */
  :deep(.p-datatable .p-column-header),
  :deep(.p-datatable .p-datatable-tbody td) {
    &:nth-child(7),  /* Phases */
    &:nth-child(9)   /* Délais */ {
      display: none;
    }
  }
  /* En-tête mobile */
  .flex.flex-col.sm\\:flex-row {
    gap: 1rem;
  }
  /* Tableau en mode carte sur très petit écran */
  :deep(.p-datatable-tbody tr) {
    display: block;
    border: 1px solid var(--surface-200);
    margin-bottom: 1rem;
    border-radius: 0.5rem;
    background: var(--surface-0);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }
  :deep(.p-datatable-tbody td) {
    display: block;
    border: none;
    border-bottom: 1px solid var(--surface-100);
    padding: 0.75rem;
    &:last-child {
      border-bottom: none;
    }
  }
  :deep(.p-datatable-header) {
    display: none;
  }
}
/* Style pour les badges dans le tableau */
:deep(.p-badge) {
  font-size: 0.75rem;
  line-height: 1rem;
  padding-left: 0.5rem;
  padding-right: 0.5rem;
  padding-top: 0.25rem;
  padding-bottom: 0.25rem;
}
/* Animation pour les lignes du tableau */
:deep(.p-datatable-tbody > tr) {
  transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 200ms;
}
:deep(.p-datatable-tbody > tr:hover) {
  background-color: rgb(248 250 252);
}
:deep(.dark .p-datatable-tbody > tr:hover) {
  background-color: rgb(30 41 59);
}
/* Style pour les liens des numéros */
:deep(.p-datatable-tbody a) {
  transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 200ms;
}
</style>