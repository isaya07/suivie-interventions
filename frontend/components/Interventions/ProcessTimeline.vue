<!--
  Composant Timeline du processus Enedis

  Affiche la progression du processus de branchement avec :
  - Dates de suivi (réception → mise en service)
  - Indicateurs de délais et performances
  - Actions de mise à jour des dates
-->
<template>
  <div class="process-timeline">
    <!-- Indicateurs de délais -->
    <div v-if="delaisData" class="mb-4 sm:mb-6 p-3 sm:p-4 bg-surface-50 dark:bg-surface-800 rounded-lg border">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-3 sm:mb-4 gap-2 sm:gap-0">
        <h4 class="text-base sm:text-lg font-medium text-surface-900 dark:text-surface-50">
          Indicateurs de performance
        </h4>
        <Button
          icon="pi pi-refresh"
          severity="secondary"
          text
          size="small"
          @click="loadDelais"
          :loading="loadingDelais"
          v-tooltip="'Actualiser les délais'"
        />
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
        <div class="text-center p-2 sm:p-0">
          <div class="text-xl sm:text-2xl font-bold" :class="getDelaiClass(delaisData.indicateurs_performance?.respect_delai_etude)">
            {{ delaisData.delais_processus?.reception_vers_etude || 0 }}j
          </div>
          <div class="text-xs sm:text-sm text-surface-600 dark:text-surface-400 mb-1">Délai étude</div>
          <Badge
            :value="delaisData.indicateurs_performance?.respect_delai_etude || 'N/A'"
            :severity="getDelaiSeverity(delaisData.indicateurs_performance?.respect_delai_etude)"
            class="text-xs"
          />
        </div>

        <div class="text-center p-2 sm:p-0">
          <div class="text-xl sm:text-2xl font-bold text-surface-900 dark:text-surface-50">
            {{ delaisData.delais_processus?.delai_total || 0 }}j
          </div>
          <div class="text-xs sm:text-sm text-surface-600 dark:text-surface-400 mb-1">Délai total</div>
          <Badge
            :value="delaisData.indicateurs_performance?.respect_delai_global || 'N/A'"
            :severity="getDelaiSeverity(delaisData.indicateurs_performance?.respect_delai_global)"
            class="text-xs"
          />
        </div>

        <div class="text-center p-2 sm:p-0">
          <div class="text-xl sm:text-2xl font-bold text-primary-600 dark:text-primary-400">
            {{ delaisData.indicateurs_performance?.pourcentage_avancement || 0 }}%
          </div>
          <div class="text-xs sm:text-sm text-surface-600 dark:text-surface-400 mb-1">Avancement</div>
          <div class="text-xs text-surface-500 dark:text-surface-400">
            {{ getPhaseActuelleLabel(delaisData.indicateurs_performance?.phase_actuelle) }}
          </div>
        </div>
      </div>
    </div>

    <!-- Timeline -->
    <Timeline :value="timelineEvents" align="left" class="custom-timeline">
      <template #marker="slotProps">
        <div
          class="flex w-5 h-5 sm:w-6 sm:h-6 items-center justify-center text-white rounded-full shadow-lg"
          :class="getMarkerClass(slotProps.item.status)"
        >
          <i :class="slotProps.item.icon" class="text-xs"></i>
        </div>
      </template>

      <template #content="slotProps">
        <Card class="mt-3 sm:mt-4">
          <template #content>
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-3 sm:gap-0">
              <div class="flex-1 min-w-0">
                <h4 class="font-medium text-surface-900 dark:text-surface-50 mb-2 text-sm sm:text-base">
                  {{ slotProps.item.title }}
                </h4>
                <p class="text-surface-600 dark:text-surface-400 text-xs sm:text-sm mb-2">
                  {{ slotProps.item.description }}
                </p>

                <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
                  <div v-if="slotProps.item.date" class="flex items-center gap-1 text-xs sm:text-sm">
                    <i class="pi pi-calendar text-surface-400 text-xs"></i>
                    <span class="text-surface-700 dark:text-surface-300">
                      {{ formatDate(slotProps.item.date) }}
                    </span>
                  </div>

                  <div v-if="slotProps.item.delai" class="flex items-center gap-1 text-xs sm:text-sm">
                    <i class="pi pi-clock text-surface-400 text-xs"></i>
                    <span :class="getDelaiClass(slotProps.item.delaiStatus)">
                      {{ slotProps.item.delai }}j
                    </span>
                  </div>
                </div>
              </div>

              <!-- Actions -->
              <div v-if="canEdit && slotProps.item.canEdit" class="flex-shrink-0 self-start sm:ml-4">
                <Button
                  v-if="!slotProps.item.date"
                  icon="pi pi-plus"
                  size="small"
                  @click="openDateDialog(slotProps.item.key, slotProps.item.title)"
                  v-tooltip="'Ajouter la date'"
                />
                <Button
                  v-else
                  icon="pi pi-pencil"
                  size="small"
                  severity="secondary"
                  @click="openDateDialog(slotProps.item.key, slotProps.item.title, slotProps.item.date)"
                  v-tooltip="'Modifier la date'"
                />
              </div>
            </div>
          </template>
        </Card>
      </template>
    </Timeline>

    <!-- Dialog d'édition de date -->
    <Dialog
      v-model:visible="showDateDialog"
      :modal="true"
      :closable="true"
      :header="dateDialogTitle"
      class="w-full max-w-md mx-4 sm:mx-0"
    >
      <div class="space-y-4">
        <div class="space-y-2">
          <label class="text-sm font-medium text-surface-700 dark:text-surface-300">
            Date et heure
          </label>
          <DatePicker
            v-model="editingDate"
            showTime
            :showIcon="true"
            iconDisplay="input"
            placeholder="Sélectionner une date"
            class="w-full"
          />
        </div>

        <div class="space-y-2">
          <label class="text-sm font-medium text-surface-700 dark:text-surface-300">
            Notes (optionnel)
          </label>
          <Textarea
            v-model="editingNotes"
            rows="3"
            placeholder="Remarques, observations..."
            class="w-full"
          />
        </div>
      </div>

      <template #footer>
        <div class="flex gap-2 justify-end">
          <Button
            label="Annuler"
            severity="secondary"
            @click="closeeDateDialog"
          />
          <Button
            label="Enregistrer"
            @click="saveDate"
            :loading="savingDate"
          />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
  suiviProcessus: {
    type: Object,
    default: () => ({})
  },
  interventionId: {
    type: [String, Number],
    required: true
  }
})

