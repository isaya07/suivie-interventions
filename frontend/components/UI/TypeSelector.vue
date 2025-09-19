<template>
  <div class="type-selector">
    <!-- Mode simple (dropdown) -->
    <div v-if="mode === 'dropdown'" class="type-dropdown">
      <label v-if="label" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        {{ label }}
        <span v-if="required" class="text-red-500">*</span>
      </label>

      <Dropdown
        v-model="selectedType"
        :options="availableTypes"
        option-label="label"
        option-value="value"
        :placeholder="placeholder"
        :show-clear="!required"
        :filter="filterable"
        :class="dropdownClasses"
        @change="handleChange"
      >
        <template #option="{ option }">
          <div class="flex items-center space-x-3 p-2">
            <div class="flex-shrink-0">
              <i :class="option.icon" class="text-lg" :style="{ color: option.color }"></i>
            </div>
            <div class="flex-1 min-w-0">
              <div class="font-medium text-gray-900 dark:text-gray-100">
                {{ option.label }}
              </div>
              <div v-if="option.description" class="text-sm text-gray-500 dark:text-gray-400 truncate">
                {{ option.description }}
              </div>
            </div>
            <div v-if="option.reglementaire" class="flex-shrink-0">
              <Badge :value="option.reglementaire" severity="info" size="small" />
            </div>
          </div>
        </template>

        <template #value="{ value }">
          <div v-if="value" class="flex items-center space-x-2">
            <i :class="getSelectedTypeIcon(value)" class="text-lg" :style="{ color: getSelectedTypeColor(value) }"></i>
            <span>{{ getSelectedTypeLabel(value) }}</span>
          </div>
        </template>
      </Dropdown>

      <!-- Message d'aide -->
      <div v-if="helpText" class="mt-2 text-sm text-gray-600 dark:text-gray-400">
        <i class="pi pi-info-circle mr-1"></i>
        {{ helpText }}
      </div>
    </div>

    <!-- Mode cartes (sélection visuelle) -->
    <div v-else-if="mode === 'cards'" class="type-cards">
      <label v-if="label" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">
        {{ label }}
        <span v-if="required" class="text-red-500">*</span>
      </label>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="type in availableTypes"
          :key="type.value"
          class="type-card cursor-pointer rounded-lg border-2 p-4 transition-all duration-200 hover:shadow-md"
          :class="getCardClasses(type)"
          @click="selectType(type.value)"
        >
          <!-- En-tête de la carte -->
          <div class="flex items-center justify-between mb-3">
            <div class="flex items-center space-x-2">
              <i :class="type.icon" class="text-xl" :style="{ color: type.color }"></i>
              <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ type.label }}</h3>
            </div>
            <div v-if="selectedType === type.value" class="text-blue-600">
              <i class="pi pi-check-circle text-lg"></i>
            </div>
          </div>

          <!-- Description -->
          <p v-if="type.description" class="text-sm text-gray-600 dark:text-gray-400 mb-3">
            {{ type.description }}
          </p>

          <!-- Spécifications techniques -->
          <div v-if="type.specs" class="space-y-2">
            <div v-for="spec in type.specs" :key="spec.label" class="flex justify-between text-xs">
              <span class="text-gray-500">{{ spec.label }}:</span>
              <span class="font-medium text-gray-700 dark:text-gray-300">{{ spec.value }}</span>
            </div>
          </div>

          <!-- Badge réglementaire -->
          <div v-if="type.reglementaire" class="mt-3">
            <Badge :value="type.reglementaire" severity="info" size="small" />
          </div>

          <!-- Prix estimatif -->
          <div v-if="type.price && showPricing" class="mt-3 text-right">
            <span class="text-lg font-bold text-green-600">{{ formatPrice(type.price) }}</span>
          </div>
        </div>
      </div>

      <!-- Message d'aide pour les cartes -->
      <div v-if="helpText" class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
        <div class="flex items-start space-x-2">
          <i class="pi pi-info-circle text-blue-600 dark:text-blue-400 mt-0.5"></i>
          <p class="text-sm text-blue-800 dark:text-blue-200">{{ helpText }}</p>
        </div>
      </div>
    </div>

    <!-- Mode assistant guidé -->
    <div v-else-if="mode === 'wizard'" class="type-wizard">
      <div class="wizard-container bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
        <!-- En-tête du wizard -->
        <div class="wizard-header mb-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
            Assistant de sélection du type de branchement
          </h3>
          <p class="text-sm text-gray-600 dark:text-gray-400">
            Répondez à quelques questions pour identifier le type optimal
          </p>
        </div>

        <!-- Étapes du wizard -->
        <div class="wizard-steps">
          <!-- Étape 1: Type de raccordement -->
          <div v-if="wizardStep === 1" class="wizard-step">
            <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-3">
              Quel type de raccordement souhaitez-vous ?
            </h4>
            <div class="space-y-3">
              <label v-for="option in connectionTypes" :key="option.value" class="flex items-center space-x-3 cursor-pointer">
                <input
                  v-model="wizardData.connectionType"
                  type="radio"
                  :value="option.value"
                  class="text-blue-600 focus:ring-blue-500"
                />
                <div class="flex-1">
                  <div class="font-medium text-gray-900 dark:text-gray-100">{{ option.label }}</div>
                  <div class="text-sm text-gray-500">{{ option.description }}</div>
                </div>
              </label>
            </div>
          </div>

          <!-- Étape 2: Distance -->
          <div v-if="wizardStep === 2" class="wizard-step">
            <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-3">
              Quelle est la distance de la dérivation individuelle ?
            </h4>
            <div class="space-y-3">
              <InputNumber
                v-model="wizardData.distance"
                :min="0"
                :max="500"
                suffix=" m"
                placeholder="Distance en mètres"
                class="w-full"
              />
              <div class="text-sm text-gray-500">
                <i class="pi pi-info-circle mr-1"></i>
                Cette distance détermine la classification Type 1 (≤ 30m) ou Type 2 (> 30m)
              </div>
            </div>
          </div>

          <!-- Étape 3: Puissance -->
          <div v-if="wizardStep === 3" class="wizard-step">
            <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-3">
              Quelle puissance est nécessaire ?
            </h4>
            <div class="space-y-3">
              <label v-for="power in powerOptions" :key="power.value" class="flex items-center space-x-3 cursor-pointer">
                <input
                  v-model="wizardData.power"
                  type="radio"
                  :value="power.value"
                  class="text-blue-600 focus:ring-blue-500"
                />
                <div class="flex-1">
                  <div class="font-medium text-gray-900 dark:text-gray-100">{{ power.label }}</div>
                  <div class="text-sm text-gray-500">{{ power.description }}</div>
                </div>
              </label>
            </div>
          </div>

          <!-- Résultat -->
          <div v-if="wizardStep === 4" class="wizard-result">
            <div class="text-center mb-6">
              <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                <i class="pi pi-check text-2xl text-green-600"></i>
              </div>
              <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                Type recommandé identifié !
              </h4>
            </div>

            <div v-if="recommendedType" class="recommended-type border border-green-200 dark:border-green-800 rounded-lg p-4 bg-green-50 dark:bg-green-900/20">
              <div class="flex items-center justify-between mb-3">
                <div class="flex items-center space-x-3">
                  <i :class="recommendedType.icon" class="text-2xl" :style="{ color: recommendedType.color }"></i>
                  <div>
                    <h5 class="font-semibold text-gray-900 dark:text-gray-100">{{ recommendedType.label }}</h5>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ recommendedType.description }}</p>
                  </div>
                </div>
                <Badge :value="recommendedType.reglementaire" severity="success" />
              </div>

              <div v-if="recommendedType.specs" class="grid grid-cols-2 gap-3 mt-4">
                <div v-for="spec in recommendedType.specs" :key="spec.label" class="text-sm">
                  <span class="text-gray-500">{{ spec.label }}:</span>
                  <span class="font-medium text-gray-700 dark:text-gray-300 ml-1">{{ spec.value }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Navigation du wizard -->
        <div class="wizard-navigation flex justify-between mt-6">
          <Button
            v-if="wizardStep > 1"
            label="Précédent"
            icon="pi pi-chevron-left"
            severity="secondary"
            @click="previousStep"
          />
          <div class="flex-1"></div>
          <Button
            v-if="wizardStep < 4"
            label="Suivant"
            icon="pi pi-chevron-right"
            icon-pos="right"
            :disabled="!canProceed"
            @click="nextStep"
          />
          <Button
            v-else
            label="Sélectionner ce type"
            icon="pi pi-check"
            @click="selectRecommendedType"
          />
        </div>

        <!-- Indicateur de progression -->
        <div class="wizard-progress mt-4">
          <div class="flex justify-between text-xs text-gray-500 mb-1">
            <span>Étape {{ wizardStep }} sur 4</span>
            <span>{{ Math.round((wizardStep / 4) * 100) }}%</span>
          </div>
          <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
            <div
              class="bg-blue-600 h-2 rounded-full transition-all duration-300"
              :style="{ width: `${(wizardStep / 4) * 100}%` }"
            ></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Validation et erreurs -->
    <div v-if="error" class="mt-2 text-sm text-red-600 dark:text-red-400">
      <i class="pi pi-exclamation-triangle mr-1"></i>
      {{ error }}
    </div>

    <!-- Aperçu de la sélection -->
    <div v-if="selectedType && showPreview" class="type-preview mt-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
      <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">Type sélectionné :</h4>
      <div class="flex items-center space-x-3">
        <i :class="getSelectedTypeIcon(selectedType)" class="text-xl" :style="{ color: getSelectedTypeColor(selectedType) }"></i>
        <div>
          <div class="font-medium">{{ getSelectedTypeLabel(selectedType) }}</div>
          <div class="text-sm text-gray-600 dark:text-gray-400">{{ getSelectedTypeDescription(selectedType) }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  modelValue: {
    type: [String, Object],
    default: null
  },
  mode: {
    type: String,
    default: 'dropdown',
    validator: (value) => ['dropdown', 'cards', 'wizard'].includes(value)
  },
  label: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: 'Sélectionnez un type de branchement'
  },
  required: {
    type: Boolean,
    default: false
  },
  helpText: {
    type: String,
    default: ''
  },
  filterable: {
    type: Boolean,
    default: true
  },
  showPricing: {
    type: Boolean,
    default: false
  },
  showPreview: {
    type: Boolean,
    default: true
  },
  types: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['update:modelValue', 'change', 'selection-complete'])

