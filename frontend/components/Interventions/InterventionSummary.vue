<!--
  Composant de résumé financier et statistiques de branchement électrique

  Fonctionnalités :
  - Tableau de bord avec métriques clés
  - Comparaison estimé vs réel
  - Indicateurs de performance
  - Graphiques de répartition du temps
  - Calculs automatiques des coûts et marges
-->
<template>
  <div class="intervention-summary">
    <!-- Métriques principales -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <!-- Durée totale -->
      <Card class="metric-card">
        <template #content>
          <div class="text-center p-4">
            <div class="flex justify-center mb-2">
              <Avatar
                icon="pi pi-clock"
                class="bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-300"
                size="large"
                shape="circle"
              />
            </div>
            <div class="text-2xl font-bold text-surface-900 dark:text-surface-50 mb-1">
              {{ formatDuration(totalDuration) }}
            </div>
            <div class="text-sm text-surface-600 dark:text-surface-400">
              Durée totale
            </div>
            <div class="text-xs text-surface-500 dark:text-surface-500 mt-1">
              Estimé: {{ estimatedDuration }}h
            </div>
          </div>
        </template>
      </Card>

      <!-- Coût total -->
      <Card class="metric-card">
        <template #content>
          <div class="text-center p-4">
            <div class="flex justify-center mb-2">
              <Avatar
                icon="pi pi-euro"
                class="bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-300"
                size="large"
                shape="circle"
              />
            </div>
            <div class="text-2xl font-bold text-surface-900 dark:text-surface-50 mb-1">
              {{ formatCurrency(intervention?.cout_total_reel) }}
            </div>
            <div class="text-sm text-surface-600 dark:text-surface-400">
              Coût total réel
            </div>
            <div class="text-xs text-surface-500 dark:text-surface-500 mt-1">
              Estimé: {{ formatCurrency(intervention?.cout_total_estime) }}
            </div>
          </div>
        </template>
      </Card>

      <!-- Écart budgétaire -->
      <Card class="metric-card">
        <template #content>
          <div class="text-center p-4">
            <div class="flex justify-center mb-2">
              <Avatar
                :icon="getEcartIcon()"
                :class="getEcartAvatarClass()"
                size="large"
                shape="circle"
              />
            </div>
            <div class="text-2xl font-bold mb-1" :class="getEcartTextClass()">
              {{ formatEcart() }}
            </div>
            <div class="text-sm text-surface-600 dark:text-surface-400">
              Écart budget
            </div>
            <div class="text-xs text-surface-500 dark:text-surface-500 mt-1">
              {{ getEcartDescription() }}
            </div>
          </div>
        </template>
      </Card>

      <!-- Efficacité -->
      <Card class="metric-card">
        <template #content>
          <div class="text-center p-4">
            <div class="flex justify-center mb-2">
              <Avatar
                icon="pi pi-chart-line"
                class="bg-purple-100 text-purple-600 dark:bg-purple-900 dark:text-purple-300"
                size="large"
                shape="circle"
              />
            </div>
            <div class="text-2xl font-bold text-surface-900 dark:text-surface-50 mb-1">
              {{ getEfficiencyScore() }}%
            </div>
            <div class="text-sm text-surface-600 dark:text-surface-400">
              Efficacité
            </div>
            <div class="text-xs text-surface-500 dark:text-surface-500 mt-1">
              {{ getEfficiencyLabel() }}
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Détail par phase -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
      <!-- Phase Terrassement -->
      <Card v-if="hasPhase('terrassement')">
        <template #header>
          <div class="p-4 border-b border-surface-200 dark:border-surface-700">
            <h4 class="text-lg font-semibold text-surface-900 dark:text-surface-50 flex items-center gap-2">
              <i class="pi pi-home text-orange-600"></i>
              Phase Terrassement
            </h4>
          </div>
        </template>
        <template #content>
          <PhaseDetailSummary
            phase="terrassement"
            :duration="totalTerrassementDuration"
            :cost="getPhaseRealCost('terrassement')"
            :estimated-cost="getPhaseEstimatedCost('terrassement')"
            :hourly-rate="38"
            :status="intervention?.phase_terrassement_statut"
          />
        </template>
      </Card>

      <!-- Phase Branchement -->
      <Card>
        <template #header>
          <div class="p-4 border-b border-surface-200 dark:border-surface-700">
            <h4 class="text-lg font-semibold text-surface-900 dark:text-surface-50 flex items-center gap-2">
              <i class="pi pi-bolt text-blue-600"></i>
              Phase Branchement
            </h4>
          </div>
        </template>
        <template #content>
          <PhaseDetailSummary
            phase="branchement"
            :duration="totalBranchementDuration"
            :cost="getPhaseRealCost('branchement')"
            :estimated-cost="getPhaseEstimatedCost('branchement')"
            :hourly-rate="45"
            :status="intervention?.phase_branchement_statut"
          />
        </template>
      </Card>
    </div>

    <!-- Graphique de répartition du temps -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Répartition du temps -->
      <Card>
        <template #header>
          <div class="p-4 border-b border-surface-200 dark:border-surface-700">
            <h4 class="text-lg font-semibold text-surface-900 dark:text-surface-50">
              Répartition du temps
            </h4>
          </div>
        </template>
        <template #content>
          <div class="text-center">
            <canvas
              ref="timeChartCanvas"
              width="300"
              height="300"
              class="max-w-full h-auto"
            ></canvas>
          </div>
        </template>
      </Card>

      <!-- Évolution des coûts -->
      <Card>
        <template #header>
          <div class="p-4 border-b border-surface-200 dark:border-surface-700">
            <h4 class="text-lg font-semibold text-surface-900 dark:text-surface-50">
              Comparaison budgétaire
            </h4>
          </div>
        </template>
        <template #content>
          <BudgetComparison
            :estimated-total="intervention?.cout_total_estime"
            :real-total="intervention?.cout_total_reel"
            :terrassement-real="getPhaseRealCost('terrassement')"
            :branchement-real="getPhaseRealCost('branchement')"
            :has-terrassement="hasPhase('terrassement')"
          />
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup>
/**
 * Composant de résumé financier et statistiques de branchement électrique
 *
 * Props:
 * @param {object} intervention - Données complètes de l'intervention
 * @param {number} totalTerrassementDuration - Durée totale terrassement en heures
 * @param {number} totalBranchementDuration - Durée totale branchement en heures
 */