const emit = defineEmits(['timeline-updated'])

const { $api } = useNuxtApp()
const toast = useToast()
const authStore = useAuthStore()

// État réactif
const delaisData = ref(null)
const loadingDelais = ref(false)
const showDateDialog = ref(false)
const dateDialogTitle = ref('')
const editingDateKey = ref('')
const editingDate = ref(null)
const editingNotes = ref('')
const savingDate = ref(false)

// Permissions
const canEdit = computed(() => {
  return authStore.hasPermission('manager') || authStore.hasPermission('admin')
})

// Configuration des étapes du processus
const processSteps = [
  {
    key: 'date_reception_dossier',
    title: 'Réception du dossier',
    description: 'Dossier reçu et enregistré dans le système Enedis',
    icon: 'pi pi-inbox',
    canEdit: true
  },
  {
    key: 'date_etude_technique',
    title: 'Étude technique',
    description: 'Validation technique du projet et dimensionnement',
    icon: 'pi pi-search',
    canEdit: true
  },
  {
    key: 'date_validation_devis',
    title: 'Validation du devis',
    description: 'Accord client sur le devis proposé',
    icon: 'pi pi-check-circle',
    canEdit: true
  },
  {
    key: 'date_realisation_terrassement',
    title: 'Réalisation terrassement',
    description: 'Travaux de terrassement effectués',
    icon: 'pi pi-arrow-down-right',
    canEdit: true
  },
  {
    key: 'date_realisation_cablage',
    title: 'Réalisation câblage',
    description: 'Installation électrique et raccordement',
    icon: 'pi pi-bolt',
    canEdit: true
  },
  {
    key: 'date_mise_en_service',
    title: 'Mise en service',
    description: 'Tests finaux et activation du branchement',
    icon: 'pi pi-power-off',
    canEdit: true
  }
]

// Timeline events calculés
const timelineEvents = computed(() => {
  if (!props.suiviProcessus) return []

  return processSteps.map((step, index) => {
    const date = props.suiviProcessus[step.key]
    const previousDate = index > 0 ? props.suiviProcessus[processSteps[index - 1].key] : null

    let delai = null
    let delaiStatus = null

    if (date && previousDate) {
      const diff = Math.floor((new Date(date) - new Date(previousDate)) / (1000 * 60 * 60 * 24))
      delai = diff

      // Définir des seuils de délais par étape
      const seuils = {
        date_etude_technique: { ok: 5, alerte: 10 },
        date_validation_devis: { ok: 7, alerte: 14 },
        date_realisation_terrassement: { ok: 10, alerte: 20 },
        date_realisation_cablage: { ok: 3, alerte: 7 },
        date_mise_en_service: { ok: 2, alerte: 5 }
      }

      const seuil = seuils[step.key]
      if (seuil) {
        if (delai <= seuil.ok) delaiStatus = 'OK'
        else if (delai <= seuil.alerte) delaiStatus = 'ALERTE'
        else delaiStatus = 'DEPASSEMENT'
      }
    }

    return {
      ...step,
      date,
      delai,
      delaiStatus,
      status: date ? 'completed' : 'pending'
    }
  })
})

