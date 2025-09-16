<!--
  Composant principal pour la gestion des branchements électriques

  Fonctionnalités :
  - Gestion des deux phases (terrassement puis branchement)
  - Chronomètre en temps réel pour chaque phase
  - Assignation des techniciens par spécialité
  - Calcul automatique des coûts et durées
  - Interface PrimeVue responsive avec gestion du mode sombre
-->
<template>
  <div class="branchement-electrique">
    <!-- En-tête avec informations de base -->
    <Card class="mb-6">
      <template #header>
        <div class="flex justify-between items-center p-4 border-b border-surface-200 dark:border-surface-700">
          <div>
            <h2 class="text-2xl font-bold text-surface-900 dark:text-surface-50">
              Branchement Électrique #{{ intervention?.numero }}
            </h2>
            <p class="text-surface-600 dark:text-surface-400 mt-1">
              {{ intervention?.type_prestation_nom }}
            </p>
          </div>
          <Badge
            :value="getStatutGlobal()"
            :severity="getStatutSeverity(getStatutGlobal())"
            class="text-sm font-medium"
          />
        </div>
      </template>

      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Informations client -->
          <div class="space-y-2">
            <label class="text-sm font-medium text-surface-700 dark:text-surface-300">Client</label>
            <p class="text-surface-900 dark:text-surface-50">{{ intervention?.client_nom }}</p>
          </div>

          <!-- Date d'intervention prévue -->
          <div class="space-y-2">
            <label class="text-sm font-medium text-surface-700 dark:text-surface-300">Date prévue</label>
            <p class="text-surface-900 dark:text-surface-50">
              {{ formatDate(intervention?.date_intervention) }}
            </p>
          </div>

          <!-- Coût estimé -->
          <div class="space-y-2">
            <label class="text-sm font-medium text-surface-700 dark:text-surface-300">Coût estimé</label>
            <p class="text-surface-900 dark:text-surface-50 font-medium">
              {{ formatCurrency(intervention?.cout_total_estime) }}
            </p>
          </div>

          <!-- Coût réel -->
          <div class="space-y-2">
            <label class="text-sm font-medium text-surface-700 dark:text-surface-300">Coût réel</label>
            <p class="text-surface-900 dark:text-surface-50 font-medium"
               :class="getCoutClass(intervention?.ecart_pourcentage)">
              {{ formatCurrency(intervention?.cout_total_reel) }}
              <span v-if="intervention?.ecart_pourcentage" class="text-sm ml-1">
                ({{ intervention.ecart_pourcentage > 0 ? '+' : '' }}{{ intervention.ecart_pourcentage }}%)
              </span>
            </p>
          </div>
        </div>
      </template>
    </Card>

    <!-- Gestion des phases -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
      <!-- Phase 1: Terrassement -->
      <PhaseCard
        v-if="hasPhase('terrassement')"
        :intervention-id="interventionId"
        phase="terrassement"
        :phase-data="getPhaseData('terrassement')"
        :techniciens="terrassiers"
        :is-active="canStartPhase('terrassement')"
        @phase-updated="loadIntervention"
      />

      <!-- Phase 2: Branchement -->
      <PhaseCard
        :intervention-id="interventionId"
        phase="branchement"
        :phase-data="getPhaseData('branchement')"
        :techniciens="cableurs"
        :is-active="canStartPhase('branchement')"
        @phase-updated="loadIntervention"
      />
    </div>

    <!-- Historique des sessions -->
    <Card class="mb-6">
      <template #header>
        <div class="p-4 border-b border-surface-200 dark:border-surface-700">
          <h3 class="text-lg font-semibold text-surface-900 dark:text-surface-50">
            Historique des sessions de travail
          </h3>
        </div>
      </template>

      <template #content>
        <SessionHistory
          :sessions="sessionHistory"
          @refresh="loadSessionHistory"
        />
      </template>
    </Card>

    <!-- Statistiques et résumé -->
    <Card>
      <template #header>
        <div class="p-4 border-b border-surface-200 dark:border-surface-700">
          <h3 class="text-lg font-semibold text-surface-900 dark:text-surface-50">
            Résumé financier
          </h3>
        </div>
      </template>

      <template #content>
        <InterventionSummary
          :intervention="intervention"
          :total-terrassement-duration="totalTerrassementDuration"
          :total-branchement-duration="totalBranchementDuration"
        />
      </template>
    </Card>
  </div>
</template>

<script setup>
/**
 * Composant principal pour la gestion des branchements électriques
 *
 * Props:
 * @param {string|number} interventionId - ID de l'intervention à gérer
 *
 * Événements émis:
 * @event intervention-updated - Émis quand l'intervention est mise à jour
 */

import { ref, computed, onMounted } from 'vue'
import { useTimeTracking } from '@/composables/useTimeTracking'

// Composants enfants
import PhaseCard from './PhaseCard.vue'
import SessionHistory from './SessionHistory.vue'
import InterventionSummary from './InterventionSummary.vue'

const props = defineProps({
  interventionId: {
    type: [String, Number],
    required: true
  }
})

const emit = defineEmits(['intervention-updated'])

const { $api } = useNuxtApp()

// État réactif
const intervention = ref(null)
const techniciens = ref([])
const loading = ref(false)
const error = ref(null)

// Composable de gestion du temps
const {
  sessionHistory,
  loadSessionHistory,
  totalTerrassementDuration,
  totalBranchementDuration
} = useTimeTracking(props.interventionId)

// Techniciens filtrés par spécialité
const terrassiers = computed(() =>
  techniciens.value.filter(t => t.specialite_principale === 'terrassier')
)

const cableurs = computed(() =>
  techniciens.value.filter(t => t.specialite_principale === 'cableur')
)

/**
 * Charge les données de l'intervention depuis l'API
 */
