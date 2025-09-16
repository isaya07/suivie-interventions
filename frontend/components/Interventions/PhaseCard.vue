<!--
  Composant de gestion d'une phase de branchement électrique

  Fonctionnalités :
  - Interface de contrôle du chronomètre (démarrage/arrêt)
  - Assignation et gestion des techniciens
  - Affichage des métriques de temps et coût
  - Gestion des notes et commentaires
  - Interface PrimeVue responsive avec indicateurs visuels
-->
<template>
  <Card class="phase-card" :class="getPhaseCardClass()">
    <template #header>
      <div class="flex justify-between items-center p-4 border-b border-surface-200 dark:border-surface-700">
        <div class="flex items-center gap-3">
          <Avatar
            :icon="getPhaseIcon()"
            :class="getPhaseAvatarClass()"
            size="large"
            shape="circle"
          />
          <div>
            <h3 class="text-lg font-semibold text-surface-900 dark:text-surface-50 capitalize">
              Phase {{ phaseNumber }}: {{ phaseLabel }}
            </h3>
            <Badge
              :value="getStatutLabel(phaseData.statut)"
              :severity="getStatutSeverity(phaseData.statut)"
              class="mt-1"
            />
          </div>
        </div>

        <!-- Chronomètre en temps réel -->
        <div v-if="isRunning && currentPhase === phase" class="text-right">
          <div class="text-2xl font-mono font-bold text-primary-600 dark:text-primary-400">
            {{ formattedElapsedTime }}
          </div>
          <div class="text-sm text-surface-600 dark:text-surface-400">
            Session en cours
          </div>
        </div>
      </div>
    </template>

    <template #content>
      <div class="space-y-4">
        <!-- Assignation du technicien -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-2">
            <label class="text-sm font-medium text-surface-700 dark:text-surface-300">
              Technicien assigné
            </label>
            <Dropdown
              v-model="selectedTechnicien"
              :options="techniciens"
              option-label="nom_complet"
              option-value="id"
              placeholder="Sélectionner un technicien"
              :disabled="phaseData.statut === 'terminee' || phaseData.statut === 'annulee'"
              class="w-full"
              @change="updateTechnicien"
            />
          </div>

          <div class="space-y-2">
            <label class="text-sm font-medium text-surface-700 dark:text-surface-300">
              Taux horaire
            </label>
            <InputText
              :model-value="formatCurrency(phaseData.taux_horaire)"
              readonly
              class="w-full"
            />
          </div>
        </div>

        <!-- Métriques de temps et coût -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="text-center p-3 bg-surface-50 dark:bg-surface-800 rounded-lg">
            <div class="text-xl font-bold text-surface-900 dark:text-surface-50">
              {{ formatDuration(getTotalDuration(phase) * 3600) }}
            </div>
            <div class="text-xs text-surface-600 dark:text-surface-400">
              Temps total
            </div>
          </div>

          <div class="text-center p-3 bg-surface-50 dark:bg-surface-800 rounded-lg">
            <div class="text-xl font-bold text-surface-900 dark:text-surface-50">
              {{ formatCurrency(phaseData.cout) }}
            </div>
            <div class="text-xs text-surface-600 dark:text-surface-400">
              Coût réel
            </div>
          </div>

          <div class="text-center p-3 bg-surface-50 dark:bg-surface-800 rounded-lg">
            <div class="text-xl font-bold text-surface-900 dark:text-surface-50">
              {{ getSessionCount(phase) }}
            </div>
            <div class="text-xs text-surface-600 dark:text-surface-400">
              Sessions
            </div>
          </div>

          <div class="text-center p-3 bg-surface-50 dark:bg-surface-800 rounded-lg">
            <div class="text-xl font-bold text-surface-900 dark:text-surface-50">
              {{ getEstimatedDuration() }}h
            </div>
            <div class="text-xs text-surface-600 dark:text-surface-400">
              Estimé
            </div>
          </div>
        </div>

        <!-- Contrôles du chronomètre -->
        <div class="flex gap-3 justify-center">
          <Button
            v-if="!isRunning || currentPhase !== phase"
            :label="getStartButtonLabel()"
            icon="pi pi-play"
            :severity="getStartButtonSeverity()"
            :disabled="!canStart()"
            @click="showStartDialog = true"
          />

          <Button
            v-if="isRunning && currentPhase === phase"
            label="Arrêter la session"
            icon="pi pi-stop"
            severity="danger"
            @click="showStopDialog = true"
          />

          <Button
            v-if="phaseData.statut === 'en_cours' && (!isRunning || currentPhase !== phase)"
            label="Terminer la phase"
            icon="pi pi-check"
            severity="success"
            @click="completePhase"
          />
        </div>

        <!-- Notes de la phase -->
        <div class="space-y-2">
          <label class="text-sm font-medium text-surface-700 dark:text-surface-300">
            Notes de la phase
          </label>
          <Textarea
            v-model="phaseNotes"
            rows="3"
            placeholder="Ajoutez des notes pour cette phase..."
            class="w-full"
            @blur="updateNotes"
          />
        </div>
      </div>
    </template>

    <!-- Dialog de démarrage de session -->
    <Dialog
      v-model:visible="showStartDialog"
      :modal="true"
      :closable="true"
      header="Démarrer une session de travail"
      class="w-full max-w-md"
    >
      <div class="space-y-4">
        <div class="space-y-2">
          <label class="text-sm font-medium text-surface-700 dark:text-surface-300">
            Technicien
          </label>
          <Dropdown
            v-model="sessionTechnicien"
            :options="techniciens"
            option-label="nom_complet"
            option-value="id"
            placeholder="Sélectionner un technicien"
            class="w-full"
          />
        </div>

        <div class="space-y-2">
          <label class="text-sm font-medium text-surface-700 dark:text-surface-300">
            Notes de démarrage (optionnel)
          </label>
          <Textarea
            v-model="sessionStartNotes"
            rows="3"
            placeholder="Notes pour cette session..."
            class="w-full"
          />
        </div>
      </div>

      <template #footer>
        <div class="flex gap-2 justify-end">
          <Button
            label="Annuler"
            severity="secondary"
            @click="showStartDialog = false"
          />
          <Button
            label="Démarrer"
            icon="pi pi-play"
            severity="success"
            :disabled="!sessionTechnicien"
            @click="startSession"
          />
        </div>
      </template>
    </Dialog>

    <!-- Dialog d'arrêt de session -->
    <Dialog
      v-model:visible="showStopDialog"
      :modal="true"
      :closable="true"
      header="Arrêter la session de travail"
      class="w-full max-w-md"
    >
      <div class="space-y-4">
        <p class="text-surface-700 dark:text-surface-300">
          Durée de la session : <strong>{{ formattedElapsedTime }}</strong>
        </p>

        <div class="space-y-2">
          <label class="text-sm font-medium text-surface-700 dark:text-surface-300">
            Notes finales (optionnel)
          </label>
          <Textarea
            v-model="sessionStopNotes"
            rows="3"
            placeholder="Résumé du travail effectué..."
            class="w-full"
          />
        </div>
      </div>

      <template #footer>
        <div class="flex gap-2 justify-end">
          <Button
            label="Continuer"
            severity="secondary"
            @click="showStopDialog = false"
          />
          <Button
            label="Arrêter"
            icon="pi pi-stop"
            severity="danger"
            @click="stopSession"
          />
        </div>
      </template>
    </Dialog>
  </Card>
