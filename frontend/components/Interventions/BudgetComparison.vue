<!--
  Composant de comparaison budgétaire avec graphique en barres

  Affiche la comparaison entre coûts estimés et réels
  avec répartition par phase et indicateurs visuels
-->
<template>
  <div class="budget-comparison">
    <!-- Graphique en barres -->
    <div class="mb-6">
      <canvas
        ref="chartCanvas"
        width="400"
        height="250"
        class="w-full max-w-full h-auto"
      ></canvas>
    </div>

    <!-- Détails numériques -->
    <div class="space-y-3">
      <!-- Total -->
      <div class="flex justify-between items-center p-3 bg-surface-50 dark:bg-surface-800 rounded-lg">
        <div>
          <div class="font-medium text-surface-900 dark:text-surface-50">Total</div>
          <div class="text-sm text-surface-600 dark:text-surface-400">
            Écart: {{ formatEcart(totalVariance) }}
          </div>
        </div>
        <div class="text-right">
          <div class="text-lg font-bold text-surface-900 dark:text-surface-50">
            {{ formatCurrency(realTotal) }}
          </div>
          <div class="text-sm text-surface-600 dark:text-surface-400">
            Estimé: {{ formatCurrency(estimatedTotal) }}
          </div>
        </div>
      </div>

      <!-- Branchement -->
      <div class="flex justify-between items-center p-3 bg-blue-50 dark:bg-blue-950 rounded-lg">
        <div>
          <div class="font-medium text-blue-900 dark:text-blue-100">Branchement</div>
          <div class="text-sm text-blue-700 dark:text-blue-300">
            Écart: {{ formatEcart(branchementVariance) }}
          </div>
        </div>
        <div class="text-right">
          <div class="text-lg font-bold text-blue-900 dark:text-blue-100">
            {{ formatCurrency(branchementReal) }}
          </div>
          <div class="text-sm text-blue-700 dark:text-blue-300">
            Estimé: {{ formatCurrency(branchementEstimated) }}
          </div>
        </div>
      </div>

      <!-- Terrassement (si applicable) -->
      <div
        v-if="hasTerrassement"
        class="flex justify-between items-center p-3 bg-orange-50 dark:bg-orange-950 rounded-lg"
      >
        <div>
          <div class="font-medium text-orange-900 dark:text-orange-100">Terrassement</div>
          <div class="text-sm text-orange-700 dark:text-orange-300">
            Écart: {{ formatEcart(terrassementVariance) }}
          </div>
        </div>
        <div class="text-right">
          <div class="text-lg font-bold text-orange-900 dark:text-orange-100">
            {{ formatCurrency(terrassementReal) }}
          </div>
          <div class="text-sm text-orange-700 dark:text-orange-300">
            Estimé: {{ formatCurrency(terrassementEstimated) }}
          </div>
        </div>
      </div>
    </div>

    <!-- Résumé de performance -->
    <div class="mt-4 p-3 rounded-lg border" :class="getPerformanceBorderClass()">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <i :class="getPerformanceIcon()" class="text-lg"></i>
          <div>
            <div class="font-medium text-surface-900 dark:text-surface-50">
              {{ getPerformanceLabel() }}
            </div>
            <div class="text-sm text-surface-600 dark:text-surface-400">
              {{ getPerformanceDescription() }}
            </div>
          </div>
        </div>
        <div class="text-right">
          <div class="text-xl font-bold" :class="getPerformanceTextClass()">
            {{ formatEcart(totalVariance) }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
/**
 * Composant de comparaison budgétaire avec graphique en barres
 *
 * Props:
 * @param {number} estimatedTotal - Coût total estimé
 * @param {number} realTotal - Coût total réel
 * @param {number} terrassementReal - Coût réel terrassement
 * @param {number} branchementReal - Coût réel branchement
 * @param {boolean} hasTerrassement - Si l'intervention a du terrassement
 */

import { ref, computed, onMounted, nextTick, watch } from 'vue'

const props = defineProps({
  estimatedTotal: {
    type: Number,
    default: 0
  },
  realTotal: {
    type: Number,
    default: 0
  },
  terrassementReal: {
    type: Number,
    default: 0
  },
  branchementReal: {
    type: Number,
    default: 0
  },
  hasTerrassement: {
    type: Boolean,
    default: false
  }
})

// Référence du canvas
const chartCanvas = ref(null)

// Coûts estimés calculés (4h × taux)
const terrassementEstimated = computed(() => props.hasTerrassement ? 4 * 38 : 0) // 152€
const branchementEstimated = computed(() => 4 * 45) // 180€

// Variances en pourcentage
const totalVariance = computed(() => {
  if (!props.estimatedTotal) return 0
  return ((props.realTotal - props.estimatedTotal) / props.estimatedTotal) * 100
})

const terrassementVariance = computed(() => {
  if (!terrassementEstimated.value) return 0
  return ((props.terrassementReal - terrassementEstimated.value) / terrassementEstimated.value) * 100
})

const branchementVariance = computed(() => {
  if (!branchementEstimated.value) return 0
  return ((props.branchementReal - branchementEstimated.value) / branchementEstimated.value) * 100
})

/**
 * Dessine le graphique en barres comparatif
 */
const drawChart = () => {
  if (!chartCanvas.value) return

  const canvas = chartCanvas.value
  const ctx = canvas.getContext('2d')
  const width = canvas.width
  const height = canvas.height

  // Marges
  const margin = { top: 20, right: 20, bottom: 60, left: 60 }
  const chartWidth = width - margin.left - margin.right
  const chartHeight = height - margin.top - margin.bottom

  // Données
  const data = []

  if (props.hasTerrassement) {
    data.push({
      label: 'Terrassement',
      estimated: terrassementEstimated.value,
      real: props.terrassementReal,
      color: '#f97316' // orange-500
    })
  }

  data.push({
    label: 'Branchement',
    estimated: branchementEstimated.value,
    real: props.branchementReal,
    color: '#3b82f6' // blue-500
  })

  // Valeur maximale pour l'échelle
  const maxValue = Math.max(
    ...data.flatMap(d => [d.estimated, d.real]),
    0
  ) * 1.1

  // Effacer le canvas
  ctx.clearRect(0, 0, width, height)

  if (maxValue === 0) {
    // Afficher un message si pas de données
    ctx.fillStyle = '#6b7280'
    ctx.font = '16px sans-serif'
    ctx.textAlign = 'center'
    ctx.fillText('Aucune donnée', width / 2, height / 2)
    return
  }

  // Configuration
  const barWidth = chartWidth / (data.length * 3) // 3 pour 2 barres + espacement
  const groupWidth = barWidth * 2.5

  // Dessiner les axes
  ctx.strokeStyle = '#d1d5db'
  ctx.lineWidth = 1

  // Axe Y
  ctx.beginPath()
  ctx.moveTo(margin.left, margin.top)
  ctx.lineTo(margin.left, margin.top + chartHeight)
  ctx.stroke()

  // Axe X
  ctx.beginPath()
  ctx.moveTo(margin.left, margin.top + chartHeight)
  ctx.lineTo(margin.left + chartWidth, margin.top + chartHeight)
  ctx.stroke()

  // Graduations Y
  const steps = 5
  ctx.fillStyle = '#6b7280'
  ctx.font = '12px sans-serif'
  ctx.textAlign = 'right'

  for (let i = 0; i <= steps; i++) {
    const value = (maxValue / steps) * i
    const y = margin.top + chartHeight - (chartHeight / steps) * i

    // Ligne de grille
    ctx.strokeStyle = '#f3f4f6'
    ctx.beginPath()
    ctx.moveTo(margin.left, y)
    ctx.lineTo(margin.left + chartWidth, y)
    ctx.stroke()

    // Label
    ctx.fillText(
      new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value),
      margin.left - 5,
      y + 4
    )
  }

  // Dessiner les barres
  data.forEach((item, index) => {
    const groupX = margin.left + (index + 0.5) * (chartWidth / data.length)
    const barX1 = groupX - barWidth
    const barX2 = groupX

    // Barre estimée (plus claire)
    const estimatedHeight = (item.estimated / maxValue) * chartHeight
    const estimatedY = margin.top + chartHeight - estimatedHeight

    ctx.fillStyle = item.color + '80' // 50% opacity
    ctx.fillRect(barX1, estimatedY, barWidth * 0.8, estimatedHeight)

    // Barre réelle (couleur pleine)
    const realHeight = (item.real / maxValue) * chartHeight
    const realY = margin.top + chartHeight - realHeight

    ctx.fillStyle = item.color
    ctx.fillRect(barX2, realY, barWidth * 0.8, realHeight)

    // Labels des barres
    ctx.fillStyle = '#374151'
    ctx.font = '11px sans-serif'
    ctx.textAlign = 'center'

    // Label estimé
    ctx.fillText(
      formatCurrency(item.estimated),
      barX1 + barWidth * 0.4,
      estimatedY - 5
    )

    // Label réel
    ctx.fillText(
      formatCurrency(item.real),
      barX2 + barWidth * 0.4,
      realY - 5
    )

    // Label de la phase
    ctx.font = 'bold 12px sans-serif'
    ctx.fillText(
      item.label,
      groupX + barWidth * 0.4,
      margin.top + chartHeight + 20
    )
  })

  // Légende
  const legendY = margin.top + chartHeight + 40
  ctx.font = '12px sans-serif'
  ctx.textAlign = 'left'

  // Estimé
  ctx.fillStyle = '#6b7280' + '80'
  ctx.fillRect(margin.left, legendY, 15, 12)
  ctx.fillStyle = '#374151'
  ctx.fillText('Estimé', margin.left + 20, legendY + 9)

  // Réel
  ctx.fillStyle = '#374151'
  ctx.fillRect(margin.left + 80, legendY, 15, 12)
  ctx.fillText('Réel', margin.left + 100, legendY + 9)
}

