export const useClients = () => {
  const clientsStore = useClientsStore()

  return {
    // State
    clients: computed(() => clientsStore.clients),
    currentClient: computed(() => clientsStore.currentClient),
    loading: computed(() => clientsStore.loading),

    // Actions
    fetchClients: clientsStore.fetchClients,
    fetchClient: clientsStore.fetchClient,
    createClient: clientsStore.createClient,
    updateClient: clientsStore.updateClient,
    deleteClient: clientsStore.deleteClient,
    findClientsByLocation: clientsStore.findClientsByLocation,
  }
}