</template>

<script setup>
/**
 * Composant de gestion d'une phase de branchement électrique
 *
 * Props:
 * @param {string|number} interventionId - ID de l'intervention
 * @param {string} phase - Type de phase ('terrassement' ou 'branchement')
 * @param {object} phaseData - Données de la phase
 * @param {array} techniciens - Liste des techniciens disponibles
 * @param {boolean} isActive - Si la phase peut être démarrée
 *
 * Événements émis:
 * @event phase-updated - Émis quand la phase est mise à jour
 */

import { ref, computed, watch, onMounted } from 'vue'
import { useTimeTracking } from '@/composables/useTimeTracking'
import { useToast } from 'primevue/usetoast'

const props = defineProps({
  interventionId: {
    type: [String, Number],
    required: true
  },
  phase: {
    type: String,
    required: true,
    validator: value => ['terrassement', 'branchement'].includes(value)
  },
  phaseData: {
    type: Object,
    required: true
  },
  techniciens: {
    type: Array,
    default: () => []
  },
  isActive: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['phase-updated'])

const { $api } = useNuxtApp()
const toast = useToast()

// Composable de gestion du temps
const {
  isRunning,
  currentPhase,
  elapsedTime,
  formattedElapsedTime,
  sessionHistory,
  startTimer,
  stopTimer,
  getTotalDuration,
  formatDuration
} = useTimeTracking(props.interventionId)

// État réactif local
const selectedTechnicien = ref(props.phaseData.technicien_id)
const phaseNotes = ref(props.phaseData.notes || '')
const showStartDialog = ref(false)
const showStopDialog = ref(false)
const sessionTechnicien = ref(null)
const sessionStartNotes = ref('')
const sessionStopNotes = ref('')

// Propriétés calculées
const phaseNumber = computed(() => props.phase === 'terrassement' ? '1' : '2')

const phaseLabel = computed(() => {
  return props.phase === 'terrassement' ? 'Terrassement' : 'Branchement électrique'
})

/**
 * Détermine si la phase peut être démarrée
 */
const canStart = () => {
  return props.isActive &&
         props.phaseData.statut === 'en_attente' &&
         !isRunning.value &&
         props.techniciens.length > 0
}

/**
 * Obtient le libellé du bouton de démarrage
 */
const getStartButtonLabel = () => {
  if (props.phaseData.statut === 'terminee') return 'Phase terminée'
  if (props.phaseData.statut === 'annulee') return 'Phase annulée'
  if (props.phaseData.statut === 'en_cours') return 'Reprendre session'
  return 'Démarrer session'
}

/**
 * Obtient la severity du bouton de démarrage
 */
const getStartButtonSeverity = () => {
  if (props.phaseData.statut === 'en_cours') return 'info'
  return 'success'
}

/**
 * Retourne l'icône de la phase
 */
const getPhaseIcon = () => {
  return props.phase === 'terrassement' ? 'pi pi-home' : 'pi pi-bolt'
}

/**
 * Retourne les classes CSS de l'avatar de phase
 */
const getPhaseAvatarClass = () => {
  const baseClass = 'text-white'

  if (props.phase === 'terrassement') {
    return `${baseClass} bg-orange-500`
  }
  return `${baseClass} bg-blue-500`
}

/**
 * Retourne les classes CSS de la carte de phase
 */
const getPhaseCardClass = () => {
  if (isRunning.value && currentPhase.value === props.phase) {
    return 'border-2 border-primary-400 shadow-lg'
  }

  if (props.phaseData.statut === 'terminee') {
    return 'border-l-4 border-green-400'
  }

  if (props.phaseData.statut === 'en_cours') {
    return 'border-l-4 border-blue-400'
  }

  if (props.phaseData.statut === 'annulee') {
    return 'border-l-4 border-red-400'
  }

  return 'border-l-4 border-surface-300 dark:border-surface-600'
}

/**
 * Retourne le libellé du statut
 */
const getStatutLabel = (statut) => {
  const labels = {
    'en_attente': 'En attente',
    'en_cours': 'En cours',
    'terminee': 'Terminée',
    'annulee': 'Annulée',
    'non_applicable': 'Non applicable'
  }
  return labels[statut] || statut
}

/**
 * Retourne la severity PrimeVue du statut
 */
const getStatutSeverity = (statut) => {
  const severities = {
    'en_attente': 'warning',
    'en_cours': 'info',
    'terminee': 'success',
    'annulee': 'danger',
    'non_applicable': 'secondary'
  }
  return severities[statut] || 'secondary'
}

/**
 * Compte le nombre de sessions pour cette phase
 */
const getSessionCount = (phase) => {
  return sessionHistory.value.filter(s => s.phase === phase).length
}

/**
 * Retourne la durée estimée pour cette phase
 */
const getEstimatedDuration = () => {
  // Cette valeur pourrait venir du type de prestation
  if (props.phase === 'terrassement') return 4
  return 4 // branchement
}

/**
 * Démarre une session de travail
 */
const startSession = async () => {
  try {
    await startTimer(props.phase, sessionTechnicien.value, sessionStartNotes.value)

    toast.add({
      severity: 'success',
      summary: 'Session démarrée',
      detail: `Session de ${phaseLabel.value.toLowerCase()} démarrée avec succès`,
      life: 3000
    })

    // Réinitialiser et fermer le dialog
    sessionStartNotes.value = ''
    showStartDialog.value = false

    emit('phase-updated')
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
 * Arrête la session de travail en cours
 */
const stopSession = async () => {
  try {
    await stopTimer(sessionStopNotes.value)

    toast.add({
      severity: 'success',
      summary: 'Session arrêtée',
      detail: `Session de ${phaseLabel.value.toLowerCase()} arrêtée avec succès`,
      life: 3000
    })

    // Réinitialiser et fermer le dialog
    sessionStopNotes.value = ''
    showStopDialog.value = false

    emit('phase-updated')
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
 * Termine définitivement la phase
 */
const completePhase = async () => {
  try {
    const response = await $api.post('/intervention_electrique.php?action=terminer_phase', {
      intervention_id: props.interventionId,
      phase: props.phase
    })

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Phase terminée',
        detail: `Phase ${phaseLabel.value.toLowerCase()} terminée avec succès`,
        life: 3000
      })

      emit('phase-updated')
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
 * Met à jour le technicien assigné
 */
const updateTechnicien = async () => {
  try {
    const response = await $api.put('/intervention_electrique.php', {
      intervention_id: props.interventionId,
      [`technicien_${props.phase}_id`]: selectedTechnicien.value
    })

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Technicien mis à jour',
        detail: 'Technicien assigné avec succès',
        life: 3000
      })

      emit('phase-updated')
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

    // Restaurer la valeur précédente
    selectedTechnicien.value = props.phaseData.technicien_id
  }
}

/**
 * Met à jour les notes de la phase
 */
const updateNotes = async () => {
  try {
    const response = await $api.put('/intervention_electrique.php', {
      intervention_id: props.interventionId,
      [`phase_${props.phase}_notes`]: phaseNotes.value
    })

    if (!response.success) {
      throw new Error(response.message)
    }
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de sauvegarder les notes',
      life: 5000
    })
  }
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

// Watchers pour synchroniser les props
watch(() => props.phaseData.technicien_id, (newValue) => {
  selectedTechnicien.value = newValue
})

watch(() => props.phaseData.notes, (newValue) => {
  phaseNotes.value = newValue || ''
})

// Initialisation
onMounted(() => {
  // Pré-sélectionner le technicien si disponible
  if (props.phaseData.technicien_id) {
    sessionTechnicien.value = props.phaseData.technicien_id
  }
})
</script>

<style scoped>
/**
 * Styles CSS personnalisés pour PhaseCard
 */

.phase-card {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 300ms;
}

/* Animation pour les métriques */
.metric-card {
  transition-property: transform;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 200ms;
}

.metric-card:hover {
  transform: scale(1.05);
}

/* Indicateur de session active */
.active-session {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Style pour les boutons de contrôle */
.control-buttons {
  gap: 0.5rem;
}

@media (max-width: 768px) {
  .control-buttons {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }
}
</style>