const loadIntervention = async () => {
  loading.value = true
  error.value = null

  try {
    const response = await $api.get(`/intervention_electrique.php?id=${props.interventionId}`)

    if (response.success) {
      intervention.value = response.data
      emit('intervention-updated', intervention.value)
    } else {
      throw new Error(response.message || 'Erreur lors du chargement de l\'intervention')
    }
  } catch (err) {
    error.value = err.message
    console.error('Erreur loadIntervention:', err)
  } finally {
    loading.value = false
  }
}

/**
 * Charge la liste des techniciens disponibles
 */
const loadTechniciens = async () => {
  try {
    const response = await $api.get('/users.php?action=list&role=technicien')

    if (response.success) {
      techniciens.value = response.users || []
    }
  } catch (err) {
    console.error('Erreur loadTechniciens:', err)
    techniciens.value = []
  }
}

/**
 * Détermine si l'intervention a une phase donnée
 *
 * @param {string} phase - 'terrassement' ou 'branchement'
 * @returns {boolean}
 */
const hasPhase = (phase) => {
  if (phase === 'terrassement') {
    return intervention.value?.has_terrassement === true
  }
  return true // Le branchement est toujours présent
}

/**
 * Récupère les données d'une phase spécifique
 *
 * @param {string} phase - 'terrassement' ou 'branchement'
 * @returns {object} Données de la phase
 */
const getPhaseData = (phase) => {
  if (!intervention.value) return {}

  const prefix = `phase_${phase}_`

  return {
    statut: intervention.value[`${prefix}statut`],
    technicien_id: intervention.value[`${prefix}technicien_id`],
    technicien_nom: intervention.value[`technicien_${phase}_nom`],
    taux_horaire: intervention.value[`${prefix}taux_horaire`],
    date_debut: intervention.value[`${prefix}date_debut`],
    date_fin: intervention.value[`${prefix}date_fin`],
    duree_heures: intervention.value[`${prefix}duree_heures`],
    cout: intervention.value[`${prefix}cout`],
    notes: intervention.value[`${prefix}notes`]
  }
}

/**
 * Détermine si une phase peut être démarrée
 *
 * @param {string} phase - 'terrassement' ou 'branchement'
 * @returns {boolean}
 */
const canStartPhase = (phase) => {
  if (!intervention.value) return false

  if (phase === 'terrassement') {
    // Le terrassement peut démarrer si l'intervention est en attente
    return intervention.value.phase_terrassement_statut === 'en_attente'
  }

  if (phase === 'branchement') {
    // Le branchement peut démarrer si :
    // - Il n'y a pas de terrassement OU le terrassement est terminé
    // - ET le branchement est en attente
    const terrassementOk = !hasPhase('terrassement') ||
                          intervention.value.phase_terrassement_statut === 'terminee'

    return terrassementOk && intervention.value.phase_branchement_statut === 'en_attente'
  }

  return false
}

/**
 * Calcule le statut global de l'intervention
 *
 * @returns {string} Statut global
 */
const getStatutGlobal = () => {
  if (!intervention.value) return 'En attente'

  const branchementOk = intervention.value.phase_branchement_statut === 'terminee'
  const terrassementOk = !hasPhase('terrassement') ||
                        intervention.value.phase_terrassement_statut === 'terminee'

  if (branchementOk && terrassementOk) return 'Terminée'

  if (intervention.value.phase_branchement_statut === 'en_cours' ||
      intervention.value.phase_terrassement_statut === 'en_cours') {
    return 'En cours'
  }

  if (intervention.value.phase_branchement_statut === 'annulee' ||
      intervention.value.phase_terrassement_statut === 'annulee') {
    return 'Annulée'
  }

  return 'En attente'
}

/**
 * Retourne la classe CSS pour la severity du badge de statut
 *
 * @param {string} statut - Statut à classifier
 * @returns {string} Severity PrimeVue
 */
const getStatutSeverity = (statut) => {
  switch (statut) {
    case 'Terminée': return 'success'
    case 'En cours': return 'info'
    case 'Annulée': return 'danger'
    default: return 'warning'
  }
}

/**
 * Retourne la classe CSS pour l'affichage du coût selon l'écart
 *
 * @param {number} ecartPourcentage - Écart en pourcentage
 * @returns {string} Classes CSS
 */
const getCoutClass = (ecartPourcentage) => {
  if (!ecartPourcentage) return ''

  if (ecartPourcentage > 10) return 'text-red-600 dark:text-red-400'
  if (ecartPourcentage > 0) return 'text-orange-600 dark:text-orange-400'
  if (ecartPourcentage < -5) return 'text-green-600 dark:text-green-400'

  return ''
}

/**
 * Formate une date au format français
 *
 * @param {string} dateString - Date à formater
 * @returns {string} Date formatée
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

/**
 * Formate un montant en euros
 *
 * @param {number} amount - Montant à formater
 * @returns {string} Montant formaté
 */
const formatCurrency = (amount) => {
  if (!amount) return '-'

  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR'
  }).format(amount)
}

// Initialisation au montage
onMounted(async () => {
  await Promise.all([
    loadIntervention(),
    loadTechniciens(),
    loadSessionHistory()
  ])
})
</script>

<style scoped>
/**
 * Styles CSS personnalisés pour le composant
 * Complète les classes Tailwind avec des styles spécifiques
 */

.branchement-electrique {
  max-width: 80rem;
  margin-left: auto;
  margin-right: auto;
  padding: 1rem;
}

/* Animation pour les transitions de statut */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Indicateur de coût avec couleur dynamique */
.cout-indicator {
  transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 200ms;
}

/* Responsive breakpoints personnalisés */
@media (max-width: 768px) {
  .branchement-electrique {
    padding: 0.5rem;
  }
}
</style>