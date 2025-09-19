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
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center p-3 sm:p-4 border-b border-surface-200 dark:border-surface-700 gap-3 sm:gap-0">
          <div class="flex-1 min-w-0">
            <h2 class="text-xl sm:text-2xl font-bold text-surface-900 dark:text-surface-50 truncate">
              Branchement Électrique #{{ intervention?.numero }}
            </h2>
            <p class="text-surface-600 dark:text-surface-400 mt-1 text-sm sm:text-base truncate">
              {{ intervention?.type_prestation_nom }}
            </p>
          </div>
          <div class="flex-shrink-0 self-start sm:self-center">
            <Badge
              :value="getStatutGlobal()"
              :severity="getStatutSeverity(getStatutGlobal())"
              class="text-sm font-medium"
            />
          </div>
        </div>
      </template>

      <template #content>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
          <!-- Informations client -->
          <div class="space-y-2">
            <label class="text-sm font-medium text-surface-700 dark:text-surface-300">Client</label>
            <p class="text-surface-900 dark:text-surface-50 text-sm sm:text-base break-words">{{ intervention?.client_nom }}</p>
          </div>

          <!-- Date d'intervention prévue -->
          <div class="space-y-2">
            <label class="text-sm font-medium text-surface-700 dark:text-surface-300">Date prévue</label>
            <p class="text-surface-900 dark:text-surface-50 text-sm sm:text-base">
              {{ formatDate(intervention?.date_intervention) }}
            </p>
          </div>

          <!-- Coût estimé -->
          <div class="space-y-2">
            <label class="text-sm font-medium text-surface-700 dark:text-surface-300">Coût estimé</label>
            <p class="text-surface-900 dark:text-surface-50 font-medium text-sm sm:text-base">
              {{ formatCurrency(intervention?.cout_total_estime) }}
            </p>
          </div>

          <!-- Coût réel -->
          <div class="space-y-2">
            <label class="text-sm font-medium text-surface-700 dark:text-surface-300">Coût réel</label>
            <p class="text-surface-900 dark:text-surface-50 font-medium text-sm sm:text-base"
               :class="getCoutClass(intervention?.ecart_pourcentage)">
              {{ formatCurrency(intervention?.cout_total_reel) }}
              <span v-if="intervention?.ecart_pourcentage" class="text-xs sm:text-sm ml-1 block sm:inline">
                ({{ intervention.ecart_pourcentage > 0 ? '+' : '' }}{{ intervention.ecart_pourcentage }}%)
              </span>
            </p>
          </div>
        </div>
      </template>
    </Card>

    <!-- Spécifications Enedis -->
    <Card class="mb-6">
      <template #header>
        <div class="p-3 sm:p-4 border-b border-surface-200 dark:border-surface-700">
          <h3 class="text-base sm:text-lg font-semibold text-surface-900 dark:text-surface-50">
            Spécifications du branchement Enedis
          </h3>
        </div>
      </template>

      <template #content>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
          <!-- Type réglementaire -->
          <div class="space-y-2">
            <label class="text-sm font-medium text-surface-700 dark:text-surface-300">Type réglementaire</label>
            <div class="flex flex-col sm:flex-row sm:items-center gap-2">
              <Badge
                :value="getTypeReglementaireLabel(intervention?.specifications?.type_reglementaire)"
                :severity="getTypeReglementaireSeverity(intervention?.specifications?.type_reglementaire)"
                class="text-xs sm:text-sm"
              />
              <span class="text-xs sm:text-sm text-surface-600 dark:text-surface-400">
                {{ getTypeReglementaireDescription(intervention?.specifications?.type_reglementaire) }}
              </span>
            </div>
          </div>

          <!-- Mode de pose -->
          <div class="space-y-2">
            <label class="text-sm font-medium text-surface-700 dark:text-surface-300">Mode de pose</label>
            <div class="flex items-center gap-2">
              <i :class="getModePoseIcon(intervention?.specifications?.mode_pose)"
                 class="text-base sm:text-lg text-primary-500 flex-shrink-0"></i>
              <span class="text-surface-900 dark:text-surface-50 text-sm sm:text-base">
                {{ getModePoseLabel(intervention?.specifications?.mode_pose) }}
              </span>
            </div>
          </div>

          <!-- Distance totale -->
          <div class="space-y-2">
            <label class="text-sm font-medium text-surface-700 dark:text-surface-300">Distance raccordement</label>
            <p class="text-surface-900 dark:text-surface-50 font-medium text-sm sm:text-base">
              {{ intervention?.specifications?.distance_raccordement || 0 }} m
            </p>
          </div>

          <!-- Liaison Réseau (LR) -->
          <div class="space-y-2">
            <label class="text-sm font-medium text-surface-700 dark:text-surface-300">
              Liaison Réseau (LR)
              <i class="pi pi-info-circle text-xs ml-1 text-surface-400"
                 v-tooltip="'Distance du réseau au CCPI'"></i>
            </label>
            <p class="text-surface-900 dark:text-surface-50 text-sm sm:text-base">
              {{ intervention?.specifications?.longueur_liaison_reseau || 0 }} m
            </p>
          </div>

          <!-- Dérivation Individuelle (DI) -->
          <div class="space-y-2">
            <label class="text-sm font-medium text-surface-700 dark:text-surface-300">
              Dérivation Individuelle (DI)
              <i class="pi pi-info-circle text-xs ml-1 text-surface-400"
                 v-tooltip="'Distance du CCPI au tableau électrique'"></i>
            </label>
            <p class="text-surface-900 dark:text-surface-50 text-sm sm:text-base">
              {{ intervention?.specifications?.longueur_derivation_individuelle || 0 }} m
              <span v-if="intervention?.specifications?.type_reglementaire === 'type_1' && intervention?.specifications?.longueur_derivation_individuelle > 30"
                    class="text-orange-600 dark:text-orange-400 text-xs ml-1 block sm:inline">
                (> 30m : devrait être Type 2)
              </span>
            </p>
          </div>

          <!-- Critère Type 1/2 -->
          <div class="space-y-2 sm:col-span-2 lg:col-span-1">
            <label class="text-sm font-medium text-surface-700 dark:text-surface-300">Critère type</label>
            <p class="text-surface-600 dark:text-surface-400 text-xs sm:text-sm">
              {{ getCritereType(intervention?.specifications) }}
            </p>
          </div>
        </div>
      </template>
    </Card>

    <!-- Timeline du processus -->
    <Card class="mb-6">
      <template #header>
        <div class="p-3 sm:p-4 border-b border-surface-200 dark:border-surface-700">
          <h3 class="text-base sm:text-lg font-semibold text-surface-900 dark:text-surface-50">
            Suivi du processus Enedis
          </h3>
        </div>
      </template>

      <template #content>
        <ProcessTimeline
          :suivi-processus="intervention?.suivi_processus"
          :intervention-id="interventionId"
          @timeline-updated="loadIntervention"
        />
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

    <!-- Timeline du processus d'intervention -->
    <Card class="mb-6">
      <template #header>
        <div class="p-3 sm:p-4 border-b border-surface-200 dark:border-surface-700">
          <h3 class="text-base sm:text-lg font-semibold text-surface-900 dark:text-surface-50 flex items-center">
            <i class="pi pi-sitemap mr-2 text-blue-600"></i>
            Suivi du processus d'intervention
          </h3>
        </div>
      </template>

      <template #content>
        <div class="p-4">
          <TimelineComponent
            :etapes="timelineSteps"
            :vertical="true"
            :show-summary="true"
            @action="handleTimelineAction"
          />
        </div>
      </template>
    </Card>

    <!-- Historique des sessions -->
    <Card class="mb-6">
      <template #header>
        <div class="p-3 sm:p-4 border-b border-surface-200 dark:border-surface-700">
          <h3 class="text-base sm:text-lg font-semibold text-surface-900 dark:text-surface-50">
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
        <div class="p-3 sm:p-4 border-b border-surface-200 dark:border-surface-700">
          <h3 class="text-base sm:text-lg font-semibold text-surface-900 dark:text-surface-50">
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
import ProcessTimeline from './ProcessTimeline.vue'

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