// État local
const selectedType = ref(props.modelValue)
const error = ref('')
const wizardStep = ref(1)
const wizardData = ref({
  connectionType: null,
  distance: null,
  power: null
})

// Types de branchement Enedis par défaut
const defaultTypes = [
  {
    value: 'BRANCHEMENT_SEUL_MONOPHASE',
    label: 'Branchement seul monophasé',
    description: 'Raccordement direct sans terrassement, monophasé 36 kVA max',
    icon: 'pi pi-bolt',
    color: '#3b82f6',
    reglementaire: 'Type 1',
    specs: [
      { label: 'Puissance max', value: '36 kVA' },
      { label: 'Phase', value: 'Monophasé' },
      { label: 'Distance DI', value: '≤ 30m' }
    ],
    price: 850
  },
  {
    value: 'BRANCHEMENT_SEUL_TRIPHASE',
    label: 'Branchement seul triphasé',
    description: 'Raccordement direct sans terrassement, triphasé jusqu\'à 250 kVA',
    icon: 'pi pi-flash',
    color: '#f59e0b',
    reglementaire: 'Type 1/2',
    specs: [
      { label: 'Puissance max', value: '250 kVA' },
      { label: 'Phase', value: 'Triphasé' },
      { label: 'Distance DI', value: 'Variable' }
    ],
    price: 1250
  },
  {
    value: 'BRANCHEMENT_TERRASSEMENT',
    label: 'Branchement avec terrassement',
    description: 'Raccordement nécessitant des travaux de terrassement',
    icon: 'pi pi-cog',
    color: '#059669',
    reglementaire: 'Type 2',
    specs: [
      { label: 'Terrassement', value: 'Inclus' },
      { label: 'Distance DI', value: '> 30m' },
      { label: 'Complexité', value: 'Élevée' }
    ],
    price: 2800
  }
]

