<template>
  <Card
    class="metrics-card transition-all duration-300 hover:shadow-lg"
    :class="cardClasses"
  >
    <template #content>
      <div class="metrics-content">
        <!-- En-tête de la métrique -->
        <div class="metrics-header flex items-start justify-between mb-4">
          <div class="flex items-center">
            <!-- Icône de la métrique -->
            <div
              class="metrics-icon flex-shrink-0 p-3 rounded-lg mr-3"
              :class="iconClasses"
            >
              <i :class="metric.icon" class="text-xl"></i>
            </div>

            <!-- Titre et description -->
            <div class="min-w-0 flex-1">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 truncate">
                {{ metric.title }}
              </h3>
              <p v-if="metric.description" class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                {{ metric.description }}
              </p>
            </div>
          </div>

          <!-- Menu d'actions -->
          <div v-if="showMenu" class="relative">
            <Button
              icon="pi pi-ellipsis-v"
              text
              rounded
              size="small"
              class="!text-gray-400 hover:!text-gray-600"
              @click="toggleMenu"
            />

            <div
              v-if="menuVisible"
              class="absolute right-0 top-8 z-10 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 min-w-[8rem]"
            >
              <button
                v-for="action in menuActions"
                :key="action.label"
                class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center"
                @click="handleMenuAction(action)"
              >
                <i :class="action.icon" class="mr-2 text-xs"></i>
                {{ action.label }}
              </button>
            </div>
          </div>
        </div>

        <!-- Valeur principale -->
        <div class="metrics-value mb-4">
          <div class="flex items-baseline">
            <span
              class="text-3xl lg:text-4xl font-bold tracking-tight"
              :class="valueClasses"
            >
              {{ formattedValue }}
            </span>
            <span v-if="metric.unit" class="ml-2 text-lg text-gray-500 dark:text-gray-400">
              {{ metric.unit }}
            </span>
          </div>

          <!-- Évolution / Tendance -->
          <div v-if="metric.change !== undefined" class="flex items-center mt-2">
            <div
              class="flex items-center px-2 py-1 rounded-full text-sm font-medium"
              :class="changeClasses"
            >
              <i
                :class="changeIcon"
                class="mr-1 text-xs"
              ></i>
              {{ Math.abs(metric.change) }}%
            </div>
            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">
              vs {{ metric.changePeriod || 'période précédente' }}
            </span>
          </div>
        </div>

        <!-- Graphique mini ou barre de progression -->
        <div v-if="showProgress || showChart" class="metrics-visual mb-4">
          <!-- Barre de progression -->
          <div v-if="showProgress" class="space-y-2">
            <div class="flex justify-between text-sm">
              <span class="text-gray-600 dark:text-gray-400">Progression</span>
              <span class="font-medium text-gray-900 dark:text-gray-100">
                {{ Math.round(progressPercentage) }}%
              </span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
              <div
                class="h-2 rounded-full transition-all duration-300"
                :class="progressBarClasses"
                :style="{ width: `${Math.min(progressPercentage, 100)}%` }"
              ></div>
            </div>
            <div v-if="metric.target" class="flex justify-between text-xs text-gray-500">
              <span>{{ metric.current || metric.value }}</span>
              <span>Objectif: {{ metric.target }}</span>
            </div>
          </div>

          <!-- Mini graphique (simulation) -->
          <div v-if="showChart" class="chart-container h-20 bg-gray-50 dark:bg-gray-700 rounded flex items-end justify-center p-2">
            <div v-for="(point, index) in chartData" :key="index"
                 class="bg-blue-500 dark:bg-blue-400 rounded-t mx-px flex-1 transition-all duration-300"
                 :style="{ height: `${point}%` }"
            ></div>
          </div>
        </div>

        <!-- Actions rapides -->
        <div v-if="quickActions && quickActions.length > 0" class="metrics-actions flex gap-2">
          <Button
            v-for="action in quickActions"
            :key="action.label"
            :label="action.label"
            :icon="action.icon"
            :severity="action.severity || 'secondary'"
            size="small"
            text
            @click="$emit('action', action.type, metric)"
          />
        </div>

        <!-- Footer avec métadonnées -->
        <div v-if="showFooter" class="metrics-footer mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
            <div v-if="metric.lastUpdate" class="flex items-center">
              <i class="pi pi-clock mr-1"></i>
              {{ formatLastUpdate(metric.lastUpdate) }}
            </div>
            <div v-if="metric.source" class="flex items-center">
              <i class="pi pi-database mr-1"></i>
              {{ metric.source }}
            </div>
          </div>
        </div>
      </div>
    </template>
  </Card>
</template>

<script setup>
const props = defineProps({
  metric: {
    type: Object,
    required: true,
    validator: (metric) => {
      return metric.title && (metric.value !== undefined || metric.current !== undefined)
    }
  },
  variant: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'compact', 'detailed', 'chart'].includes(value)
  },
  color: {
    type: String,
    default: 'blue',
    validator: (value) => ['blue', 'green', 'red', 'orange', 'purple', 'gray'].includes(value)
  },
  showProgress: {
    type: Boolean,
    default: false
  },
  showChart: {
    type: Boolean,
    default: false
  },
  showMenu: {
    type: Boolean,
    default: false
  },
  showFooter: {
    type: Boolean,
    default: true
  },
  quickActions: {
    type: Array,
    default: () => []
  },
  menuActions: {
    type: Array,
    default: () => [
      { label: 'Actualiser', icon: 'pi pi-refresh', type: 'refresh' },
      { label: 'Exporter', icon: 'pi pi-download', type: 'export' }
    ]
  }
})

