<template>
  <div
    class="intervention-card bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 cursor-pointer transition-all duration-200 hover:shadow-md"
    :class="{
      'border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/10': urgent,
      'border-blue-300 dark:border-blue-700': priorite === 'haute',
      'hover:border-blue-400 dark:hover:border-blue-600': !urgent
    }"
    @click="$emit('view', intervention.id)"
  >
    <!-- En-tête de la carte -->
    <div class="flex items-start justify-between mb-3">
      <div class="flex-1 min-w-0">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">
          {{ intervention.numero }}
        </h3>
        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">
          {{ intervention.titre }}
        </p>
      </div>
      <div class="flex-shrink-0 ml-2">
        <Badge
          :value="intervention.priorite"
          :severity="getPrioriteSeverity(intervention.priorite)"
          size="small"
        />
      </div>
    </div>

    <!-- Informations client et type -->
    <div class="space-y-2 mb-3">
      <div class="flex items-center gap-2">
        <i class="pi pi-user text-xs text-gray-500"></i>
        <span class="text-xs text-gray-700 dark:text-gray-300 truncate">
          {{ intervention.client_nom }}
        </span>
      </div>

      <div class="flex items-center gap-2">
        <i :class="getTypeIcon(intervention.type_reglementaire)" class="text-xs"></i>
        <span class="text-xs text-gray-700 dark:text-gray-300">
          {{ getTypeLabel(intervention.type_reglementaire) }}
        </span>
      </div>
    </div>

    <!-- Assignation technicien -->
    <div v-if="intervention.technicien_branchement || intervention.technicien_terrassement" class="mb-3">
      <div class="flex items-center gap-1 mb-1">
        <i class="pi pi-users text-xs text-gray-500"></i>
        <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Assigné à:</span>
      </div>
      <div class="space-y-1">
        <div v-if="intervention.technicien_branchement" class="flex items-center gap-2">
          <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
          <span class="text-xs text-gray-600 dark:text-gray-400">
            {{ intervention.technicien_branchement }}
          </span>
        </div>
        <div v-if="intervention.technicien_terrassement" class="flex items-center gap-2">
          <div class="w-2 h-2 bg-orange-500 rounded-full"></div>
          <span class="text-xs text-gray-600 dark:text-gray-400">
            {{ intervention.technicien_terrassement }}
          </span>
        </div>
      </div>
    </div>

    <!-- Date prévue -->
    <div v-if="intervention.date_prevue" class="mb-3">
      <div class="flex items-center gap-2">
        <i class="pi pi-calendar text-xs text-gray-500"></i>
        <span class="text-xs text-gray-700 dark:text-gray-300">
          {{ formatDate(intervention.date_prevue) }}
        </span>
      </div>
    </div>

    <!-- Indicateur de retard -->
    <div v-if="urgent" class="mb-3">
      <div class="flex items-center gap-2 text-red-600 dark:text-red-400">
        <i class="pi pi-exclamation-triangle text-xs"></i>
        <span class="text-xs font-medium">
          {{ getRetardMessage(intervention) }}
        </span>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex gap-1 pt-3 border-t border-gray-100 dark:border-gray-700">
      <!-- Action d'assignation -->
      <Button
        v-if="!intervention.technicien_branchement && !intervention.technicien_terrassement"
        icon="pi pi-user-plus"
        size="small"
        severity="info"
        text
        v-tooltip="'Assigner'"
        @click.stop="$emit('assign', intervention)"
      />

      <!-- Action de completion -->
      <Button
        v-if="intervention.statut === 'en_cours'"
        icon="pi pi-check"
        size="small"
        severity="success"
        text
        v-tooltip="'Terminer'"
        @click.stop="$emit('complete', intervention)"
      />

      <!-- Action de priorisation -->
      <Button
        v-if="urgent"
        icon="pi pi-arrow-up"
        size="small"
        severity="warning"
        text
        v-tooltip="'Prioriser'"
        @click.stop="$emit('prioritize', intervention)"
      />

      <!-- Action de planification -->
      <Button
        v-if="intervention.statut === 'en_attente'"
        icon="pi pi-calendar-plus"
        size="small"
        severity="secondary"
        text
        v-tooltip="'Planifier'"
        @click.stop="$emit('schedule', intervention)"
      />

      <!-- Spacer -->
      <div class="flex-1"></div>

      <!-- Bouton voir détails -->
      <Button
        icon="pi pi-eye"
        size="small"
        severity="secondary"
        text
        v-tooltip="'Voir détails'"
        @click.stop="$emit('view', intervention.id)"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  intervention: {
    type: Object,
    required: true
  },
  compact: {
    type: Boolean,
    default: false
  },
  urgent: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['view', 'assign', 'complete', 'prioritize', 'schedule'])

// Computed pour la priorité calculée
const priorite = computed(() => {
  if (props.urgent) return 'urgente'
  return props.intervention.priorite || 'normale'
})

// Méthodes
const getPrioriteSeverity = (priorite) => {
  switch (priorite?.toLowerCase()) {
    case 'urgente': return 'danger'
    case 'haute': return 'warning'
    case 'normale': return 'info'
    case 'basse': return 'secondary'
    default: return 'secondary'
  }
}

const getTypeIcon = (type) => {
  switch (type) {
    case 'type_1': return 'pi pi-flag text-blue-500'
    case 'type_2': return 'pi pi-flag text-orange-500'
    default: return 'pi pi-flag text-gray-500'
  }
}

const getTypeLabel = (type) => {
  switch (type) {
    case 'type_1': return 'Type 1'
    case 'type_2': return 'Type 2'
    default: return 'Non défini'
  }
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short'
  })
}

const getRetardMessage = (intervention) => {
  const dateCreation = new Date(intervention.date_creation)
  const joursEcoules = Math.floor((new Date() - dateCreation) / (1000 * 60 * 60 * 24))

  if (joursEcoules > 21) {
    return `${joursEcoules - 21}j de retard`
  }
  return 'Délai critique'
}
</script>

<style scoped>
.intervention-card {
  min-height: 180px;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Animation au survol */
.intervention-card:hover {
  transform: translateY(-1px);
}

/* Style pour les cartes urgentes */
.intervention-card.border-red-300 {
  box-shadow: 0 0 0 1px rgba(239, 68, 68, 0.1);
}

/* Dark mode adjustments */
.dark .intervention-card {
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.3);
}

.dark .intervention-card:hover {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
}
</style>