// Options pour le wizard
const connectionTypes = [
  { value: 'direct', label: 'Raccordement direct', description: 'Sans travaux de terrassement' },
  { value: 'terrassement', label: 'Avec terrassement', description: 'Nécessite des travaux de terrassement' }
]

const powerOptions = [
  { value: 'mono', label: 'Monophasé (≤ 36 kVA)', description: 'Pour usage domestique standard' },
  { value: 'tri_standard', label: 'Triphasé standard (≤ 100 kVA)', description: 'Pour usage professionnel léger' },
  { value: 'tri_forte', label: 'Triphasé forte puissance (> 100 kVA)', description: 'Pour usage industriel' }
]

// Types disponibles (props.types ou défaut)
const availableTypes = computed(() => {
  return props.types.length > 0 ? props.types : defaultTypes
})

// Computed properties pour le wizard
const canProceed = computed(() => {
  switch (wizardStep.value) {
    case 1: return wizardData.value.connectionType !== null
    case 2: return wizardData.value.distance !== null && wizardData.value.distance >= 0
    case 3: return wizardData.value.power !== null
    default: return true
  }
})

const recommendedType = computed(() => {
  if (wizardStep.value < 4) return null

  const { connectionType, distance, power } = wizardData.value

  // Logique de recommandation basée sur les réponses
  if (connectionType === 'terrassement') {
    return availableTypes.value.find(t => t.value === 'BRANCHEMENT_TERRASSEMENT')
  }

  if (distance > 30) {
    // Type 2 nécessaire
    if (power === 'tri_forte') {
      return availableTypes.value.find(t => t.value === 'BRANCHEMENT_SEUL_TRIPHASE')
    }
    return availableTypes.value.find(t => t.reglementaire?.includes('Type 2'))
  }

  // Type 1 possible
  if (power === 'mono') {
    return availableTypes.value.find(t => t.value === 'BRANCHEMENT_SEUL_MONOPHASE')
  }

  return availableTypes.value.find(t => t.value === 'BRANCHEMENT_SEUL_TRIPHASE')
})