const emit = defineEmits(['action', 'menu-action'])

// État local
const menuVisible = ref(false)

// Computed properties
const formattedValue = computed(() => {
  const value = props.metric.value ?? props.metric.current

  if (typeof value === 'number') {
    if (props.metric.format === 'currency') {
      return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
      }).format(value)
    }

    if (props.metric.format === 'percentage') {
      return `${value}%`
    }

    if (value >= 1000000) {
      return `${(value / 1000000).toFixed(1)}M`
    }

    if (value >= 1000) {
      return `${(value / 1000).toFixed(1)}k`
    }

    return value.toLocaleString('fr-FR')
  }

  return value
})

const progressPercentage = computed(() => {
  if (!props.metric.target) return 0
  const current = props.metric.current ?? props.metric.value
  return (current / props.metric.target) * 100
})

const cardClasses = computed(() => {
  const classes = ['metrics-card']

  if (props.variant === 'compact') {
    classes.push('!p-4')
  }

  if (props.metric.highlight) {
    classes.push('ring-2 ring-blue-500 ring-opacity-50')
  }

  return classes
})

const iconClasses = computed(() => {
  const colorMap = {
    blue: 'bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-300',
    green: 'bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-300',
    red: 'bg-red-100 text-red-600 dark:bg-red-900 dark:text-red-300',
    orange: 'bg-orange-100 text-orange-600 dark:bg-orange-900 dark:text-orange-300',
    purple: 'bg-purple-100 text-purple-600 dark:bg-purple-900 dark:text-purple-300',
    gray: 'bg-gray-100 text-gray-600 dark:bg-gray-900 dark:text-gray-300'
  }

  return colorMap[props.color] || colorMap.blue
})

const valueClasses = computed(() => {
  const colorMap = {
    blue: 'text-blue-600 dark:text-blue-400',
    green: 'text-green-600 dark:text-green-400',
    red: 'text-red-600 dark:text-red-400',
    orange: 'text-orange-600 dark:text-orange-400',
    purple: 'text-purple-600 dark:text-purple-400',
    gray: 'text-gray-600 dark:text-gray-400'
  }

  return colorMap[props.color] || 'text-gray-900 dark:text-gray-100'
})

const changeClasses = computed(() => {
  if (props.metric.change > 0) {
    return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
  } else if (props.metric.change < 0) {
    return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
  }
  return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
})

const changeIcon = computed(() => {
  if (props.metric.change > 0) {
    return 'pi pi-arrow-up'
  } else if (props.metric.change < 0) {
    return 'pi pi-arrow-down'
  }
  return 'pi pi-minus'
})

const progressBarClasses = computed(() => {
  const percentage = progressPercentage.value

  if (percentage >= 90) {
    return 'bg-green-500'
  } else if (percentage >= 70) {
    return 'bg-blue-500'
  } else if (percentage >= 50) {
    return 'bg-orange-500'
  }
  return 'bg-red-500'
})

const chartData = computed(() => {
  if (props.metric.chartData) {
    return props.metric.chartData
  }

  // Données simulées pour démonstration
  return Array.from({ length: 12 }, () => Math.random() * 100)
})

// Méthodes
const toggleMenu = () => {
  menuVisible.value = !menuVisible.value
}

const handleMenuAction = (action) => {
  menuVisible.value = false
  emit('menu-action', action.type, props.metric)
}

const formatLastUpdate = (timestamp) => {
  const date = new Date(timestamp)
  const now = new Date()
  const diff = now - date

  if (diff < 60000) {
    return 'À l\'instant'
  } else if (diff < 3600000) {
    return `Il y a ${Math.floor(diff / 60000)} min`
  } else if (diff < 86400000) {
    return `Il y a ${Math.floor(diff / 3600000)}h`
  }

  return date.toLocaleDateString('fr-FR')
}

// Fermer le menu lors d'un clic extérieur
onMounted(() => {
  const handleClickOutside = (event) => {
    if (!event.target.closest('.metrics-card')) {
      menuVisible.value = false
    }
  }

  document.addEventListener('click', handleClickOutside)

  onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
  })
})
</script>

<style scoped>
.metrics-card {
  transition: all 0.3s ease;
}

.metrics-card:hover {
  transform: translateY(-4px);
}

.chart-container {
  position: relative;
  overflow: hidden;
}

.metrics-visual .chart-container > div:hover {
  background-color: rgb(37 99 235);
}

.dark .metrics-visual .chart-container > div:hover {
  background-color: rgb(147 197 253);
}

/* Animation pour les valeurs qui changent */
@keyframes valueChange {
  0% { transform: scale(1); }
  50% { transform: scale(1.05); }
  100% { transform: scale(1); }
}

.metrics-value .text-3xl {
  animation: valueChange 0.3s ease-in-out;
}

/* Responsive adjustments */
@media (max-width: 640px) {
  .metrics-content {
    padding: 0.25rem;
  }

  .metrics-value .text-3xl {
    font-size: 1.5rem;
    line-height: 2rem;
  }
}
</style>