/**
 * Charge les données de délais depuis l'API
 */
const loadDelais = async () => {
  loadingDelais.value = true

  try {
    const response = await $api.get(`/intervention_electrique.php?action=delais&id=${props.interventionId}`)

    if (response.success) {
      delaisData.value = response.data
    }
  } catch (err) {
    console.error('Erreur chargement délais:', err)
  } finally {
    loadingDelais.value = false
  }
}

/**
 * Ouvre le dialog d'édition de date
 */
const openDateDialog = (key, title, currentDate = null) => {
  editingDateKey.value = key
  dateDialogTitle.value = title
  editingDate.value = currentDate ? new Date(currentDate) : new Date()
  editingNotes.value = ''
  showDateDialog.value = true
}

/**
 * Ferme le dialog d'édition
 */
const closeeDateDialog = () => {
  showDateDialog.value = false
  editingDateKey.value = ''
  editingDate.value = null
  editingNotes.value = ''
}

/**
 * Sauvegarde la date modifiée
 */
const saveDate = async () => {
  if (!editingDate.value) return

  savingDate.value = true

  try {
    const updateData = {
      [editingDateKey.value]: editingDate.value.toISOString().slice(0, 19).replace('T', ' ')
    }

    const response = await $api.put('/intervention_electrique.php', {
      id: props.interventionId,
      ...updateData
    })

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Date mise à jour',
        detail: 'La date a été enregistrée avec succès',
        life: 3000
      })

      emit('timeline-updated')
      closeeDateDialog()
      await loadDelais() // Recharger les délais
    } else {
      throw new Error(response.message)
    }
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: err.message,
      life: 5000
    })
  } finally {
    savingDate.value = false
  }
}

/**
 * Utilitaires d'affichage
 */
const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getMarkerClass = (status) => {
  switch (status) {
    case 'completed': return 'bg-primary-500'
    case 'in_progress': return 'bg-yellow-500'
    case 'pending': return 'bg-surface-400'
    default: return 'bg-surface-300'
  }
}

const getDelaiSeverity = (status) => {
  switch (status) {
    case 'OK': return 'success'
    case 'ALERTE': return 'warning'
    case 'DEPASSEMENT': return 'danger'
    default: return 'secondary'
  }
}

const getDelaiClass = (status) => {
  switch (status) {
    case 'OK': return 'text-green-600 dark:text-green-400'
    case 'ALERTE': return 'text-orange-600 dark:text-orange-400'
    case 'DEPASSEMENT': return 'text-red-600 dark:text-red-400'
    default: return 'text-surface-600 dark:text-surface-400'
  }
}

const getPhaseActuelleLabel = (phase) => {
  const labels = {
    'RECEPTION': 'Réception',
    'ETUDE_TECHNIQUE': 'Étude technique',
    'VALIDATION_DEVIS': 'Validation devis',
    'TERRASSEMENT': 'Terrassement',
    'CABLAGE': 'Câblage',
    'MISE_EN_SERVICE': 'Mise en service',
    'TERMINEE': 'Terminée'
  }
  return labels[phase] || phase
}

// Charger les délais au montage et quand l'intervention change
onMounted(() => {
  loadDelais()
})

watch(() => props.interventionId, () => {
  if (props.interventionId) {
    loadDelais()
  }
})
</script>

<style scoped>
.process-timeline {
  width: 100%;
}

/* Personnalisation de la timeline */
:deep(.p-timeline) {
  padding: 0;
}

:deep(.p-timeline-event-content) {
  padding-left: 1rem;
}

:deep(.p-timeline-event-connector) {
  background-color: rgb(229 231 235);
}

:deep(.dark .p-timeline-event-connector) {
  background-color: rgb(75 85 99);
}

/* Animation des marqueurs */
:deep(.p-timeline-event-marker) {
  transition: all 0.3s ease;
}

:deep(.p-timeline-event-marker:hover) {
  transform: scale(1.1);
}

/* Responsive pour mobile */
@media (max-width: 640px) {
  .process-timeline {
    padding: 0;
  }

  :deep(.p-timeline-event-content) {
    padding-left: 0.5rem;
  }

  :deep(.p-timeline-event-marker) {
    width: 1.25rem;
    height: 1.25rem;
  }

  :deep(.p-card-content) {
    padding: 0.75rem;
  }
}

/* Très petits écrans */
@media (max-width: 480px) {
  :deep(.p-timeline-event-content) {
    padding-left: 0.25rem;
  }

  :deep(.p-card-content) {
    padding: 0.5rem;
  }

  :deep(.p-timeline-event-connector) {
    width: 2px;
  }
}
</style>