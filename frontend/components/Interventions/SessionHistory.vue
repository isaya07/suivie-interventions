<!--
  Composant d'affichage de l'historique des sessions de travail

  Fonctionnalités :
  - Tableau responsive avec tri et filtrage
  - Détails de chaque session (durée, coût, notes)
  - Filtrage par phase et technicien
  - Export des données en CSV
  - Interface PrimeVue optimisée
-->
<template>
  <div class="session-history">
    <!-- Filtres et contrôles -->
    <div class="flex flex-col md:flex-row gap-4 mb-4">
      <div class="flex-1">
        <MultiSelect
          v-model="selectedPhases"
          :options="phaseOptions"
          option-label="label"
          option-value="value"
          placeholder="Filtrer par phase"
          :max-selected-labels="2"
          class="w-full"
          @change="applyFilters"
        />
      </div>

      <div class="flex-1">
        <MultiSelect
          v-model="selectedTechniciens"
          :options="technicienOptions"
          option-label="label"
          option-value="value"
          placeholder="Filtrer par technicien"
          :max-selected-labels="2"
          class="w-full"
          @change="applyFilters"
        />
      </div>

      <div class="flex gap-2">
        <Button
          icon="pi pi-refresh"
          severity="secondary"
          v-tooltip="'Actualiser'"
          @click="refresh"
        />
        <Button
          icon="pi pi-download"
          severity="secondary"
          v-tooltip="'Exporter CSV'"
          @click="exportToCsv"
        />
      </div>
    </div>

    <!-- Tableau des sessions -->
    <DataTable
      :value="filteredSessions"
      :paginator="true"
      :rows="10"
      :rows-per-page-options="[10, 25, 50]"
      paginator-template="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
      current-page-report-template="{first} à {last} sur {totalRecords} sessions"
      :sort-field="'debut'"
      :sort-order="-1"
      responsive-layout="scroll"
      striped-rows
      class="w-full"
      :empty-message="emptyMessage"
    >
      <!-- Colonne Phase -->
      <Column field="phase" header="Phase" sortable class="min-w-[120px]">
        <template #body="{ data }">
          <Badge
            :value="getPhaseLabel(data.phase)"
            :severity="getPhaseSeverity(data.phase)"
            class="font-medium"
          />
        </template>
      </Column>

      <!-- Colonne Technicien -->
      <Column field="technicien_nom" header="Technicien" sortable class="min-w-[150px]">
        <template #body="{ data }">
          <div class="flex items-center gap-2">
            <Avatar
              :label="getTechnicienInitials(data.technicien_nom)"
              size="small"
              shape="circle"
              class="bg-primary-100 text-primary-700"
            />
            <span class="font-medium">{{ data.technicien_nom }}</span>
          </div>
        </template>
      </Column>

      <!-- Colonne Date/Heure -->
      <Column field="debut" header="Début" sortable class="min-w-[180px]">
        <template #body="{ data }">
          <div class="text-sm">
            <div class="font-medium">{{ formatDate(data.debut) }}</div>
            <div class="text-surface-600 dark:text-surface-400">
              {{ formatTime(data.debut) }}
              <span v-if="data.fin"> - {{ formatTime(data.fin) }}</span>
            </div>
          </div>
        </template>
      </Column>

      <!-- Colonne Durée -->
      <Column field="duree_minutes" header="Durée" sortable class="min-w-[100px]">
        <template #body="{ data }">
          <div class="text-center">
            <div class="font-mono font-bold text-lg">
              {{ formatDuration(data.duree_minutes) }}
            </div>
            <div class="text-xs text-surface-600 dark:text-surface-400">
              {{ data.duree_minutes || 0 }} min
            </div>
          </div>
        </template>
      </Column>

      <!-- Colonne Coût -->
      <Column field="cout_estime" header="Coût estimé" sortable class="min-w-[120px]">
        <template #body="{ data }">
          <div class="text-right font-medium">
            {{ formatCurrency(calculateSessionCost(data)) }}
          </div>
        </template>
      </Column>

      <!-- Colonne Statut -->
      <Column field="fin" header="Statut" class="min-w-[100px]">
        <template #body="{ data }">
          <Badge
            :value="data.fin ? 'Terminée' : 'En cours'"
            :severity="data.fin ? 'success' : 'info'"
          />
        </template>
      </Column>

      <!-- Colonne Actions -->
      <Column header="Actions" class="min-w-[80px]">
        <template #body="{ data }">
          <div class="flex gap-1">
            <Button
              icon="pi pi-eye"
              severity="secondary"
              size="small"
              v-tooltip="'Voir détails'"
              @click="showSessionDetails(data)"
            />
            <Button
              v-if="!data.fin"
              icon="pi pi-stop"
              severity="danger"
              size="small"
              v-tooltip="'Arrêter session'"
              @click="stopSession(data)"
            />
          </div>
        </template>
      </Column>

      <!-- Template pour état vide -->
      <template #empty>
        <div class="text-center p-8">
          <i class="pi pi-clock text-4xl text-surface-400 dark:text-surface-600 mb-4"></i>
          <h3 class="text-lg font-medium text-surface-700 dark:text-surface-300 mb-2">
            Aucune session trouvée
          </h3>
          <p class="text-surface-600 dark:text-surface-400">
            {{ emptyMessage }}
          </p>
        </div>
      </template>
    </DataTable>

    <!-- Dialog des détails de session -->
    <Dialog
      v-model:visible="showDetailsDialog"
      :modal="true"
      :closable="true"
      header="Détails de la session"
      class="w-full max-w-2xl"
    >
      <div v-if="selectedSession" class="space-y-6">
        <!-- Informations générales -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-3">
            <div>
              <label class="text-sm font-medium text-surface-700 dark:text-surface-300">Phase</label>
              <p class="text-surface-900 dark:text-surface-50">
                {{ getPhaseLabel(selectedSession.phase) }}
              </p>
            </div>
            <div>
              <label class="text-sm font-medium text-surface-700 dark:text-surface-300">Technicien</label>
              <p class="text-surface-900 dark:text-surface-50">
                {{ selectedSession.technicien_nom }}
              </p>
            </div>
          </div>

          <div class="space-y-3">
            <div>
              <label class="text-sm font-medium text-surface-700 dark:text-surface-300">Durée</label>
              <p class="text-surface-900 dark:text-surface-50 font-mono text-lg">
                {{ formatDuration(selectedSession.duree_minutes) }}
              </p>
            </div>
            <div>
              <label class="text-sm font-medium text-surface-700 dark:text-surface-300">Coût estimé</label>
              <p class="text-surface-900 dark:text-surface-50 font-medium">
                {{ formatCurrency(calculateSessionCost(selectedSession)) }}
              </p>
            </div>
          </div>
        </div>

        <!-- Timeline -->
        <div class="space-y-2">
          <label class="text-sm font-medium text-surface-700 dark:text-surface-300">Timeline</label>
          <div class="bg-surface-50 dark:bg-surface-800 p-4 rounded-lg">
            <div class="flex items-center gap-4 text-sm">
              <div class="flex items-center gap-2">
                <i class="pi pi-play-circle text-green-600"></i>
                <span>Début: {{ formatDateTime(selectedSession.debut) }}</span>
              </div>
              <div v-if="selectedSession.fin" class="flex items-center gap-2">
                <i class="pi pi-stop-circle text-red-600"></i>
                <span>Fin: {{ formatDateTime(selectedSession.fin) }}</span>
              </div>
              <div v-else class="flex items-center gap-2">
                <i class="pi pi-clock text-blue-600"></i>
                <span>En cours...</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Notes -->
        <div v-if="selectedSession.notes" class="space-y-2">
          <label class="text-sm font-medium text-surface-700 dark:text-surface-300">Notes</label>
          <div class="bg-surface-50 dark:bg-surface-800 p-4 rounded-lg">
            <p class="text-surface-900 dark:text-surface-50 whitespace-pre-wrap">
              {{ selectedSession.notes }}
            </p>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end">
          <Button
            label="Fermer"
            severity="secondary"
            @click="showDetailsDialog = false"
          />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<script setup>
