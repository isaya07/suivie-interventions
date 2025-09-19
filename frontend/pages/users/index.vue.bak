<template>
  <div>
    <AppHeader />

    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
      <div class="px-4 py-6 sm:px-0">
        <!-- En-tête -->
        <div class="flex justify-between items-center mb-6">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Utilisateurs</h1>
            <p class="text-gray-600 dark:text-gray-400">Gérez les utilisateurs et techniciens</p>
          </div>

          <Button
            label="Nouvel utilisateur"
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
                  placeholder="Nom, email..."
                  class="w-full"
                  icon="pi pi-search"
                />
              </div>

              <div class="min-w-48">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Rôle
                </label>
                <Select
                  v-model="roleFilter"
                  :options="roleOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Tous les rôles"
                  class="w-full"
                  @change="applyFilters"
                />
              </div>

              <div class="min-w-48">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Type technicien
                </label>
                <Select
                  v-model="typeFilter"
                  :options="typeOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Tous les types"
                  :disabled="roleFilter !== 'technicien'"
                  class="w-full"
                  @change="applyFilters"
                />
              </div>
            </div>
          </template>
        </Card>

        <!-- Liste des utilisateurs -->
        <Card>
          <template #content>
            <div v-if="loading" class="flex justify-center py-8">
              <ProgressSpinner style="width: 50px; height: 50px" strokeWidth="4" />
            </div>

            <div v-else>
              <DataTable
                :value="filteredUsers"
                :paginator="true"
                :rows="10"
                :rowsPerPageOptions="[5, 10, 20, 50]"
                :totalRecords="filteredUsers.length"
                paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                currentPageReportTemplate="Affichage de {first} à {last} sur {totalRecords} utilisateurs"
                class="p-datatable-sm"
                sortField="nom"
                :sortOrder="1"
                :globalFilterFields="['nom', 'email']"
              >
                <Column field="nom" header="Utilisateur" :sortable="true">
                  <template #body="{ data }">
                    <div class="flex items-center">
                      <Avatar
                        :label="data.nom?.[0] || 'U'"
                        class="mr-3"
                        style="background-color: #3b82f6; color: white"
                        shape="circle"
                      />
                      <div>
                        <div class="text-sm font-medium text-gray-900">{{ data.nom }}</div>
                        <div class="text-sm text-gray-500">{{ data.email }}</div>
                      </div>
                    </div>
                  </template>
                </Column>

                <Column header="Rôle" :sortable="true" sortField="role">
                  <template #body="{ data }">
                    <div class="flex flex-col space-y-1">
                      <Badge
                        :value="getRoleText(data.role)"
                        :severity="getRoleSeverity(data.role)"
                        size="small"
                      />
                      <Badge
                        v-if="data.role === 'technicien' && data.type_technicien"
                        :value="getTypeText(data.type_technicien)"
                        :severity="getTypeSeverity(data.type_technicien)"
                        size="small"
                      />
                    </div>
                  </template>
                </Column>

                <Column header="Contact" :sortable="false">
                  <template #body="{ data }">
                    <div>
                      <div class="text-sm text-gray-900">{{ data.telephone || '-' }}</div>
                      <div class="text-sm text-gray-500">{{ formatDate(data.date_creation) }}</div>
                    </div>
                  </template>
                </Column>

                <Column header="Statut" :sortable="true" sortField="is_active">
                  <template #body="{ data }">
                    <Badge
                      :value="data.is_active ? 'Actif' : 'Inactif'"
                      :severity="data.is_active ? 'success' : 'danger'"
                      size="small"
                    />
                  </template>
                </Column>

                <Column header="Actions" :sortable="false" style="width: 150px">
                  <template #body="{ data }">
                    <div class="flex space-x-1">
                      <Button
                        icon="pi pi-eye"
                        outlined
                        size="small"
                        @click="$router.push(`/users/${data.id}`)"
                        v-tooltip.top="'Voir les détails'"
                      />
                      <Button
                        icon="pi pi-pencil"
                        outlined
                        size="small"
                        @click="editUser(data)"
                        v-tooltip.top="'Modifier'"
                      />
                      <Button
                        v-if="data.id !== currentUser?.id"
                        :icon="data.is_active ? 'pi pi-times' : 'pi pi-check'"
                        outlined
                        size="small"
                        :severity="data.is_active ? 'danger' : 'success'"
                        @click="toggleUserStatusConfirm(data)"
                        :v-tooltip.top="data.is_active ? 'Désactiver' : 'Activer'"
                      />
                    </div>
                  </template>
                </Column>

                <template #empty>
                  <div class="text-center py-8">
                    <i class="pi pi-users text-gray-400 text-4xl mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun utilisateur trouvé</h3>
                    <p class="text-sm text-gray-500">Aucun utilisateur ne correspond à vos critères.</p>
                  </div>
                </template>
              </DataTable>
            </div>
          </template>
        </Card>
      </div>
    </main>

    <!-- Modal de création/édition -->
    <Dialog
      v-model:visible="showCreateModal"
      :modal="true"
      header="Nouvel utilisateur"
      :style="{ width: '50rem' }"
      :maximizable="true"
      :closable="true"
    >
      <UserForm
        :user="null"
        @submit="handleUserSubmit"
        @cancel="showCreateModal = false"
      />
    </Dialog>

    <Dialog
      v-model:visible="showEditModal"
      :modal="true"
      header="Modifier l'utilisateur"
      :style="{ width: '50rem' }"
      :maximizable="true"
      :closable="true"
    >
      <UserForm
        :user="selectedUser"
        @submit="handleUserSubmit"
        @cancel="showEditModal = false"
      />
    </Dialog>

    <!-- Confirmation de suppression -->
    <ConfirmDialog />
  </div>
