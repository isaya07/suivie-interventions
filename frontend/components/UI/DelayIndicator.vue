<template>
  <div
    class="delay-indicator"
    :class="containerClasses"
  >
    <!-- Mode badge simple -->
    <div v-if="mode === 'badge'" class="delay-badge">
      <Badge
        :value="badgeText"
        :severity="severity"
        :size="size"
        :class="badgeClasses"
      >
        <template #value>
          <div class="flex items-center">
            <i :class="iconClass" class="mr-1"></i>
            {{ badgeText }}
          </div>
        </template>
      </Badge>
    </div>

    <!-- Mode barre de progression -->
    <div v-else-if="mode === 'progress'" class="delay-progress">
      <div class="flex items-center justify-between mb-2">
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
          {{ progressLabel }}
        </span>
        <span class="text-sm font-bold" :class="valueClasses">
          {{ progressText }}
        </span>
      </div>

      <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 relative overflow-hidden">
        <!-- Barre de progression principale -->
        <div
          class="h-full rounded-full transition-all duration-500 ease-out relative"
          :class="progressBarClasses"
          :style="{ width: `${Math.min(progressPercentage, 100)}%` }"
        >
          <!-- Animation pulse pour retards critiques -->
          <div
            v-if="isCritical"
            class="absolute inset-0 bg-white bg-opacity-30 rounded-full animate-pulse"
          ></div>
        </div>

        <!-- Marqueur d'objectif -->
        <div
          v-if="showTarget && targetPercentage < 100"
          class="absolute top-0 bottom-0 w-0.5 bg-gray-400 dark:bg-gray-500"
          :style="{ left: `${targetPercentage}%` }"
        >
          <div class="absolute -top-1 -left-2 w-4 h-4 border-2 border-gray-400 bg-white rounded-full transform translate-y-[-4px]"></div>
        </div>
      </div>

      <!-- Détails sous la barre -->
      <div class="flex justify-between mt-2 text-xs text-gray-500 dark:text-gray-400">
        <span>{{ formatDate(startDate) }}</span>
        <span v-if="showTarget">Objectif: {{ targetText }}</span>
        <span>{{ formatDate(endDate) }}</span>
      </div>
    </div>

    <!-- Mode alerte complète -->
    <div v-else-if="mode === 'alert'" class="delay-alert">
      <div
        class="rounded-lg border-l-4 p-4"
        :class="alertClasses"
      >
        <div class="flex items-start">
          <!-- Icône d'alerte -->
          <div class="flex-shrink-0">
            <i
              :class="[iconClass, iconColorClasses]"
              class="text-xl"
            ></i>
          </div>

          <!-- Contenu de l'alerte -->
          <div class="ml-3 flex-1">
            <h3 class="text-sm font-medium" :class="titleClasses">
              {{ alertTitle }}
            </h3>
            <div class="mt-1 text-sm" :class="textClasses">
              <p>{{ alertMessage }}</p>
            </div>

            <!-- Actions suggérées -->
            <div v-if="suggestedActions && suggestedActions.length > 0" class="mt-3">
              <div class="flex space-x-2">
                <Button
                  v-for="action in suggestedActions"
                  :key="action.label"
                  :label="action.label"
                  :icon="action.icon"
                  :severity="action.severity || 'secondary'"
                  size="small"
                  @click="$emit('action', action.type, delayData)"
                />
              </div>
            </div>
          </div>

          <!-- Bouton de fermeture -->
          <div v-if="dismissible" class="ml-auto pl-3">
            <Button
              icon="pi pi-times"
              text
              rounded
              size="small"
              class="!text-gray-400 hover:!text-gray-600"
              @click="$emit('dismiss')"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Mode ligne de temps -->
    <div v-else-if="mode === 'timeline'" class="delay-timeline">
      <div class="relative">
        <!-- Ligne de temps horizontale -->
        <div class="flex items-center space-x-2">
          <!-- Point de départ -->
          <div class="flex flex-col items-center">
            <div class="w-3 h-3 bg-green-500 rounded-full"></div>
            <span class="text-xs text-gray-500 mt-1">Début</span>
          </div>

          <!-- Barre de progression de la timeline -->
          <div class="flex-1 relative h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
            <div
              class="h-full rounded-full transition-all duration-500"
              :class="timelineBarClasses"
              :style="{ width: `${Math.min(progressPercentage, 100)}%` }"
            ></div>

            <!-- Indicateur de position actuelle -->
            <div
              class="absolute top-0 bottom-0 w-1 bg-gray-800 dark:bg-gray-200 rounded-full"
              :style="{ left: `${Math.min(progressPercentage, 100)}%` }"
            >
              <div class="absolute -top-2 -left-1 w-3 h-3 bg-gray-800 dark:bg-gray-200 rounded-full"></div>
            </div>
          </div>

          <!-- Point d'arrivée -->
          <div class="flex flex-col items-center">
            <div
              class="w-3 h-3 rounded-full"
              :class="endPointClasses"
            ></div>
            <span class="text-xs text-gray-500 mt-1">Échéance</span>
          </div>
        </div>

        <!-- Texte de statut sous la timeline -->
        <div class="text-center mt-2">
          <span class="text-sm font-medium" :class="statusTextClasses">
            {{ timelineStatusText }}
          </span>
        </div>
      </div>
    </div>

    <!-- Tooltip informatif -->
    <div
      v-if="showTooltip"
      class="delay-tooltip"
      v-tooltip.top="tooltipText"
    >
      <i class="pi pi-info-circle text-gray-400 hover:text-gray-600 cursor-help ml-2"></i>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  // Données du délai
  delayData: {
    type: Object,
    required: true,
    validator: (data) => {
      return data.startDate && data.endDate
    }
  },

  // Mode d'affichage
  mode: {
    type: String,
    default: 'badge',
    validator: (value) => ['badge', 'progress', 'alert', 'timeline'].includes(value)
  },

  // Taille pour le mode badge
  size: {
    type: String,
    default: 'normal',
    validator: (value) => ['small', 'normal', 'large'].includes(value)
  },

  // Options d'affichage
  showTarget: {
    type: Boolean,
    default: true
  },

  showTooltip: {
    type: Boolean,
    default: false
  },

  dismissible: {
    type: Boolean,
    default: false
  },

  // Actions suggérées
  suggestedActions: {
    type: Array,
    default: () => []
  },

  // Seuils personnalisés
  thresholds: {
    type: Object,
    default: () => ({
      warning: 80,    // 80% du délai écoulé
      critical: 100,  // 100% du délai dépassé
      danger: 120     // 120% délai largement dépassé
    })
  }
})

