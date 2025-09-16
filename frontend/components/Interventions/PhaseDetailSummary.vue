<!--
  Composant de détail d'une phase pour le résumé

  Affiche les métriques et indicateurs spécifiques à une phase
  avec comparaison estimé vs réel et indicateurs visuels
-->
<template>
  <div class="text-sm space-y-4">
    <!-- Métriques principales -->
    <div class="grid grid-cols-2 gap-4">
      <div class="text-center p-3 bg-surface-50 dark:bg-surface-800 rounded-lg">
        <div class="text-lg font-bold text-surface-900 dark:text-surface-50">
          {{ formatDuration(duration) }}
        </div>
        <div class="text-xs text-surface-600 dark:text-surface-400">
          Temps réel
        </div>
      </div>

      <div class="text-center p-3 bg-surface-50 dark:bg-surface-800 rounded-lg">
        <div class="text-lg font-bold text-surface-900 dark:text-surface-50">
          {{ formatCurrency(cost) }}
        </div>
        <div class="text-xs text-surface-600 dark:text-surface-400">
          Coût réel
        </div>
      </div>
    </div>

    <!-- Barres de progression -->
    <div class="space-y-3">
      <!-- Progression du temps -->
      <div class="space-y-1">
        <div class="flex justify-between text-sm">
          <span class="text-surface-700 dark:text-surface-300">Temps vs Estimé</span>
          <span class="font-medium" :class="getTimeVarianceClass()">
            {{ getTimeVarianceText() }}
          </span>
        </div>
        <ProgressBar
          :value="getTimeProgress()"
          :class="getTimeProgressClass()"
          :show-value="false"
        />
      </div>

      <!-- Progression du coût -->
      <div class="space-y-1">
        <div class="flex justify-between text-sm">
          <span class="text-surface-700 dark:text-surface-300">Coût vs Estimé</span>
          <span class="font-medium" :class="getCostVarianceClass()">
            {{ getCostVarianceText() }}
          </span>
        </div>
        <ProgressBar
          :value="getCostProgress()"
          :class="getCostProgressClass()"
          :show-value="false"
        />
      </div>
    </div>

    <!-- Statut et taux horaire -->
    <div class="flex justify-between items-center pt-2 border-t border-surface-200 dark:border-surface-700">
      <div class="flex items-center gap-2">
        <Badge
          :value="getStatusLabel()"
          :severity="getStatusSeverity()"
          class="text-xs"
        />
      </div>
      <div class="text-right">
        <div class="text-sm font-medium text-surface-900 dark:text-surface-50">
          {{ formatCurrency(hourlyRate) }}/h
        </div>
        <div class="text-xs text-surface-600 dark:text-surface-400">
          Taux horaire
        </div>
      </div>
    </div>

    <!-- Indicateurs de performance -->
    <div v-if="status === 'terminee'" class="mt-4 p-3 rounded-lg border" :class="getPerformanceBorderClass()">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <i :class="getPerformanceIcon()" class="text-lg"></i>
          <span class="font-medium text-surface-900 dark:text-surface-50">
            {{ getPerformanceLabel() }}
          </span>
        </div>
        <div class="text-right">
          <div class="text-sm font-bold" :class="getPerformanceTextClass()">
            {{ getOverallPerformance() }}%
          </div>
          <div class="text-xs text-surface-600 dark:text-surface-400">
            Performance
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
/**
 * Composant de détail d'une phase pour le résumé
 *
 * Props:
 * @param {string} phase - 'terrassement' ou 'branchement'
 * @param {number} duration - Durée réelle en heures
 * @param {number} cost - Coût réel
 * @param {number} estimatedCost - Coût estimé
 * @param {number} hourlyRate - Taux horaire
 * @param {string} status - Statut de la phase
 */

import { computed } from 'vue'

const props = defineProps({
  phase: {
    type: String,
    required: true,
    validator: value => ['terrassement', 'branchement'].includes(value)
  },
  duration: {
    type: Number,
    default: 0
  },
  cost: {
    type: Number,
    default: 0
  },
  estimatedCost: {
    type: Number,
    default: 0
  },
  hourlyRate: {
    type: Number,
    required: true
  },
  status: {
    type: String,
    default: 'en_attente'
  }
})

// Durée estimée standard (4h)
const estimatedDuration = 4

// Propriétés calculées pour les variances
const timeVariance = computed(() => {
  if (!props.duration || !estimatedDuration) return 0
  return ((props.duration - estimatedDuration) / estimatedDuration) * 100
})

const costVariance = computed(() => {
  if (!props.cost || !props.estimatedCost) return 0
  return ((props.cost - props.estimatedCost) / props.estimatedCost) * 100
})

/**
 * Calcule le pourcentage de progression du temps
 */
const getTimeProgress = () => {
  if (!props.duration) return 0
  return Math.min((props.duration / estimatedDuration) * 100, 200) // Plafonné à 200%
}

/**
 * Calcule le pourcentage de progression du coût
 */
const getCostProgress = () => {
  if (!props.cost || !props.estimatedCost) return 0
  return Math.min((props.cost / props.estimatedCost) * 100, 200) // Plafonné à 200%
}