/**
 * Libellé de performance basé sur la variance totale
 */
const getPerformanceLabel = () => {
  const variance = totalVariance.value
  if (Math.abs(variance) <= 5) return 'Budget maîtrisé'
  if (variance > 20) return 'Dépassement important'
  if (variance > 10) return 'Dépassement modéré'
  if (variance > 5) return 'Léger dépassement'
  if (variance < -15) return 'Économie importante'
  if (variance < -5) return 'Économie réalisée'
  return 'Écart minimal'
}

/**
 * Description de performance
 */
const getPerformanceDescription = () => {
  const variance = totalVariance.value
  if (Math.abs(variance) <= 5) return 'Coûts conformes aux prévisions'
  if (variance > 20) return 'Révision nécessaire des estimations'
  if (variance > 10) return 'Surveillance des coûts recommandée'
  if (variance > 5) return 'Dépassement acceptable'
  if (variance < -15) return 'Efficacité remarquable'
  if (variance < -5) return 'Bon contrôle des coûts'
  return 'Performance équilibrée'
}

/**
 * Icône de performance
 */
const getPerformanceIcon = () => {
  const variance = Math.abs(totalVariance.value)
  if (variance <= 5) return 'pi pi-check-circle text-green-600'
  if (variance > 20) return 'pi pi-exclamation-triangle text-red-600'
  if (variance > 10) return 'pi pi-info-circle text-orange-600'
  return 'pi pi-minus-circle text-blue-600'
}

