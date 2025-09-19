<template>
  <div>
    <!-- En-tête -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
          Clients
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
          Gérez vos clients et leurs informations
        </p>
      </div>
      <Button
        label="Nouveau client"
        icon="pi pi-plus"
        @click="showCreateModal = true"
        class="p-button-primary"
      />
    </div>
    <!-- Filtres rapides -->
    <Card class="mb-6">
      <template #content>
        <div class="flex flex-wrap items-end gap-4">
          <div class="flex-1 min-w-64">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Recherche
            </label>
            <InputText
              v-model="searchTerm"
              placeholder="Nom, email, ville..."
              class="w-full"
              icon="pi pi-search"
            />
          </div>
          <div class="min-w-48">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Affichage
            </label>
            <Select
              v-model="viewMode"
              :options="viewModeOptions"
              optionLabel="label"
              optionValue="value"
              class="w-full"
            />
          </div>
        </div>
      </template>
    </Card>
    <!-- Liste des clients -->
    <Card>
      <template #content>
        <div v-if="loading" class="flex justify-center py-8">
          <ProgressSpinner style="width: 50px; height: 50px" strokeWidth="4" />
        </div>
        <div v-else>
          <DataTable
            :value="filteredClients"
            :paginator="true"
            :rows="10"
            :rowsPerPageOptions="[5, 10, 20, 50]"
            :totalRecords="filteredClients.length"
            paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
            currentPageReportTemplate="Affichage de {first} à {last} sur {totalRecords} clients"
            class="p-datatable-sm"
            sortField="nom"
            :sortOrder="1"
            :globalFilterFields="[
              'nom',
              'email',
              'ville',
              'contact_principal',
              'telephone',
            ]"
          >
            <Column field="nom" header="Client" :sortable="true">
              <template #body="{ data }">
                <div class="flex items-center">
                  <Avatar
                    :label="data.nom?.[0] || 'C'"
                    class="mr-3"
                    style="background-color: #3b82f6; color: white"
                    shape="circle"
                  />
                  <div>
                    <div class="text-sm font-medium text-gray-900">
                      {{ data.nom }}
                    </div>
                    <div class="text-sm text-gray-500">
                      {{ data.contact_principal || "Pas de contact" }}
                    </div>
                  </div>
                </div>
              </template>
            </Column>
            <Column header="Contact" :sortable="false">
              <template #body="{ data }">
                <div>
                  <div class="text-sm text-gray-900">
                    {{ data.email || "-" }}
                  </div>
                  <div class="text-sm text-gray-500">
                    {{ data.telephone || "-" }}
                  </div>
                </div>
              </template>
            </Column>
            <Column header="Adresse" :sortable="false">
              <template #body="{ data }">
                <div class="text-sm text-gray-900 max-w-xs">
                  <div v-if="data.ville || data.code_postal">
                    {{ data.ville }} {{ data.code_postal }}
                  </div>
                  <div v-if="data.adresse" class="text-gray-500 truncate">
                    {{ data.adresse }}
                  </div>
                  <div
                    v-if="!data.ville && !data.code_postal && !data.adresse"
                    class="text-gray-400"
                  >
                    Pas d'adresse
                  </div>
                </div>
              </template>
            </Column>
            <Column header="GPS" :sortable="false">
              <template #body="{ data }">
                <div
                  v-if="data.latitude && data.longitude"
                  class="flex items-center text-green-600"
                >
                  <i class="pi pi-map-marker mr-1"></i>
                  <span class="text-xs">{{
                    formatCoordinate(data.latitude, data.longitude)
                  }}</span>
                </div>
                <div v-else class="text-gray-400 text-xs">
                  <i class="pi pi-times"></i> Pas de GPS
                </div>
              </template>
            </Column>
            <Column header="Actions" :sortable="false" style="width: 150px">
              <template #body="{ data }">
                <div class="flex space-x-1">
                  <Button
                    outlined
                    size="small"
                    @click="$router.push(`/clients/${data.id}`)"
                    v-tooltip.top="'Voir les détails'"
                  >
                    <i class="pi pi-eye"></i>
                  </Button>
                  <Button
                    outlined
                    size="small"
                    @click="editClient(data)"
                    v-tooltip.top="'Modifier'"
                  >
                    <i class="pi pi-pencil"></i>
                  </Button>
                  <Button
                    outlined
                    size="small"
                    severity="danger"
                    @click="deleteClientConfirm(data)"
                    v-tooltip.top="'Supprimer'"
                  >
                    <i class="pi pi-trash"></i>
                  </Button>
                </div>
              </template>
            </Column>
            <template #empty>
              <div class="text-center py-8">
                <i class="pi pi-users text-gray-400 text-4xl mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">
                  Aucun client trouvé
                </h3>
                <p class="text-sm text-gray-500">
                  Aucun client ne correspond à vos critères.
                </p>
              </div>
            </template>
          </DataTable>
        </div>
      </template>
    </Card>
  </div>
  <!-- Modal de création/édition -->
  <Dialog
    v-model:visible="showCreateModal"
    :modal="true"
    header="Nouveau client"
    :style="{ width: '50rem' }"
    :maximizable="true"
    :closable="true"
  >
    <ClientForm
      :client="null"
      @submit="handleClientSubmit"
      @cancel="showCreateModal = false"
    />
  </Dialog>
  <Dialog
    v-model:visible="showEditModal"
    :modal="true"
    header="Modifier le client"
    :style="{ width: '50rem' }"
    :maximizable="true"
    :closable="true"
  >
    <ClientForm
      :client="selectedClient"
      @submit="handleClientSubmit"
      @cancel="showEditModal = false"
    />
  </Dialog>
  <!-- Confirmation de suppression -->
  <ConfirmDialog />