/**
 * Classes CSS pour la barre de progression du temps
 */
const getTimeProgressClass = () => {
  const variance = timeVariance.value
  if (variance > 20) return 'progress-danger'
  if (variance > 10) return 'progress-warning'
  if (variance < -10) return 'progress-success'
  return 'progress-info'
}

/**
 * Classes CSS pour la barre de progression du coût
 */
const getCostProgressClass = () => {
  const variance = costVariance.value
  if (variance > 20) return 'progress-danger'
  if (variance > 10) return 'progress-warning'
  if (variance < -10) return 'progress-success'
  return 'progress-info'
}

/**
 * Texte de variance du temps
 */
const getTimeVarianceText = () => {
  const variance = timeVariance.value
  if (Math.abs(variance) < 1) return 'Conforme'
  const prefix = variance > 0 ? '+' : ''
  return `${prefix}${variance.toFixed(1)}%`
}

/**
 * Texte de variance du coût
 */
const getCostVarianceText = () => {
  const variance = costVariance.value
  if (Math.abs(variance) < 1) return 'Conforme'
  const prefix = variance > 0 ? '+' : ''
  return `${prefix}${variance.toFixed(1)}%`
}

/**
 * Classes CSS pour le texte de variance du temps
 */
const getTimeVarianceClass = () => {
  const variance = timeVariance.value
  if (variance > 20) return 'text-red-600 dark:text-red-400'
  if (variance > 10) return 'text-orange-600 dark:text-orange-400'
  if (variance < -10) return 'text-green-600 dark:text-green-400'
  return 'text-surface-700 dark:text-surface-300'
}

/**
 * Classes CSS pour le texte de variance du coût
 */
const getCostVarianceClass = () => {
  const variance = costVariance.value
  if (variance > 20) return 'text-red-600 dark:text-red-400'
  if (variance > 10) return 'text-orange-600 dark:text-orange-400'
  if (variance < -10) return 'text-green-600 dark:text-green-400'
  return 'text-surface-700 dark:text-surface-300'
}

/**
 * Libellé du statut
 */
const getStatusLabel = () => {
  const labels = {
    'en_attente': 'En attente',
    'en_cours': 'En cours',
    'terminee': 'Terminée',
    'annulee': 'Annulée',
    'non_applicable': 'Non applicable'
  }
  return labels[props.status] || props.status
}

/**
 * Severity du statut pour PrimeVue
 */
const getStatusSeverity = () => {
  const severities = {
    'en_attente': 'warning',
    'en_cours': 'info',
    'terminee': 'success',
    'annulee': 'danger',
    'non_applicable': 'secondary'
  }
  return severities[props.status] || 'secondary'
}

/**
 * Calcule la performance globale
 */
const getOverallPerformance = () => {
  if (props.status !== 'terminee') return 0

  // Performance basée sur l'efficacité temps et coût
  const timeEfficiency = Math.max(0, 200 - getTimeProgress())
  const costEfficiency = Math.max(0, 200 - getCostProgress())

  return Math.round((timeEfficiency + costEfficiency) / 2)
}

/**
 * Libellé de performance
 */
const getPerformanceLabel = () => {
  const performance = getOverallPerformance()
  if (performance >= 90) return 'Excellente performance'
  if (performance >= 75) return 'Bonne performance'
  if (performance >= 60) return 'Performance correcte'
  return 'Performance à améliorer'
}

/**
 * Icône de performance
 */
const getPerformanceIcon = () => {
  const performance = getOverallPerformance()
  if (performance >= 90) return 'pi pi-star-fill text-yellow-500'
  if (performance >= 75) return 'pi pi-thumbs-up text-green-600'
  if (performance >= 60) return 'pi pi-info-circle text-blue-600'
  return 'pi pi-exclamation-triangle text-orange-600'
}

/**
 * Classes CSS pour le texte de performance
 */
const getPerformanceTextClass = () => {
  const performance = getOverallPerformance()
  if (performance >= 90) return 'text-yellow-600 dark:text-yellow-400'
  if (performance >= 75) return 'text-green-600 dark:text-green-400'
  if (performance >= 60) return 'text-blue-600 dark:text-blue-400'
  return 'text-orange-600 dark:text-orange-400'
}

/**
 * Classes CSS pour la bordure de performance
 */
const getPerformanceBorderClass = () => {
  const performance = getOverallPerformance()
  if (performance >= 90) return 'border-yellow-200 bg-yellow-50 dark:border-yellow-800 dark:bg-yellow-950'
  if (performance >= 75) return 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-950'
  if (performance >= 60) return 'border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-950'
  return 'border-orange-200 bg-orange-50 dark:border-orange-800 dark:bg-orange-950'
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
</script>

<style scoped>
/* Styles personnalisés pour les barres de progression PrimeVue */
:deep(.progress-success .p-progressbar-value) {
  background-color: rgb(34 197 94);
}

:deep(.progress-info .p-progressbar-value) {
  background-color: rgb(59 130 246);
}

:deep(.progress-warning .p-progressbar-value) {
  background-color: rgb(249 115 22);
}

:deep(.progress-danger .p-progressbar-value) {
  background-color: rgb(239 68 68);
}
</style>