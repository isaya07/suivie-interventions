import { defineStore } from 'pinia'
import { ref, computed, readonly } from 'vue'

export const useClientsStore = defineStore('clients', () => {
  const clients = ref([])
  const currentClient = ref(null)
  const loading = ref(false)
  const error = ref(null)

  const { $api } = useNuxtApp()

  // Actions
  const fetchClients = async () => {
    loading.value = true
    error.value = null
    try {
      const response = await $api('/clients.php')
      if (response.records) {
        clients.value = response.records
      }
    } catch (err) {
      error.value = 'Erreur lors de la récupération des clients'
      console.error('❌ Erreur lors de la récupération des clients:', err)
    } finally {
      loading.value = false
    }
  }

  const fetchClient = async (id) => {
    loading.value = true
    try {
      const response = await $api(`/clients.php?id=${id}`)
      currentClient.value = response
      return response
    } catch (error) {
      console.error('❌ Erreur lors de la récupération du client:', error)
      return null
    } finally {
      loading.value = false
    }
  }

  const createClient = async (clientData) => {
    loading.value = true
    try {
      const response = await $api('/clients.php', {
        method: 'POST',
        body: JSON.stringify(clientData)
      })

      if (response.id) {
        await fetchClients() // Reload the list
        return { success: true, data: response }
      }
      return { success: false, message: response.message }
    } catch (error) {
      console.error('❌ Erreur lors de la création du client:', error)
      return { success: false, message: 'Erreur lors de la création' }
    } finally {
      loading.value = false
    }
  }

  const updateClient = async (clientData) => {
    loading.value = true
    try {
      const response = await $api('/clients.php', {
        method: 'PUT',
        body: JSON.stringify(clientData)
      })

      if (response.message && response.message.includes('succès')) {
        await fetchClients() // Reload the list
        if (currentClient.value && currentClient.value.id === clientData.id) {
          await fetchClient(clientData.id) // Reload current client
        }
        return { success: true, data: response }
      }

      return { success: false, message: response.message }
    } catch (error) {
      console.error('❌ Erreur lors de la mise à jour du client:', error)
      return { success: false, message: 'Erreur lors de la mise à jour' }
    } finally {
      loading.value = false
    }
  }

  const deleteClient = async (id) => {
    loading.value = true
    try {
      const response = await $api('/clients.php', {
        method: 'DELETE',
        body: JSON.stringify({ id })
      })

      if (response.message && response.message.includes('succès')) {
        await fetchClients() // Reload the list
        if (currentClient.value && currentClient.value.id === id) {
          currentClient.value = null
        }
        return { success: true, data: response }
      }

      return { success: false, message: response.message }
    } catch (error) {
      console.error('❌ Erreur lors de la suppression du client:', error)
      return { success: false, message: 'Erreur lors de la suppression' }
    } finally {
      loading.value = false
    }
  }

  const findClientsByLocation = async (lat, lng, radius = 10) => {
    loading.value = true
    try {
      const response = await $api(`/clients.php?location=1&lat=${lat}&lng=${lng}&radius=${radius}`)
      if (response.records) {
        return response.records
      }
      return []
    } catch (error) {
      console.error('❌ Erreur lors de la recherche par localisation:', error)
      return []
    } finally {
      loading.value = false
    }
  }

  // Getters
  const getClientById = computed(() => (id) => {
    return clients.value.find(client => client.id == id)
  })

  const clientsWithGPS = computed(() => {
    return clients.value.filter(client => client.latitude && client.longitude)
  })

  return {
    // State
    clients: readonly(clients),
    currentClient: readonly(currentClient),
    loading: readonly(loading),
    error: readonly(error),

    // Getters
    getClientById,
    clientsWithGPS,

    // Actions
    fetchClients,
    fetchClient,
    createClient,
    updateClient,
    deleteClient,
    findClientsByLocation,
  }
})