</template>

<script setup>
definePageMeta({
  middleware: ["auth", "admin"],
});

const { $confirm, $toast } = useNuxtApp();
const { user: currentUser } = useAuth();
const { users, fetchUsers, createUser, updateUser, loading } = useUsers();

const showCreateModal = ref(false);
const showEditModal = ref(false);
const selectedUser = ref(null);
const roleFilter = ref("");
const typeFilter = ref("");
const searchTerm = ref("");

const roleOptions = [
  { label: 'Administrateurs', value: 'admin' },
  { label: 'Techniciens', value: 'technicien' },
  { label: 'Managers', value: 'manager' },
  { label: 'Clients', value: 'client' }
];

const typeOptions = [
  { label: 'Câbleurs', value: 'cableur' },
  { label: 'Terrassiers', value: 'terrassier' },
  { label: 'Autres', value: 'autre' }
];

const filteredUsers = computed(() => {
  let filtered = users.value;

  // Filtrer par rôle
  if (roleFilter.value) {
    filtered = filtered.filter((user) => user.role === roleFilter.value);
  }

  // Filtrer par type de technicien
  if (typeFilter.value && roleFilter.value === 'technicien') {
    filtered = filtered.filter((user) => user.type_technicien === typeFilter.value);
  }

  // Filtrer par terme de recherche
  if (searchTerm.value) {
    const term = searchTerm.value.toLowerCase();
    filtered = filtered.filter(
      (user) =>
        user.nom?.toLowerCase().includes(term) ||
        user.email?.toLowerCase().includes(term)
    );
  }

  return filtered;
});

const getRoleText = (role) => {
  return (
    {
      admin: "Administrateur",
      technicien: "Technicien",
      manager: "Manager",
      client: "Client",
    }[role] || "Inconnu"
  );
};

const getRoleSeverity = (role) => {
  return (
    {
      admin: "danger",
      technicien: "info",
      manager: "success",
      client: "secondary",
    }[role] || "secondary"
  );
};

const getTypeText = (type) => {
  return (
    {
      cableur: "Câbleur",
      terrassier: "Terrassier",
      autre: "Autre",
    }[type] || "Autre"
  );
};

const getTypeSeverity = (type) => {
  return (
    {
      cableur: "info",
      terrassier: "warning",
      autre: "secondary",
    }[type] || "secondary"
  );
};

const formatDate = (dateString) => {
  if (!dateString) return 'Non définie';
  return new Date(dateString).toLocaleDateString("fr-FR");
};

const editUser = (user) => {
  selectedUser.value = user;
  showEditModal.value = true;
};

const toggleUserStatusConfirm = async (user) => {
  const action = user.is_active ? "désactiver" : "activer";

  $confirm.require({
    message: `Êtes-vous sûr de vouloir ${action} l'utilisateur "${user.nom}" ?`,
    header: 'Confirmation de modification',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      const result = await updateUser({
        id: user.id,
        actif: !user.is_active,
      });

      if (result.success) {
        $toast.add({
          severity: 'success',
          summary: 'Succès',
          detail: `Utilisateur ${action} avec succès`,
          life: 3000
        });
        await fetchUsers();
      } else {
        $toast.add({
          severity: 'error',
          summary: 'Erreur',
          detail: result.message,
          life: 3000
        });
      }
    }
  });
};

const handleUserSubmit = async (userData) => {
  let result;

  if (showCreateModal.value) {
    result = await createUser(userData);
  } else {
    result = await updateUser(userData);
  }

  if (result.success) {
    closeModals();
    $toast.add({
      severity: 'success',
      summary: 'Succès',
      detail: showCreateModal.value ? 'Utilisateur créé avec succès' : 'Utilisateur modifié avec succès',
      life: 3000
    });
    await fetchUsers();
  } else {
    $toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: result.message,
      life: 3000
    });
  }
};

const closeModals = () => {
  showCreateModal.value = false;
  showEditModal.value = false;
  selectedUser.value = null;
};

const applyFilters = () => {
  // Réinitialiser le filtre de type si on ne filtre plus sur les techniciens
  if (roleFilter.value !== 'technicien') {
    typeFilter.value = '';
  }
  // Les filtres sont automatiquement appliqués via le computed
};

onMounted(() => {
  fetchUsers();
});
</script>