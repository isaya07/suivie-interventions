<!--
  Page de liste des branchements électriques

  Affiche tous les branchements électriques avec filtrage,
  tri et actions rapides. Interface optimisée pour la gestion
  des phases et du suivi du temps.
-->
<template>
  <div>
    <AppHeader />

    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
      <div class="px-4 py-6 sm:px-0">
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
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Recherche -->
          <div class="space-y-2">
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

          <!-- Type de prestation -->
          <div class="space-y-2">
            <label class="text-sm font-medium text-surface-700 dark:text-surface-300">
              Type
            </label>
            <MultiSelect
              v-model="selectedTypes"
              :options="typeOptions"
              option-label="label"
              option-value="value"
              placeholder="Tous les types"
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

        <!-- Actions rapides -->
        <div class="flex flex-wrap gap-2 mt-4 pt-4 border-t border-surface-200 dark:border-surface-700">
          <Button
            label="Actualiser"
            icon="pi pi-refresh"
            severity="secondary"
            size="small"
            @click="loadInterventions"
          />
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
              <NuxtLink
                :to="`/interventions/electrique/${data.id}`"
                class="font-mono font-bold text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-200"
              >
                #{{ data.numero }}
              </NuxtLink>
            </template>
          </Column>

          <!-- Colonne Client -->
          <Column field="client_nom" header="Client" sortable class="min-w-[200px]">
            <template #body="{ data }">
              <div>
                <div class="font-medium text-surface-900 dark:text-surface-50">
                  {{ data.client_nom }}
                </div>
                <div class="text-sm text-surface-600 dark:text-surface-400">
                  {{ data.type_prestation_nom }}
                </div>
              </div>
            </template>
          </Column>

          <!-- Colonne Phases -->
          <Column header="Phases" class="min-w-[200px]">
            <template #body="{ data }">
              <div class="space-y-1">
                <!-- Phase Terrassement -->
                <div v-if="data.has_terrassement" class="flex items-center gap-2">
                  <Badge
                    value="Terrassement"
                    :severity="getStatutSeverity(data.phase_terrassement_statut)"
                    class="text-xs"
                  />
                  <span v-if="data.technicien_terrassement_nom" class="text-xs text-surface-600 dark:text-surface-400">
                    {{ data.technicien_terrassement_nom }}
                  </span>
                </div>

                <!-- Phase Branchement -->
                <div class="flex items-center gap-2">
                  <Badge
                    value="Branchement"
                    :severity="getStatutSeverity(data.phase_branchement_statut)"
                    class="text-xs"
                  />
                  <span v-if="data.technicien_branchement_nom" class="text-xs text-surface-600 dark:text-surface-400">
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
                  {{ formatDuration(data.duree_totale_heures) }}
                </div>
                <div class="text-sm font-medium text-surface-900 dark:text-surface-50">
                  {{ formatCurrency(data.cout_total_reel) }}
                </div>
                <div v-if="data.ecart_pourcentage" class="text-xs" :class="getEcartClass(data.ecart_pourcentage)">
                  {{ data.ecart_pourcentage > 0 ? '+' : '' }}{{ data.ecart_pourcentage }}%
                </div>
              </div>
            </template>
          </Column>

          <!-- Colonne Statut global -->
          <Column header="Statut" class="min-w-[120px]">
            <template #body="{ data }">
              <Badge
                :value="getStatutGlobal(data)"
                :severity="getStatutSeverity(getStatutGlobal(data))"
                class="font-medium"
              />
            </template>
          </Column>

          <!-- Colonne Date -->
          <Column field="date_creation" header="Créée le" sortable class="min-w-[150px]">
            <template #body="{ data }">
              <div class="text-sm">
                <div class="font-medium">{{ formatDate(data.date_creation) }}</div>
                <div class="text-surface-600 dark:text-surface-400">
                  {{ formatTime(data.date_creation) }}
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
                  @click="viewIntervention(data.id)"
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
    </main>
  </div>
</template>

<script setup>
/**
 * Page de liste des branchements électriques
 *
 * Affiche tous les branchements électriques avec filtrage avancé,
 * actions rapides et navigation vers les détails.
 */

import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'primevue/usetoast'

// Configuration de la page
definePageMeta({
  title: 'Branchements Électriques',
  middleware: 'auth',
  layout: 'default'
})

const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()
const { $api } = useNuxtApp()

// État réactif
const interventions = ref([])
const loading = ref(true)
const searchTerm = ref('')
const selectedStatuts = ref([])
const selectedTypes = ref([])
const selectedTechniciens = ref([])

// Options pour les filtres
const statutOptions = [
  { label: 'En attente', value: 'en_attente' },
  { label: 'En cours', value: 'en_cours' },
  { label: 'Terminée', value: 'terminee' },
  { label: 'Annulée', value: 'annulee' }
]

const typeOptions = [
  { label: 'Branchement seul', value: 'BRANCHEMENT_SEUL' },
  { label: 'Branchement avec terrassement', value: 'BRANCHEMENT_TERRASSEMENT' }
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

  // Filtrage par type
  if (selectedTypes.value.length > 0) {
    filtered = filtered.filter(intervention =>
      selectedTypes.value.includes(intervention.type_prestation_code)
    )
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
 * Charge la liste des branchements électriques
 */
const loadInterventions = async () => {
  loading.value = true

  try {
    const response = await $api.get('/intervention_electrique.php')

    if (response.success) {
      interventions.value = response.interventions || []
    } else {
      throw new Error(response.message || 'Erreur lors du chargement')
    }
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: error.message,
      life: 5000
    })
    interventions.value = []
  } finally {
    loading.value = false
  }
}

/**
 * Applique les filtres (fonction appelée automatiquement)
 */
const applyFilters = () => {
  // Les filtres sont appliqués automatiquement via les computed
}

/**
 * Réinitialise tous les filtres
 */
const resetFilters = () => {
  searchTerm.value = ''
  selectedStatuts.value = []
  selectedTypes.value = []
  selectedTechniciens.value = []
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
  return authStore.hasPermission('technicien') &&
         ['en_attente', 'en_cours'].includes(getStatutGlobal(intervention).toLowerCase().replace(' ', '_'))
}

/**
 * Vérifie si l'utilisateur peut terminer une intervention
 */
const canComplete = (intervention) => {
  return authStore.hasPermission('manager') &&
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

// Initialisation
onMounted(() => {
  loadInterventions()
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