</template>
<script setup>
definePageMeta({
  middleware: ["auth"],
});
const { $confirm } = useNuxtApp();
const { user: currentUser } = useAuth();
const {
  clients,
  fetchClients,
  createClient,
  updateClient,
  deleteClient,
  loading,
} = useClients();
const showCreateModal = ref(false);
const showEditModal = ref(false);
const selectedClient = ref(null);
const searchTerm = ref("");
const viewMode = ref("all");
const viewModeOptions = [
  { label: "Tous les clients", value: "all" },
  { label: "Avec coordonnées GPS", value: "with-gps" },
  { label: "Sans coordonnées GPS", value: "without-gps" },
];
const filteredClients = computed(() => {
  let filtered = clients.value;
  // Filtrer par mode d'affichage
  if (viewMode.value === "with-gps") {
    filtered = filtered.filter((client) => client.latitude && client.longitude);
  } else if (viewMode.value === "without-gps") {
    filtered = filtered.filter(
      (client) => !client.latitude || !client.longitude
    );
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
  if (!lat || !lng) return "";
  return `${parseFloat(lat).toFixed(4)}, ${parseFloat(lng).toFixed(4)}`;
};
const editClient = (client) => {
  selectedClient.value = client;
  showEditModal.value = true;
};
const deleteClientConfirm = async (client) => {
  $confirm.require({
    message: `Êtes-vous sûr de vouloir supprimer le client "${client.nom}" ?`,
    header: "Confirmation de suppression",
    icon: "pi pi-exclamation-triangle",
    accept: async () => {
      const result = await deleteClient(client.id);
      if (result.success) {
        $toast.add({
          severity: "success",
          summary: "Succès",
          detail: "Client supprimé avec succès",
          life: 3000,
        });
      } else {
        $toast.add({
          severity: "error",
          summary: "Erreur",
          detail: result.message,
          life: 3000,
        });
      }
    },
  });
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
    $toast.add({
      severity: "success",
      summary: "Succès",
      detail: showCreateModal.value
        ? "Client créé avec succès"
        : "Client modifié avec succès",
      life: 3000,
    });
  } else {
    $toast.add({
      severity: "error",
      summary: "Erreur",
      detail: result.message,
      life: 3000,
    });
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