/**
 * Classes CSS pour le texte de performance
 */
const getPerformanceTextClass = () => {
  const variance = totalVariance.value
  if (Math.abs(variance) <= 5) return 'text-green-600 dark:text-green-400'
  if (variance > 20) return 'text-red-600 dark:text-red-400'
  if (variance > 10) return 'text-orange-600 dark:text-orange-400'
  return 'text-blue-600 dark:text-blue-400'
}

/**
 * Classes CSS pour la bordure de performance
 */
const getPerformanceBorderClass = () => {
  const variance = Math.abs(totalVariance.value)
  if (variance <= 5) return 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-950'
  if (variance > 20) return 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-950'
  if (variance > 10) return 'border-orange-200 bg-orange-50 dark:border-orange-800 dark:bg-orange-950'
  return 'border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-950'
}

/**
 * Formate un écart en pourcentage
 */
const formatEcart = (variance) => {
  if (Math.abs(variance) < 0.1) return '±0%'
  const prefix = variance > 0 ? '+' : ''
  return `${prefix}${variance.toFixed(1)}%`
}

/**
 * Formate un montant en euros
 */
const formatCurrency = (amount) => {
  if (!amount) return '0€'
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
    maximumFractionDigits: 0
  }).format(amount)
}

// Dessiner le graphique au montage et lors des changements
onMounted(() => {
  nextTick(() => {
    drawChart()
  })
})

watch([
  () => props.estimatedTotal,
  () => props.realTotal,
  () => props.terrassementReal,
  () => props.branchementReal,
  () => props.hasTerrassement
], () => {
  nextTick(() => {
    drawChart()
  })
})
</script>

<style scoped>
/**
 * Styles CSS personnalisés pour BudgetComparison
 */

.budget-comparison {
  gap: 1rem;
  display: flex;
  flex-direction: column;
}

/* Canvas responsive */
canvas {
  max-width: 100%;
  height: auto;
}

/* Animation pour les éléments */
.budget-comparison > div {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 300ms;
}

/* Responsive */
@media (max-width: 640px) {
  .budget-comparison {
    gap: 0.75rem;
  }

  .budget-comparison :deep(.text-lg) {
    font-size: 1rem;
    line-height: 1.5rem;
  }

  .budget-comparison :deep(.text-xl) {
    font-size: 1.125rem;
    line-height: 1.75rem;
  }
}
</style>