import { ref, computed, onMounted, nextTick, watch } from 'vue'

// Composants enfants
import PhaseDetailSummary from './PhaseDetailSummary.vue'
import BudgetComparison from './BudgetComparison.vue'

const props = defineProps({
  intervention: {
    type: Object,
    default: () => ({})
  },
  totalTerrassementDuration: {
    type: Number,
    default: 0
  },
  totalBranchementDuration: {
    type: Number,
    default: 0
  }
})

// Références pour les graphiques
const timeChartCanvas = ref(null)

// Propriétés calculées
const totalDuration = computed(() => {
  return props.totalTerrassementDuration + props.totalBranchementDuration
})

const estimatedDuration = computed(() => {
  let total = 4 // Branchement toujours présent
  if (hasPhase('terrassement')) {
    total += 4 // Terrassement
  }
  return total
})

/**
 * Vérifie si l'intervention a une phase donnée
 */
const hasPhase = (phase) => {
  if (phase === 'terrassement') {
    return props.intervention?.has_terrassement === true
  }
  return true // Branchement toujours présent
}

/**
 * Obtient le coût réel d'une phase
 */
const getPhaseRealCost = (phase) => {
  return props.intervention?.[`phase_${phase}_cout`] || 0
}

/**
 * Obtient le coût estimé d'une phase (calcul approximatif)
 */
const getPhaseEstimatedCost = (phase) => {
  const estimatedHours = 4 // Durée estimée standard
  const hourlyRate = phase === 'terrassement' ? 38 : 45
  return estimatedHours * hourlyRate
}

/**
 * Calcule le score d'efficacité
 */
const getEfficiencyScore = () => {
  if (!totalDuration.value || !estimatedDuration.value) return 100

  const efficiency = (estimatedDuration.value / totalDuration.value) * 100
  return Math.round(Math.min(efficiency, 150)) // Plafonné à 150%
}

/**
 * Obtient le libellé d'efficacité
 */
const getEfficiencyLabel = () => {
  const score = getEfficiencyScore()
  if (score >= 120) return 'Excellent'
  if (score >= 100) return 'Optimal'
  if (score >= 80) return 'Correct'
  return 'À améliorer'
}

/**
 * Obtient l'icône pour l'écart budgétaire
 */
const getEcartIcon = () => {
  const ecart = props.intervention?.ecart_pourcentage || 0
  if (ecart > 10) return 'pi pi-exclamation-triangle'
  if (ecart > 0) return 'pi pi-info-circle'
  if (ecart < -5) return 'pi pi-check-circle'
  return 'pi pi-minus-circle'
}

/**
 * Obtient les classes CSS pour l'avatar d'écart
 */
const getEcartAvatarClass = () => {
  const ecart = props.intervention?.ecart_pourcentage || 0
  if (ecart > 10) return 'bg-red-100 text-red-600 dark:bg-red-900 dark:text-red-300'
  if (ecart > 0) return 'bg-orange-100 text-orange-600 dark:bg-orange-900 dark:text-orange-300'
  if (ecart < -5) return 'bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-300'
  return 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300'
}

/**
 * Obtient les classes CSS pour le texte d'écart
 */
const getEcartTextClass = () => {
  const ecart = props.intervention?.ecart_pourcentage || 0
  if (ecart > 10) return 'text-red-600 dark:text-red-400'
  if (ecart > 0) return 'text-orange-600 dark:text-orange-400'
  if (ecart < -5) return 'text-green-600 dark:text-green-400'
  return 'text-surface-900 dark:text-surface-50'
}

/**
 * Formate l'écart budgétaire
 */