/**
 * Méthodes pour les spécifications Enedis
 */

/**
 * Retourne le label du type réglementaire
 */
const getTypeReglementaireLabel = (type) => {
  switch (type) {
    case 'type_1': return 'Type 1'
    case 'type_2': return 'Type 2'
    default: return 'Non défini'
  }
}

/**
 * Retourne la severity du badge pour le type réglementaire
 */
const getTypeReglementaireSeverity = (type) => {
  switch (type) {
    case 'type_1': return 'info'
    case 'type_2': return 'warning'
    default: return 'secondary'
  }
}

/**
 * Retourne la description du type réglementaire
 */
const getTypeReglementaireDescription = (type) => {
  switch (type) {
    case 'type_1': return 'DI ≤ 30m - Compteur intérieur'
    case 'type_2': return 'DI > 30m - Coffret limite propriété'
    default: return ''
  }
}

/**
 * Génère les étapes de la timeline pour le processus d'intervention
 */
const timelineSteps = computed(() => {
  if (!intervention.value) return []

  const steps = []

  // 1. Réception du dossier
  steps.push({
    id: 'reception',
    titre: 'Réception dossier',
    description: 'Dossier client reçu et analysé',
    icon: 'pi pi-file-import',
    status: intervention.value.date_reception_dossier ? 'completed' : 'pending',
    date: intervention.value.date_reception_dossier,
    statut: intervention.value.date_reception_dossier ? 'Terminé' : 'En attente',
    actions: !intervention.value.date_reception_dossier ? [
      { label: 'Marquer reçu', icon: 'pi pi-check', type: 'mark-received' }
    ] : []
  })

  // 2. Étude technique
  steps.push({
    id: 'etude',
    titre: 'Étude technique',
    description: 'Étude de faisabilité et dimensionnement',
    icon: 'pi pi-cog',
    status: intervention.value.date_etude_technique ? 'completed' :
            (intervention.value.date_reception_dossier ? 'active' : 'pending'),
    date: intervention.value.date_etude_technique,
    statut: intervention.value.date_etude_technique ? 'Terminé' :
            (intervention.value.date_reception_dossier ? 'En cours' : 'En attente'),
    duree: '2h',
    actions: !intervention.value.date_etude_technique && intervention.value.date_reception_dossier ? [
      { label: 'Planifier étude', icon: 'pi pi-calendar', type: 'schedule-study' }
    ] : []
  })

  // 3. Phase terrassement (si applicable)
  if (hasPhase('terrassement')) {
    const terrassementData = getPhaseData('terrassement')
    steps.push({
      id: 'terrassement',
      titre: 'Terrassement',
      description: 'Travaux de terrassement et pose',
      icon: 'pi pi-wrench',
      status: terrassementData.statut === 'terminee' ? 'completed' :
              (terrassementData.statut === 'en_cours' ? 'active' : 'pending'),
      date: terrassementData.date_debut,
      statut: getStatutLabel(terrassementData.statut),
      duree: terrassementData.duree_estimee_heures ? `${terrassementData.duree_estimee_heures}h` : undefined,
      actions: getPhaseActions('terrassement', terrassementData)
    })
  }

  // 4. Phase branchement
  const branchementData = getPhaseData('branchement')
  steps.push({
    id: 'branchement',
    titre: 'Branchement électrique',
    description: 'Raccordement et câblage électrique',
    icon: 'pi pi-bolt',
    status: branchementData.statut === 'terminee' ? 'completed' :
            (branchementData.statut === 'en_cours' ? 'active' : 'pending'),
    date: branchementData.date_debut,
    statut: getStatutLabel(branchementData.statut),
    duree: branchementData.duree_estimee_heures ? `${branchementData.duree_estimee_heures}h` : undefined,
    actions: getPhaseActions('branchement', branchementData)
  })

  // 5. Contrôle et mise en service
  steps.push({
    id: 'controle',
    titre: 'Contrôle et tests',
    description: 'Vérifications et tests de conformité',
    icon: 'pi pi-check-circle',
    status: intervention.value.date_controle ? 'completed' :
            (branchementData.statut === 'terminee' ? 'active' : 'pending'),
    date: intervention.value.date_controle,
    statut: intervention.value.date_controle ? 'Terminé' :
            (branchementData.statut === 'terminee' ? 'En cours' : 'En attente'),
    duree: '30min',
    actions: !intervention.value.date_controle && branchementData.statut === 'terminee' ? [
      { label: 'Effectuer contrôle', icon: 'pi pi-search', type: 'start-control' }
    ] : []
  })

  // 6. Mise en service
  steps.push({
    id: 'mise_en_service',
    titre: 'Mise en service',
    description: 'Activation du branchement',
    icon: 'pi pi-power-off',
    status: intervention.value.date_mise_en_service ? 'completed' :
            (intervention.value.date_controle ? 'active' : 'pending'),
    date: intervention.value.date_mise_en_service,
    statut: intervention.value.date_mise_en_service ? 'Terminé' :
            (intervention.value.date_controle ? 'En cours' : 'En attente'),
    actions: !intervention.value.date_mise_en_service && intervention.value.date_controle ? [
      { label: 'Mettre en service', icon: 'pi pi-play', type: 'activate-service' }
    ] : []
  })

  return steps
})