const emit = defineEmits(['action', 'dismiss'])

// Computed properties pour les calculs de délai
const now = computed(() => new Date())
const startDate = computed(() => new Date(props.delayData.startDate))
const endDate = computed(() => new Date(props.delayData.endDate))
const targetDate = computed(() => props.delayData.targetDate ? new Date(props.delayData.targetDate) : endDate.value)

const totalDuration = computed(() => endDate.value - startDate.value)
const elapsed = computed(() => now.value - startDate.value)
const progressPercentage = computed(() => Math.max(0, (elapsed.value / totalDuration.value) * 100))

const targetPercentage = computed(() => {
  if (!props.delayData.targetDate) return 100
  const targetElapsed = targetDate.value - startDate.value
  return Math.max(0, (targetElapsed / totalDuration.value) * 100)
})

const daysRemaining = computed(() => {
  const remaining = endDate.value - now.value
  return Math.ceil(remaining / (1000 * 60 * 60 * 24))
})

const isOverdue = computed(() => now.value > endDate.value)
const isCritical = computed(() => progressPercentage.value >= props.thresholds.critical)
const isWarning = computed(() => progressPercentage.value >= props.thresholds.warning)

// Computed properties pour le styling
const severity = computed(() => {
  if (progressPercentage.value >= props.thresholds.danger) return 'danger'
  if (progressPercentage.value >= props.thresholds.critical) return 'danger'
  if (progressPercentage.value >= props.thresholds.warning) return 'warning'
  return 'success'
})

const iconClass = computed(() => {
  if (isOverdue.value) return 'pi pi-exclamation-triangle'
  if (isCritical.value) return 'pi pi-clock'
  if (isWarning.value) return 'pi pi-info-circle'
  return 'pi pi-check-circle'
})

const badgeText = computed(() => {
  if (isOverdue.value) {
    return `${Math.abs(daysRemaining.value)}j de retard`
  }
  if (daysRemaining.value <= 0) {
    return 'Échéance atteinte'
  }
  if (daysRemaining.value === 1) {
    return '1 jour restant'
  }
  return `${daysRemaining.value} jours restants`
})

const progressText = computed(() => {
  return `${Math.round(progressPercentage.value)}%`
})

const progressLabel = computed(() => {
  if (isOverdue.value) return 'Délai dépassé'
  if (isCritical.value) return 'Délai critique'
  if (isWarning.value) return 'Attention délai'
  return 'Progression'
})

const alertTitle = computed(() => {
  if (isOverdue.value) return 'Intervention en retard'
  if (isCritical.value) return 'Délai critique atteint'
  if (isWarning.value) return 'Délai d\'attention'
  return 'Progression normale'
})

const alertMessage = computed(() => {
  if (isOverdue.value) {
    return `Cette intervention a ${Math.abs(daysRemaining.value)} jour(s) de retard. Une action immédiate est requise.`
  }
  if (isCritical.value) {
    return `L'échéance approche rapidement. Il reste ${daysRemaining.value} jour(s) pour terminer cette intervention.`
  }
  if (isWarning.value) {
    return `Attention, il ne reste que ${daysRemaining.value} jour(s) avant l'échéance.`
  }
  return 'L\'intervention progresse dans les délais prévus.'
})