const formatEcart = () => {
  const ecart = props.intervention?.ecart_pourcentage || 0
  const prefix = ecart > 0 ? '+' : ''
  return `${prefix}${ecart.toFixed(1)}%`
}

/**
 * Obtient la description de l'écart
 */
const getEcartDescription = () => {
  const ecart = props.intervention?.ecart_pourcentage || 0
  if (ecart > 10) return 'Dépassement important'
  if (ecart > 5) return 'Dépassement modéré'
  if (ecart > 0) return 'Léger dépassement'
  if (ecart < -10) return 'Économie importante'
  if (ecart < -5) return 'Économie modérée'
  if (ecart < 0) return 'Légère économie'
  return 'Conforme au budget'
}

/**
 * Formate une durée en heures au format HH:MM
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
 * Dessine le graphique de répartition du temps
 */
const drawTimeChart = () => {
  if (!timeChartCanvas.value) return

  const canvas = timeChartCanvas.value
  const ctx = canvas.getContext('2d')
  const centerX = canvas.width / 2
  const centerY = canvas.height / 2
  const radius = Math.min(centerX, centerY) - 20

  // Données du graphique
  const terrassementDuration = props.totalTerrassementDuration
  const branchementDuration = props.totalBranchementDuration
  const total = terrassementDuration + branchementDuration

  if (total === 0) {
    // Afficher un message si pas de données
    ctx.clearRect(0, 0, canvas.width, canvas.height)
    ctx.fillStyle = '#6b7280'
    ctx.font = '16px sans-serif'
    ctx.textAlign = 'center'
    ctx.fillText('Aucune donnée', centerX, centerY)
    return
  }

  // Couleurs
  const colors = {
    terrassement: '#f97316', // orange-500
    branchement: '#3b82f6'   // blue-500
  }

  // Angles
  let currentAngle = -Math.PI / 2 // Commencer en haut

  // Effacer le canvas
  ctx.clearRect(0, 0, canvas.width, canvas.height)

  // Dessiner les secteurs
  if (terrassementDuration > 0) {
    const angle = (terrassementDuration / total) * 2 * Math.PI

    ctx.beginPath()
    ctx.moveTo(centerX, centerY)
    ctx.arc(centerX, centerY, radius, currentAngle, currentAngle + angle)
    ctx.closePath()
    ctx.fillStyle = colors.terrassement
    ctx.fill()

    currentAngle += angle
  }

  if (branchementDuration > 0) {
    const angle = (branchementDuration / total) * 2 * Math.PI

    ctx.beginPath()
    ctx.moveTo(centerX, centerY)
    ctx.arc(centerX, centerY, radius, currentAngle, currentAngle + angle)
    ctx.closePath()
    ctx.fillStyle = colors.branchement
    ctx.fill()
  }

  // Dessiner la légende
  const legendY = canvas.height - 40
  let legendX = 20

  if (terrassementDuration > 0) {
    ctx.fillStyle = colors.terrassement
    ctx.fillRect(legendX, legendY, 15, 15)
    ctx.fillStyle = '#374151'
    ctx.font = '12px sans-serif'
    ctx.textAlign = 'left'
    ctx.fillText(`Terrassement (${formatDuration(terrassementDuration)})`, legendX + 20, legendY + 12)
    legendX += 180
  }

  if (branchementDuration > 0) {
    ctx.fillStyle = colors.branchement
    ctx.fillRect(legendX, legendY, 15, 15)
    ctx.fillStyle = '#374151'
    ctx.font = '12px sans-serif'
    ctx.fillText(`Branchement (${formatDuration(branchementDuration)})`, legendX + 20, legendY + 12)
  }
}

// Dessiner le graphique au montage et lors des changements
onMounted(() => {
  nextTick(() => {
    drawTimeChart()
  })
})

// Redessiner lors des changements de données
watch([() => props.totalTerrassementDuration, () => props.totalBranchementDuration], () => {
  nextTick(() => {
    drawTimeChart()
  })
})
</script>

<style scoped>
/**
 * Styles CSS personnalisés pour InterventionSummary
 */

.intervention-summary {
  gap: 1.5rem;
  display: flex;
  flex-direction: column;
}

.metric-card {
  transition-property: transform;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 200ms;
}

.metric-card:hover {
  transform: scale(1.05);
}

.metric-card :deep(.p-card-content) {
  padding: 0;
}

/* Style pour le canvas responsive */
canvas {
  max-width: 100%;
  height: auto;
}

/* Animation pour les métriques */
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

.metric-card {
  animation: fadeInUp 0.6s ease-out;
}

.metric-card:nth-child(2) {
  animation-delay: 0.1s;
}

.metric-card:nth-child(3) {
  animation-delay: 0.2s;
}

.metric-card:nth-child(4) {
  animation-delay: 0.3s;
}

/* Responsive */
@media (max-width: 768px) {
  .intervention-summary {
    gap: 1rem;
  }

  .metric-card:hover {
    transform: scale(1);
  }
}
</style>