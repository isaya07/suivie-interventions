<template>
  <form @submit.prevent="handleSubmit" class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="md:col-span-2">
        <label for="titre" class="block text-sm font-medium text-gray-700">
          Titre *
        </label>
        <InputText
          id="titre"
          v-model="form.titre"
          type="text"
          required
          :invalid="!!errors.titre"
          placeholder="Titre de l'intervention"
          class="w-full"
        />
        <p v-if="errors.titre" class="mt-1 text-sm text-red-600">
          {{ errors.titre }}
        </p>
      </div>
      
      <div class="md:col-span-2">
        <label for="description" class="block text-sm font-medium text-gray-700">
          Description *
        </label>
        <Textarea
          id="description"
          v-model="form.description"
          required
          rows="4"
          :invalid="!!errors.description"
          placeholder="Description détaillée de l'intervention"
          class="w-full"
        />
        <p v-if="errors.description" class="mt-1 text-sm text-red-600">
          {{ errors.description }}
        </p>
      </div>
      
      <div>
        <label for="technicien_id" class="block text-sm font-medium text-gray-700">
          Technicien assigné *
        </label>
        <Select
          id="technicien_id"
          v-model="form.technicien_id"
          :options="techniciens"
          optionLabel="nom"
          optionValue="id"
          placeholder="Sélectionner un technicien"
          :invalid="!!errors.technicien_id"
          class="w-full"
        />
        <p v-if="errors.technicien_id" class="mt-1 text-sm text-red-600">
          {{ errors.technicien_id }}
        </p>
      </div>
      
      <div>
        <label for="priorite" class="block text-sm font-medium text-gray-700">
          Priorité
        </label>
        <Select
          id="priorite"
          v-model="form.priorite"
          :options="[
            { label: 'Basse', value: 'basse' },
            { label: 'Normale', value: 'normale' },
            { label: 'Haute', value: 'haute' },
            { label: 'Urgente', value: 'urgente' }
          ]"
          optionLabel="label"
          optionValue="value"
          class="w-full"
        />
      </div>
      
      <div>
        <label for="date_prevue" class="block text-sm font-medium text-gray-700">
          Date prévue
        </label>
        <DatePicker
          id="date_prevue"
          v-model="form.date_prevue"
          dateFormat="dd/mm/yy"
          class="w-full"
        />
      </div>
      
      <div>
        <label for="lieu" class="block text-sm font-medium text-gray-700">
          Lieu
        </label>
        <InputText
          id="lieu"
          v-model="form.lieu"
          type="text"
          placeholder="Lieu de l'intervention"
          class="w-full"
        />
      </div>
    </div>
    
    <div class="flex justify-end space-x-3">
      <Button
        type="button"
        @click="$emit('cancel')"
        label="Annuler"
        severity="secondary"
        outlined
      />
      <Button
        type="submit"
        :disabled="loading"
        :loading="loading"
        :label="loading ? 'Enregistrement...' : (isEditing ? 'Modifier' : 'Créer')"
      />
    </div>
  </form>
</template>

<script setup>
const props = defineProps({
  intervention: {
    type: Object,
    default: null
  }
})

defineEmits(['submit', 'cancel'])

const { techniciens, fetchTechniciens } = useUsers()
const { createIntervention, updateIntervention } = useInterventions()

const loading = ref(false)
const errors = ref({})

const isEditing = computed(() => !!props.intervention)

const form = ref({
  titre: '',
  description: '',
  technicien_id: '',
  priorite: 'normale',
  date_prevue: '',
  lieu: ''
})

const validateForm = () => {
  errors.value = {}
  
  if (!form.value.titre.trim()) {
    errors.value.titre = 'Le titre est requis'
  }
  
  if (!form.value.description.trim()) {
    errors.value.description = 'La description est requise'
  }
  
  if (!form.value.technicien_id) {
    errors.value.technicien_id = 'Le technicien est requis'
  }
  
  return Object.keys(errors.value).length === 0
}

const handleSubmit = async () => {
  if (!validateForm()) return
  
  loading.value = true
  
  try {
    let result
    
    if (isEditing.value) {
      result = await updateIntervention({
        ...form.value,
        id: props.intervention.id
      })
    } else {
      result = await createIntervention(form.value)
    }
    
    if (result.success) {
      $emit('submit', result.data)
    } else {
      // Gérer les erreurs
      if (result.errors) {
        errors.value = result.errors
      }
    }
  } catch (error) {
    console.error('Erreur lors de la soumission:', error)
  } finally {
    loading.value = false
  }
}

// Initialiser le formulaire si on édite
watch(() => props.intervention, (intervention) => {
  if (intervention) {
    form.value = {
      titre: intervention.titre || '',
      description: intervention.description || '',
      technicien_id: intervention.technicien_id || '',
      priorite: intervention.priorite || 'normale',
      date_prevue: intervention.date_prevue || '',
      lieu: intervention.lieu || ''
    }
  }
}, { immediate: true })

onMounted(() => {
  fetchTechniciens()
})
</script>