<template>
  <div class="timeline-container">
    <div class="timeline-wrapper">
      <!-- Timeline principale -->
      <div class="relative">
        <!-- Ligne de progression principale -->
        <div class="absolute inset-0 flex items-center" aria-hidden="true">
          <div class="w-full border-t border-gray-300 dark:border-gray-600"></div>
        </div>

        <!-- Étapes de la timeline -->
        <div class="relative flex justify-between">
          <div
            v-for="(etape, index) in etapes"
            :key="etape.id"
            class="timeline-step"
            :class="getStepClasses(etape, index)"
          >
            <!-- Cercle de l'étape -->
            <div
              class="timeline-circle"
              :class="getCircleClasses(etape)"
            >
              <!-- Icône de l'étape -->
              <i
                v-if="etape.icon"
                :class="etape.icon"
                class="text-sm"
              ></i>
              <!-- Numéro d'étape si pas d'icône -->
              <span v-else class="text-sm font-medium">{{ index + 1 }}</span>
            </div>

            <!-- Contenu de l'étape -->
            <div class="timeline-content mt-3">
              <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                {{ etape.titre }}
              </h3>
              <p v-if="etape.description" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ etape.description }}
              </p>

              <!-- Date de l'étape -->
              <div v-if="etape.date" class="mt-1 flex items-center text-xs text-gray-500">
                <i class="pi pi-calendar mr-1"></i>
                {{ formatDate(etape.date) }}
              </div>

              <!-- Durée si disponible -->
              <div v-if="etape.duree" class="mt-1 flex items-center text-xs text-gray-500">
                <i class="pi pi-clock mr-1"></i>
                {{ etape.duree }}
              </div>

              <!-- Badge de statut -->
              <Badge
                v-if="etape.statut"
                :value="etape.statut"
                :severity="getStatusSeverity(etape.statut)"
                size="small"
                class="mt-2"
              />

              <!-- Actions de l'étape -->
              <div v-if="etape.actions && etape.actions.length > 0" class="mt-2 flex gap-1">
                <Button
                  v-for="action in etape.actions"
                  :key="action.label"
                  :label="action.label"
                  :icon="action.icon"
                  :severity="action.severity || 'secondary'"
                  size="small"
                  text
                  @click="$emit('action', action.type, etape)"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Mode vertical pour mobile -->
      <div v-if="vertical" class="timeline-vertical lg:hidden">
        <div class="flow-root">
          <ul class="-mb-8">
            <li
              v-for="(etape, index) in etapes"
              :key="etape.id"
              class="timeline-item-vertical"
            >
              <div class="relative pb-8">
                <!-- Ligne verticale -->
                <span
                  v-if="index !== etapes.length - 1"
                  class="absolute top-5 left-5 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-600"
                  aria-hidden="true"
                ></span>

                <div class="relative flex items-start space-x-3">
                  <!-- Cercle vertical -->
                  <div
                    class="timeline-circle-vertical"
                    :class="getCircleClasses(etape)"
                  >
                    <i
                      v-if="etape.icon"
                      :class="etape.icon"
                      class="text-sm"
                    ></i>
                    <span v-else class="text-sm font-medium">{{ index + 1 }}</span>
                  </div>

                  <!-- Contenu vertical -->
                  <div class="min-w-0 flex-1">
                    <div>
                      <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        {{ etape.titre }}
                      </h3>
                      <p v-if="etape.description" class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ etape.description }}
                      </p>
                    </div>

                    <!-- Métadonnées verticales -->
                    <div class="mt-2 flex flex-wrap gap-3 text-xs text-gray-500">
                      <div v-if="etape.date" class="flex items-center">
                        <i class="pi pi-calendar mr-1"></i>
                        {{ formatDate(etape.date) }}
                      </div>
                      <div v-if="etape.duree" class="flex items-center">
                        <i class="pi pi-clock mr-1"></i>
                        {{ etape.duree }}
                      </div>
                    </div>

                    <!-- Badge et actions verticales -->
                    <div class="mt-2 flex items-center gap-2">
                      <Badge
                        v-if="etape.statut"
                        :value="etape.statut"
                        :severity="getStatusSeverity(etape.statut)"
                        size="small"
                      />

                      <div v-if="etape.actions && etape.actions.length > 0" class="flex gap-1">
                        <Button
                          v-for="action in etape.actions"
                          :key="action.label"
                          :icon="action.icon"
                          :severity="action.severity || 'secondary'"
                          size="small"
                          text
                          rounded
                          v-tooltip="action.label"
                          @click="$emit('action', action.type, etape)"
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Résumé global -->
    <div v-if="showSummary" class="timeline-summary mt-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
        <div>
          <div class="text-lg font-semibold text-green-600">{{ etapesCompletes }}</div>
          <div class="text-xs text-gray-600 dark:text-gray-400">Terminées</div>
        </div>
        <div>
          <div class="text-lg font-semibold text-orange-600">{{ etapesEnCours }}</div>
          <div class="text-xs text-gray-600 dark:text-gray-400">En cours</div>
        </div>
        <div>
          <div class="text-lg font-semibold text-blue-600">{{ etapesRestantes }}</div>
          <div class="text-xs text-gray-600 dark:text-gray-400">Restantes</div>
        </div>
        <div>
          <div class="text-lg font-semibold text-purple-600">{{ dureeTotal }}</div>
          <div class="text-xs text-gray-600 dark:text-gray-400">Durée totale</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  etapes: {
    type: Array,
    required: true,
    validator: (etapes) => {
      return etapes.every(etape =>
        etape.id && etape.titre && ['pending', 'active', 'completed', 'error'].includes(etape.status)
      )
    }
  },
  vertical: {
    type: Boolean,
    default: false
  },
  showSummary: {
    type: Boolean,
    default: true
  },
  interactive: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['action', 'step-click'])