/**
 * Retourne les actions possibles pour une phase
 */
const getPhaseActions = (phase, phaseData) => {
  const actions = []

  if (phaseData.statut === 'en_attente' && canStartPhase(phase)) {
    actions.push({ label: 'Démarrer', icon: 'pi pi-play', type: `start-${phase}` })
  }

  if (phaseData.statut === 'en_cours') {
    actions.push({ label: 'Terminer', icon: 'pi pi-check', type: `complete-${phase}` })
  }

  if (phaseData.statut === 'terminee') {
    actions.push({ label: 'Voir détails', icon: 'pi pi-eye', type: `view-${phase}` })
  }

  return actions
}

/**
 * Retourne le label du statut
 */
const getStatutLabel = (statut) => {
  switch (statut) {
    case 'en_attente': return 'En attente'
    case 'en_cours': return 'En cours'
    case 'terminee': return 'Terminé'
    case 'annulee': return 'Annulé'
    default: return 'Non défini'
  }
}

/**
 * Gestion des actions de la timeline
 */
const handleTimelineAction = (actionType, etape) => {
  switch (actionType) {
    case 'mark-received':
      // Marquer le dossier comme reçu
      console.log('Marquer dossier reçu')
      break
    case 'schedule-study':
      // Planifier l'étude technique
      console.log('Planifier étude technique')
      break
    case 'start-terrassement':
      // Démarrer la phase terrassement
      console.log('Démarrer terrassement')
      break
    case 'start-branchement':
      // Démarrer la phase branchement
      console.log('Démarrer branchement')
      break
    case 'complete-terrassement':
      // Terminer la phase terrassement
      console.log('Terminer terrassement')
      break
    case 'complete-branchement':
      // Terminer la phase branchement
      console.log('Terminer branchement')
      break
    case 'start-control':
      // Démarrer le contrôle
      console.log('Démarrer contrôle')
      break
    case 'activate-service':
      // Mettre en service
      console.log('Mettre en service')
      break
    default:
      console.log('Action timeline non définie:', actionType, etape)
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
 * Retourne le critère de classification Type 1/2
 */
const getCritereType = (specifications) => {
  if (!specifications) return 'Non défini'

  const di = specifications.longueur_derivation_individuelle || 0

  if (di <= 30) {
    return `DI = ${di}m ≤ 30m → Type 1`
  } else {
    return `DI = ${di}m > 30m → Type 2`
  }
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
  padding: 0.5rem;
}

@media (min-width: 640px) {
  .branchement-electrique {
    padding: 1rem;
  }
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
@media (max-width: 640px) {
  .branchement-electrique {
    padding: 0.25rem;
  }

  /* Améliorer l'espacement sur mobile */
  .branchement-electrique :deep(.p-card) {
    margin-bottom: 0.75rem;
  }

  .branchement-electrique :deep(.p-card-content) {
    padding: 0.75rem;
  }

  .branchement-electrique :deep(.p-card-header) {
    padding: 0.75rem;
  }
}

/* Amélioration de la lisibilité sur petits écrans */
@media (max-width: 480px) {
  .branchement-electrique {
    padding: 0.125rem;
  }

  /* Réduire encore plus les marges sur très petits écrans */
  .branchement-electrique :deep(.p-card) {
    margin-bottom: 0.5rem;
  }

  .branchement-electrique :deep(.p-card-content) {
    padding: 0.5rem;
  }

  .branchement-electrique :deep(.p-card-header) {
    padding: 0.5rem;
  }
}
</style>