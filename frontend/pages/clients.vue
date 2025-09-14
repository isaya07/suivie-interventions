<template>
  <div class="min-h-screen bg-gray-50">
    <div class="bg-white shadow">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center py-6">
          <div class="flex items-center">
            <h1 class="text-2xl font-bold text-gray-900">
              <i class="fas fa-users mr-3"></i>
              Gestion des Clients
            </h1>
          </div>
          <button
            @click="showCreateForm = true"
            class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg inline-flex items-center"
          >
            <i class="fas fa-plus mr-2"></i>
            Nouveau Client
          </button>
        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div v-if="loading" class="text-center py-8">
        <div class="inline-flex items-center px-4 py-2 font-semibold leading-6 text-sm shadow rounded-md text-white bg-blue-500">
          <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          Chargement des clients...
        </div>
      </div>

      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-md p-4">
        <div class="flex">
          <i class="fas fa-exclamation-circle text-red-400 mr-2 mt-1"></i>
          <div>
            <h3 class="text-sm font-medium text-red-800">
              Erreur de chargement
            </h3>
            <div class="mt-2 text-sm text-red-700">
              {{ error }}
            </div>
          </div>
        </div>
      </div>

      <div v-else class="bg-white shadow overflow-hidden sm:rounded-md">
        <div v-if="clients.length === 0" class="text-center py-12">
          <i class="fas fa-users text-gray-400 text-6xl mb-4"></i>
          <h3 class="mt-2 text-sm font-medium text-gray-900">Aucun client</h3>
          <p class="mt-1 text-sm text-gray-500">Commencez par créer votre premier client.</p>
        </div>

        <ul v-else role="list" class="divide-y divide-gray-200">
          <li
            v-for="client in clients"
            :key="client.id"
            class="px-6 py-4 hover:bg-gray-50 cursor-pointer"
            @click="editClient(client)"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <div class="flex-shrink-0 h-10 w-10">
                  <div class="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center">
                    <i class="fas fa-building text-white"></i>
                  </div>
                </div>
                <div class="ml-4">
                  <div class="text-sm font-medium text-gray-900">
                    {{ client.nom }}
                  </div>
                  <div class="text-sm text-gray-500">
                    {{ client.email || 'Pas d\'email' }}
                  </div>
                </div>
              </div>
              <div class="flex items-center space-x-4 text-sm text-gray-500">
                <div v-if="client.telephone" class="flex items-center">
                  <i class="fas fa-phone mr-1"></i>
                  {{ client.telephone }}
                </div>
                <div v-if="client.ville" class="flex items-center">
                  <i class="fas fa-map-marker-alt mr-1"></i>
                  {{ client.ville }}
                </div>
                <div class="flex items-center space-x-2">
                  <button
                    @click.stop="editClient(client)"
                    class="text-blue-600 hover:text-blue-900"
                  >
                    <i class="fas fa-edit"></i>
                  </button>
                  <button
                    @click.stop="deleteClient(client.id)"
                    class="text-red-600 hover:text-red-900"
                  >
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </div>
            </div>
          </li>
        </ul>
      </div>
    </div>

    <!-- Modal de création/édition -->
    <div v-if="showCreateForm || editingClient" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">
              {{ editingClient ? 'Modifier le client' : 'Nouveau client' }}
            </h3>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <ClientForm
            :client="editingClient"
            @saved="onClientSaved"
            @cancel="closeModal"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useClientsStore } from '~/stores/clients'

definePageMeta({
  middleware: 'auth'
})

const clientsStore = useClientsStore()
const { clients, loading, error } = storeToRefs(clientsStore)

const showCreateForm = ref(false)
const editingClient = ref(null)

onMounted(async () => {
  await clientsStore.fetchClients()
})

const editClient = (client) => {
  editingClient.value = { ...client }
}

const deleteClient = async (clientId) => {
  if (confirm('Êtes-vous sûr de vouloir supprimer ce client ?')) {
    await clientsStore.deleteClient(clientId)
  }
}

const onClientSaved = async () => {
  closeModal()
  await clientsStore.fetchClients()
}

const closeModal = () => {
  showCreateForm.value = false
  editingClient.value = null
}
</script>