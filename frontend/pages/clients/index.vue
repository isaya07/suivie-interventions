<template>
  <div>
    <AppHeader />

    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
      <div class="px-4 py-6 sm:px-0">
        <!-- En-tête -->
        <div class="flex justify-between items-center mb-6">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Clients</h1>
            <p class="text-gray-600">Gérez vos clients et leurs informations</p>
          </div>

          <button
            @click="showCreateModal = true"
            class="btn-primary flex items-center"
          >
            <svg
              class="w-5 h-5 mr-2"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 6v6m0 0v6m0-6h6m-6 0H6"
              ></path>
            </svg>
            Nouveau client
          </button>
        </div>

        <!-- Filtres rapides -->
        <div class="card mb-6">
          <div class="flex flex-wrap items-center gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Recherche
              </label>
              <input
                v-model="searchTerm"
                type="text"
                class="form-input w-64"
                placeholder="Nom, email, ville..."
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Affichage
              </label>
              <select v-model="viewMode" class="form-input w-48">
                <option value="all">Tous les clients</option>
                <option value="with-gps">Avec coordonnées GPS</option>
                <option value="without-gps">Sans coordonnées GPS</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Liste des clients -->
        <div v-if="loading" class="flex justify-center py-8">
          <div
            class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"
          ></div>
        </div>

        <div v-else class="card">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                  >
                    Client
                  </th>
                  <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                  >
                    Contact
                  </th>
                  <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                  >
                    Adresse
                  </th>
                  <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                  >
                    GPS
                  </th>
                  <th
                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
                  >
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr
                  v-for="client in filteredClients"
                  :key="client.id"
                  class="hover:bg-gray-50"
                >
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div
                        class="h-10 w-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-medium"
                      >
                        {{ client.nom?.[0] || "C" }}
                      </div>
                      <div class="ml-4">
                        <div class="text-sm font-medium text-gray-900">
                          {{ client.nom }}
                        </div>
                        <div class="text-sm text-gray-500">
                          {{ client.contact_principal || 'Pas de contact' }}
                        </div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">
                      {{ client.email || '-' }}
                    </div>
                    <div class="text-sm text-gray-500">
                      {{ client.telephone || '-' }}
                    </div>
                  </td>
                  <td class="px-6 py-4">
                    <div class="text-sm text-gray-900 max-w-xs">
                      <div v-if="client.ville || client.code_postal">
                        {{ client.ville }} {{ client.code_postal }}
                      </div>
                      <div v-if="client.adresse" class="text-gray-500 truncate">
                        {{ client.adresse }}
                      </div>
                      <div v-if="!client.ville && !client.code_postal && !client.adresse" class="text-gray-400">
                        Pas d'adresse
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div v-if="client.latitude && client.longitude" class="flex items-center text-green-600">
                      <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                      </svg>
                      <span class="text-xs">
                        {{ formatCoordinate(client.latitude, client.longitude) }}
                      </span>
                    </div>
                    <div v-else class="text-gray-400 text-xs">
                      Pas de GPS
                    </div>
                  </td>
                  <td
                    class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2"
                  >
                    <NuxtLink
                      :to="`/clients/${client.id}`"
                      class="text-blue-600 hover:text-blue-800"
                    >
                      Voir
                    </NuxtLink>
                    <button
                      @click="editClient(client)"
                      class="text-gray-600 hover:text-gray-800"
                    >
                      Modifier
                    </button>
                    <button
                      @click="deleteClientConfirm(client)"
                      class="text-red-600 hover:text-red-800"
                    >
                      Supprimer
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="filteredClients.length === 0" class="text-center py-8">
            <svg
              class="mx-auto h-12 w-12 text-gray-400"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
              ></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">
              Aucun client trouvé
            </h3>
            <p class="mt-1 text-sm text-gray-500">
              Aucun client ne correspond à vos critères.
            </p>
          </div>
        </div>
      </div>
    </main>

    <!-- Modal de création/édition -->
    <div
      v-if="showCreateModal || showEditModal"
      class="fixed inset-0 z-50 overflow-y-auto"
      aria-labelledby="modal-title"
      role="dialog"
      aria-modal="true"
    >
      <div
        class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0"
      >
        <div
          class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
          @click="closeModals"
        ></div>

        <div
          class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full"
        >
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
              {{
                showCreateModal
                  ? "Nouveau client"
                  : "Modifier le client"
              }}
            </h3>

            <ClientForm
              :client="selectedClient"
              @submit="handleClientSubmit"
              @cancel="closeModals"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
definePageMeta({
  middleware: ["auth"],
});

const { user: currentUser } = useAuth();
const { clients, fetchClients, createClient, updateClient, deleteClient, loading } = useClients();

const showCreateModal = ref(false);
const showEditModal = ref(false);
const selectedClient = ref(null);
const searchTerm = ref("");
const viewMode = ref("all");

const filteredClients = computed(() => {
  let filtered = clients.value;

  // Filtrer par mode d'affichage
  if (viewMode.value === 'with-gps') {
    filtered = filtered.filter(client => client.latitude && client.longitude);
  } else if (viewMode.value === 'without-gps') {
    filtered = filtered.filter(client => !client.latitude || !client.longitude);
  }

  // Filtrer par terme de recherche
  if (searchTerm.value) {
    const term = searchTerm.value.toLowerCase();
    filtered = filtered.filter(
      (client) =>
        client.nom?.toLowerCase().includes(term) ||
        client.email?.toLowerCase().includes(term) ||
        client.ville?.toLowerCase().includes(term) ||
        client.contact_principal?.toLowerCase().includes(term) ||
        client.telephone?.includes(term)
    );
  }

  return filtered;
});

const formatCoordinate = (lat, lng) => {
  if (!lat || !lng) return '';
  return `${parseFloat(lat).toFixed(4)}, ${parseFloat(lng).toFixed(4)}`;
};

const editClient = (client) => {
  selectedClient.value = client;
  showEditModal.value = true;
};

const deleteClientConfirm = async (client) => {
  if (confirm(`Êtes-vous sûr de vouloir supprimer le client "${client.nom}" ?`)) {
    const result = await deleteClient(client.id);
    if (result.success) {
      // Success handled by store
    } else {
      alert(`Erreur: ${result.message}`);
    }
  }
};

const handleClientSubmit = async (clientData) => {
  let result;

  if (showCreateModal.value) {
    result = await createClient(clientData);
  } else {
    result = await updateClient(clientData);
  }

  if (result.success) {
    closeModals();
  } else {
    alert(`Erreur: ${result.message}`);
  }
};

const closeModals = () => {
  showCreateModal.value = false;
  showEditModal.value = false;
  selectedClient.value = null;
};

onMounted(() => {
  fetchClients();
});
</script>