/**
 * Composant d'affichage de l'historique des sessions de travail
 *
 * Props:
 * @param {array} sessions - Liste des sessions à afficher
 *
 * Événements émis:
 * @event refresh - Émis pour demander un rafraîchissement des données
 */

import { ref, computed, watch } from 'vue'
import { useToast } from 'primevue/usetoast'

const props = defineProps({
  sessions: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['refresh'])

const { $api } = useNuxtApp()
const toast = useToast()

// État réactif
const selectedPhases = ref([])
const selectedTechniciens = ref([])
const showDetailsDialog = ref(false)
const selectedSession = ref(null)

// Options pour les filtres
const phaseOptions = [
  { label: 'Terrassement', value: 'terrassement' },
  { label: 'Branchement', value: 'branchement' }
]

// Options dynamiques pour les techniciens
const technicienOptions = computed(() => {
  const techniciens = [...new Set(props.sessions.map(s => s.technicien_nom))].filter(Boolean)
  return techniciens.map(nom => ({ label: nom, value: nom }))
})

// Sessions filtrées
const filteredSessions = computed(() => {
  let sessions = [...props.sessions]

  // Filtrer par phase
  if (selectedPhases.value.length > 0) {
    sessions = sessions.filter(s => selectedPhases.value.includes(s.phase))
  }

  // Filtrer par technicien
  if (selectedTechniciens.value.length > 0) {
    sessions = sessions.filter(s => selectedTechniciens.value.includes(s.technicien_nom))
  }

  return sessions
})

// Message pour état vide
const emptyMessage = computed(() => {
  if (selectedPhases.value.length > 0 || selectedTechniciens.value.length > 0) {
    return 'Aucune session ne correspond aux filtres sélectionnés'
  }
  return 'Aucune session de travail enregistrée pour cette intervention'
})

/**
 * Applique les filtres sélectionnés
 */
const applyFilters = () => {
  // Les filtres sont appliqués automatiquement via les computed
}

/**
 * Actualise les données
 */
const refresh = () => {
  emit('refresh')
  toast.add({
    severity: 'info',
    summary: 'Actualisation',
    detail: 'Données actualisées',
    life: 2000
  })
}

/**
 * Affiche les détails d'une session
 */
const showSessionDetails = (session) => {
  selectedSession.value = session
  showDetailsDialog.value = true
}

/**
 * Arrête une session en cours
 */
const stopSession = async (session) => {
  try {
    const response = await $api.post('/intervention_electrique.php?action=stop_session', {
      session_id: session.id,
      notes: 'Session arrêtée depuis l\'historique'
    })

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Session arrêtée',
        detail: 'La session a été arrêtée avec succès',
        life: 3000
      })

      emit('refresh')
    } else {
      throw new Error(response.message)
    }
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: error.message,
      life: 5000
    })
  }
}

