<template>
  <form @submit.prevent="handleSubmit" class="space-y-6">
    <!-- Informations générales -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Nom du client *
        </label>
        <InputText
          v-model="form.nom"
          type="text"
          required
          placeholder="Nom de l'entreprise ou du particulier"
          class="w-full"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Contact principal
        </label>
        <InputText
          v-model="form.contact_principal"
          type="text"
          placeholder="Nom du contact"
          class="w-full"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Email
        </label>
        <InputText
          v-model="form.email"
          type="email"
          placeholder="contact@client.com"
          class="w-full"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Téléphone
        </label>
        <InputText
          v-model="form.telephone"
          type="tel"
          placeholder="01.23.45.67.89"
          class="w-full"
        />
      </div>
    </div>

    <!-- Adresse -->
    <div class="space-y-4">
      <h3 class="text-lg font-medium text-gray-900">📍 Adresse</h3>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Adresse complète
        </label>
        <Textarea
          v-model="form.adresse"
          rows="2"
          placeholder="Numéro, rue, bâtiment..."
          class="w-full"
        />
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Ville
          </label>
          <InputText
            v-model="form.ville"
            type="text"
            placeholder="Nom de la ville"
            class="w-full"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Code postal
          </label>
          <InputText
            v-model="form.code_postal"
            type="text"
            placeholder="75001"
            class="w-full"
          />
        </div>
      </div>
    </div>

    <!-- Coordonnées GPS -->
    <div class="space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-medium text-gray-900">🌍 Coordonnées GPS</h3>
        <Button
          type="button"
          @click="getCurrentLocation"
          :disabled="loadingLocation"
          :loading="loadingLocation"
          icon="pi pi-map-marker"
          label="Ma position"
          severity="info"
          outlined
          size="small"
        />
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Latitude
          </label>
          <InputNumber
            v-model="form.latitude"
            :step="0.000001"
            :min="-90"
            :max="90"
            placeholder="48.856614"
            class="w-full"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Longitude
          </label>
          <InputNumber
            v-model="form.longitude"
            :step="0.000001"
            :min="-180"
            :max="180"
            placeholder="2.352222"
            class="w-full"
          />
        </div>
      </div>

      <!-- Aide pour les coordonnées -->
      <div class="text-sm text-gray-500 bg-blue-50 p-3 rounded-md">
        <p class="font-medium">💡 Comment obtenir les coordonnées GPS :</p>
        <ul class="mt-1 list-disc list-inside space-y-1">
          <li>Cliquez sur "Ma position" pour utiliser votre localisation actuelle</li>
          <li>Ou cherchez l'adresse sur Google Maps et cliquez droit → "Que trouve-t-on ici ?"</li>
          <li>Les coordonnées apparaîtront en bas de l'écran</li>
        </ul>
      </div>
    </div>

    <!-- Notes -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">
        Notes
      </label>
      <Textarea
        v-model="form.notes"
        rows="3"
        placeholder="Informations complémentaires, accès spécifique, etc."
        class="w-full"
      />
    </div>

    <!-- Boutons d'action -->
    <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
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
        :label="client?.id ? 'Mettre à jour' : 'Créer'"
      />
    </div>
  </form>
</template>

<script setup>
const props = defineProps({
  client: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['submit', 'cancel'])

const loading = ref(false)
const loadingLocation = ref(false)

// Formulaire réactif
const form = reactive({
  nom: '',
  email: '',
  telephone: '',
  adresse: '',
  ville: '',
  code_postal: '',
  contact_principal: '',
  notes: '',
  latitude: null,
  longitude: null
})

// Initialiser le formulaire avec les données du client si fourni
watch(() => props.client, (newClient) => {
  if (newClient) {
    Object.assign(form, {
      nom: newClient.nom || '',
      email: newClient.email || '',
      telephone: newClient.telephone || '',
      adresse: newClient.adresse || '',
      ville: newClient.ville || '',
      code_postal: newClient.code_postal || '',
      contact_principal: newClient.contact_principal || '',
      notes: newClient.notes || '',
      latitude: newClient.latitude || null,
      longitude: newClient.longitude || null
    })
  } else {
    // Reset form for new client
    Object.assign(form, {
      nom: '',
      email: '',
      telephone: '',
      adresse: '',
      ville: '',
      code_postal: '',
      contact_principal: '',
      notes: '',
      latitude: null,
      longitude: null
    })
  }
}, { immediate: true })

const getCurrentLocation = () => {
  if (!navigator.geolocation) {
    alert('La géolocalisation n\'est pas supportée par votre navigateur')
    return
  }

  loadingLocation.value = true

  navigator.geolocation.getCurrentPosition(
    (position) => {
      form.latitude = position.coords.latitude
      form.longitude = position.coords.longitude
      loadingLocation.value = false
    },
    (error) => {
      console.error('Erreur de géolocalisation:', error)
      let message = 'Impossible d\'obtenir votre position'

      switch(error.code) {
        case error.PERMISSION_DENIED:
          message = 'Autorisation de géolocalisation refusée'
          break
        case error.POSITION_UNAVAILABLE:
          message = 'Position non disponible'
          break
        case error.TIMEOUT:
          message = 'Timeout de géolocalisation'
          break
      }

      alert(message)
      loadingLocation.value = false
    },
    {
      enableHighAccuracy: true,
      timeout: 10000,
      maximumAge: 300000
    }
  )
}

const handleSubmit = async () => {
  loading.value = true
  try {
    const submitData = { ...form }

    // Ajouter l'ID si on modifie un client existant
    if (props.client?.id) {
      submitData.id = props.client.id
    }

    // Convertir les valeurs vides en null pour les coordonnées GPS
    if (submitData.latitude === '') submitData.latitude = null
    if (submitData.longitude === '') submitData.longitude = null

    emit('submit', submitData)
  } finally {
    loading.value = false
  }
}
</script>