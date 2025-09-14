<template>
  <form @submit.prevent="handleSubmit" class="space-y-6">
    <!-- Informations générales -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Nom du client *
        </label>
        <input
          v-model="form.nom"
          type="text"
          required
          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          placeholder="Nom de l'entreprise ou du particulier"
        >
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Contact principal
        </label>
        <input
          v-model="form.contact_principal"
          type="text"
          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          placeholder="Nom du contact"
        >
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Email
        </label>
        <input
          v-model="form.email"
          type="email"
          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          placeholder="contact@client.com"
        >
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Téléphone
        </label>
        <input
          v-model="form.telephone"
          type="tel"
          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          placeholder="01.23.45.67.89"
        >
      </div>
    </div>

    <!-- Adresse -->
    <div class="space-y-4">
      <h3 class="text-lg font-medium text-gray-900">📍 Adresse</h3>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Adresse complète
        </label>
        <textarea
          v-model="form.adresse"
          rows="2"
          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          placeholder="Numéro, rue, bâtiment..."
        ></textarea>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Ville
          </label>
          <input
            v-model="form.ville"
            type="text"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            placeholder="Nom de la ville"
          >
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Code postal
          </label>
          <input
            v-model="form.code_postal"
            type="text"
            pattern="[0-9]{5}"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            placeholder="75001"
          >
        </div>
      </div>
    </div>

    <!-- Coordonnées GPS -->
    <div class="space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-medium text-gray-900">🌍 Coordonnées GPS</h3>
        <button
          type="button"
          @click="getCurrentLocation"
          :disabled="loadingLocation"
          class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
        >
          <svg v-if="loadingLocation" class="animate-spin -ml-1 mr-2 h-4 w-4 text-blue-700" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <svg v-else class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
          Ma position
        </button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Latitude
          </label>
          <input
            v-model="form.latitude"
            type="number"
            step="0.000001"
            min="-90"
            max="90"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            placeholder="48.856614"
          >
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Longitude
          </label>
          <input
            v-model="form.longitude"
            type="number"
            step="0.000001"
            min="-180"
            max="180"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            placeholder="2.352222"
          >
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
      <textarea
        v-model="form.notes"
        rows="3"
        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
        placeholder="Informations complémentaires, accès spécifique, etc."
      ></textarea>
    </div>

    <!-- Boutons d'action -->
    <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
      <button
        type="button"
        @click="$emit('cancel')"
        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
      >
        Annuler
      </button>
      <button
        type="submit"
        :disabled="loading"
        class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        {{ client?.id ? 'Mettre à jour' : 'Créer' }}
      </button>
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