/**
 * Exporte les sessions en CSV
 */
const exportToCsv = () => {
  const headers = ['Phase', 'Technicien', 'Début', 'Fin', 'Durée (min)', 'Coût estimé', 'Notes']
  const csvContent = [
    headers.join(';'),
    ...filteredSessions.value.map(session => [
      getPhaseLabel(session.phase),
      session.technicien_nom || '',
      formatDateTime(session.debut),
      session.fin ? formatDateTime(session.fin) : 'En cours',
      session.duree_minutes || 0,
      calculateSessionCost(session),
      (session.notes || '').replace(/"/g, '""')
    ].map(field => `"${field}"`).join(';'))
  ].join('\n')

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  const url = URL.createObjectURL(blob)
  link.setAttribute('href', url)
  link.setAttribute('download', `sessions_intervention_${Date.now()}.csv`)
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
 * Calcule le coût estimé d'une session
 */
const calculateSessionCost = (session) => {
  if (!session.duree_minutes) return 0

  // Utiliser les taux standards par défaut
  const tauxHoraire = session.phase === 'terrassement' ? 38 : 45
  const heures = session.duree_minutes / 60

  return heures * tauxHoraire
}

/**
 * Retourne le libellé d'une phase
 */
const getPhaseLabel = (phase) => {
  return phase === 'terrassement' ? 'Terrassement' : 'Branchement'
}

/**
 * Retourne la severity d'une phase
 */
const getPhaseSeverity = (phase) => {
  return phase === 'terrassement' ? 'warning' : 'info'
}

/**
 * Obtient les initiales d'un technicien
 */
const getTechnicienInitials = (nom) => {
  if (!nom) return '??'
  return nom.split(' ').map(part => part.charAt(0).toUpperCase()).join('').slice(0, 2)
}

/**
 * Formate une durée en minutes au format HH:MM
 */
const formatDuration = (minutes) => {
  if (!minutes) return '00:00'
  const hours = Math.floor(minutes / 60)
  const mins = minutes % 60
  return `${hours.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}`
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
 * Formate une date et heure complète
 */
const formatDateTime = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleString('fr-FR', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
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
</script>

<style scoped>
/**
 * Styles CSS personnalisés pour SessionHistory
 */

.session-history {
  gap: 1rem;
  display: flex;
  flex-direction: column;
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

/* Responsive pour les contrôles */
@media (max-width: 768px) {
  .session-history :deep(.p-datatable-wrapper) {
    overflow-x: auto;
  }

  .session-history :deep(.p-column-header-content) {
    font-size: 0.75rem;
    line-height: 1rem;
  }
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
</style>