const timelineStatusText = computed(() => {
  if (isOverdue.value) return `${Math.abs(daysRemaining.value)} jours de retard`
  if (daysRemaining.value <= 1) return 'Échéance imminente'
  return `${daysRemaining.value} jours restants`
})

const targetText = computed(() => {
  if (!props.delayData.targetDate) return ''
  const targetDays = Math.ceil((targetDate.value - now.value) / (1000 * 60 * 60 * 24))
  return `${targetDays} jours`
})

const tooltipText = computed(() => {
  return `Démarré le ${formatDate(startDate.value)}, échéance le ${formatDate(endDate.value)}`
})

// Classes CSS conditionnelles
const containerClasses = computed(() => ({
  'delay-indicator-critical': isCritical.value,
  'delay-indicator-warning': isWarning.value,
  'delay-indicator-overdue': isOverdue.value
}))

const badgeClasses = computed(() => ({
  'animate-pulse': isCritical.value
}))

const valueClasses = computed(() => {
  if (isOverdue.value) return 'text-red-600 dark:text-red-400'
  if (isCritical.value) return 'text-orange-600 dark:text-orange-400'
  if (isWarning.value) return 'text-yellow-600 dark:text-yellow-400'
  return 'text-green-600 dark:text-green-400'
})

const progressBarClasses = computed(() => {
  const baseClasses = ['transition-all', 'duration-500']

  if (isOverdue.value) {
    baseClasses.push('bg-red-500')
  } else if (isCritical.value) {
    baseClasses.push('bg-orange-500')
  } else if (isWarning.value) {
    baseClasses.push('bg-yellow-500')
  } else {
    baseClasses.push('bg-green-500')
  }

  return baseClasses
})

const alertClasses = computed(() => {
  if (isOverdue.value) return 'bg-red-50 border-red-400 dark:bg-red-900/20 dark:border-red-800'
  if (isCritical.value) return 'bg-orange-50 border-orange-400 dark:bg-orange-900/20 dark:border-orange-800'
  if (isWarning.value) return 'bg-yellow-50 border-yellow-400 dark:bg-yellow-900/20 dark:border-yellow-800'
  return 'bg-green-50 border-green-400 dark:bg-green-900/20 dark:border-green-800'
})

const titleClasses = computed(() => {
  if (isOverdue.value) return 'text-red-800 dark:text-red-200'
  if (isCritical.value) return 'text-orange-800 dark:text-orange-200'
  if (isWarning.value) return 'text-yellow-800 dark:text-yellow-200'
  return 'text-green-800 dark:text-green-200'
})

const textClasses = computed(() => {
  if (isOverdue.value) return 'text-red-700 dark:text-red-300'
  if (isCritical.value) return 'text-orange-700 dark:text-orange-300'
  if (isWarning.value) return 'text-yellow-700 dark:text-yellow-300'
  return 'text-green-700 dark:text-green-300'
})

const iconColorClasses = computed(() => {
  if (isOverdue.value) return 'text-red-600 dark:text-red-400'
  if (isCritical.value) return 'text-orange-600 dark:text-orange-400'
  if (isWarning.value) return 'text-yellow-600 dark:text-yellow-400'
  return 'text-green-600 dark:text-green-400'
})

const timelineBarClasses = computed(() => {
  if (isOverdue.value) return 'bg-red-500'
  if (isCritical.value) return 'bg-orange-500'
  if (isWarning.value) return 'bg-yellow-500'
  return 'bg-green-500'
})

const endPointClasses = computed(() => {
  if (isOverdue.value) return 'bg-red-500'
  if (isCritical.value) return 'bg-orange-500'
  return 'bg-green-500'
})

const statusTextClasses = computed(() => {
  if (isOverdue.value) return 'text-red-600 dark:text-red-400'
  if (isCritical.value) return 'text-orange-600 dark:text-orange-400'
  if (isWarning.value) return 'text-yellow-600 dark:text-yellow-400'
  return 'text-green-600 dark:text-green-400'
})

// Méthodes utilitaires
const formatDate = (date) => {
  return date.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short'
  })
}
</script>

<style scoped>
.delay-indicator {
  transition: all 0.3s ease;
}

.delay-indicator-critical {
  animation: criticalPulse 2s infinite;
}

@keyframes criticalPulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.8; }
}

.delay-progress .animate-pulse {
  animation: pulse 1s infinite;
}

.delay-timeline {
  min-width: 16rem;
}

/* Responsive adjustments */
@media (max-width: 640px) {
  .delay-timeline {
    min-width: 12rem;
  }
}
</style>