// Computed properties pour les statistiques
const etapesCompletes = computed(() =>
  props.etapes.filter(etape => etape.status === 'completed').length
)

const etapesEnCours = computed(() =>
  props.etapes.filter(etape => etape.status === 'active').length
)

const etapesRestantes = computed(() =>
  props.etapes.filter(etape => etape.status === 'pending').length
)

const dureeTotal = computed(() => {
  const total = props.etapes.reduce((acc, etape) => {
    if (etape.dureeMinutes) {
      return acc + etape.dureeMinutes
    }
    return acc
  }, 0)

  if (total < 60) return `${total}min`
  return `${Math.round(total / 60)}h ${total % 60}min`
})

// Méthodes pour le styling
const getStepClasses = (etape, index) => {
  const classes = ['relative']

  if (props.interactive && etape.status !== 'pending') {
    classes.push('cursor-pointer')
  }

  return classes
}

const getCircleClasses = (etape) => {
  const baseClasses = [
    'relative', 'flex', 'h-10', 'w-10', 'items-center', 'justify-center',
    'rounded-full', 'border-2', 'transition-all', 'duration-200'
  ]

  switch (etape.status) {
    case 'completed':
      baseClasses.push(
        'bg-green-600', 'border-green-600', 'text-white',
        'hover:bg-green-700', 'hover:border-green-700'
      )
      break
    case 'active':
      baseClasses.push(
        'bg-blue-600', 'border-blue-600', 'text-white',
        'hover:bg-blue-700', 'hover:border-blue-700', 'ring-4', 'ring-blue-200'
      )
      break
    case 'error':
      baseClasses.push(
        'bg-red-600', 'border-red-600', 'text-white',
        'hover:bg-red-700', 'hover:border-red-700'
      )
      break
    default: // pending
      baseClasses.push(
        'bg-white', 'border-gray-300', 'text-gray-500',
        'dark:bg-gray-700', 'dark:border-gray-600', 'dark:text-gray-400'
      )
  }

  return baseClasses
}

const getStatusSeverity = (statut) => {
  switch (statut?.toLowerCase()) {
    case 'terminé':
    case 'completed':
      return 'success'
    case 'en cours':
    case 'active':
      return 'info'
    case 'en retard':
    case 'error':
      return 'danger'
    case 'en attente':
    case 'pending':
      return 'warning'
    default:
      return 'secondary'
  }
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>

<style scoped>
.timeline-container {
  width: 100%;
}

.timeline-wrapper {
  position: relative;
}

.timeline-step {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.timeline-circle {
  position: relative;
  z-index: 10;
}

.timeline-circle-vertical {
  position: relative;
  display: flex;
  height: 2rem;
  width: 2rem;
  align-items: center;
  justify-content: center;
  border-radius: 9999px;
  border-width: 2px;
  transition: all 0.2s ease;
}

.timeline-content {
  text-align: center;
  max-width: 6rem;
}

@media (min-width: 1024px) {
  .timeline-content {
    max-width: 8rem;
  }
}

.timeline-item-vertical:last-child .timeline-circle-vertical {
  margin-bottom: 0;
}

.timeline-summary {
  border: 1px solid rgb(229 231 235);
}

.dark .timeline-summary {
  border-color: rgb(55 65 81);
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .timeline-step {
    font-size: 0.75rem;
    line-height: 1rem;
  }

  .timeline-content {
    max-width: 5rem;
  }
}

/* Animation pour les étapes actives */
.timeline-circle.ring-4 {
  animation: pulse-ring 2s infinite;
}

@keyframes pulse-ring {
  0% {
    box-shadow: 0 0 0 4px rgb(191 219 254);
  }
  50% {
    box-shadow: 0 0 0 6px rgb(147 197 253);
  }
  100% {
    box-shadow: 0 0 0 4px rgb(191 219 254);
  }
}

/* Dark mode ring */
.dark .timeline-circle.ring-4 {
  box-shadow: 0 0 0 4px rgb(30 64 175);
}
</style>