// Computed properties pour le styling
const dropdownClasses = computed(() => {
  const classes = ['w-full']
  if (error.value) {
    classes.push('!border-red-500')
  }
  return classes
})

// Méthodes
const handleChange = (event) => {
  selectedType.value = event.value
  emit('update:modelValue', event.value)
  emit('change', event.value)
  validateSelection()
}

const selectType = (value) => {
  selectedType.value = value
  emit('update:modelValue', value)
  emit('change', value)
  validateSelection()
}

const validateSelection = () => {
  if (props.required && !selectedType.value) {
    error.value = 'La sélection d\'un type est obligatoire'
  } else {
    error.value = ''
  }
}

const getCardClasses = (type) => {
  const classes = []

  if (selectedType.value === type.value) {
    classes.push('border-blue-500 bg-blue-50 dark:bg-blue-900/20')
  } else {
    classes.push('border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600')
  }

  return classes
}

const getSelectedTypeIcon = (value) => {
  const type = availableTypes.value.find(t => t.value === value)
  return type?.icon || 'pi pi-bolt'
}

const getSelectedTypeColor = (value) => {
  const type = availableTypes.value.find(t => t.value === value)
  return type?.color || '#3b82f6'
}

const getSelectedTypeLabel = (value) => {
  const type = availableTypes.value.find(t => t.value === value)
  return type?.label || value
}

const getSelectedTypeDescription = (value) => {
  const type = availableTypes.value.find(t => t.value === value)
  return type?.description || ''
}

const formatPrice = (price) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
    minimumFractionDigits: 0
  }).format(price)
}

// Méthodes du wizard
const nextStep = () => {
  if (canProceed.value && wizardStep.value < 4) {
    wizardStep.value++
  }
}

const previousStep = () => {
  if (wizardStep.value > 1) {
    wizardStep.value--
  }
}

const selectRecommendedType = () => {
  if (recommendedType.value) {
    selectType(recommendedType.value.value)
    emit('selection-complete', recommendedType.value)
  }
}

// Watchers
watch(() => props.modelValue, (newValue) => {
  selectedType.value = newValue
}, { immediate: true })

watch(selectedType, validateSelection)
</script>

<style scoped>
.type-card {
  transition: all 0.2s ease;
}

.type-card:hover {
  transform: translateY(-4px);
}

.wizard-container {
  box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
}

.wizard-step {
  min-height: 8rem;
}

.recommended-type {
  box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
}

/* Animation pour les étapes du wizard */
.wizard-step {
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}
</style>