<template>
  <div>
    <AppHeader />

    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
      <div class="px-4 py-6 sm:px-0">
        <!-- En-tête -->
        <div class="flex justify-between items-center mb-6">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Utilisateurs</h1>
            <p class="text-gray-600">Gérez les utilisateurs et techniciens</p>
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
            Nouvel utilisateur
          </button>
        </div>

        <!-- Filtres -->
        <div class="card mb-6">
          <div class="flex flex-wrap items-center gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Filtrer par rôle
              </label>
              <select
                v-model="roleFilter"
                class="form-input w-48"
                @change="applyFilters"
              >
                <option value="">Tous les rôles</option>
                <option value="admin">Administrateurs</option>
                <option value="technicien">Techniciens</option>
                <option value="manager">Managers</option>
                <option value="client">Clients</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Type de technicien
              </label>
              <select
                v-model="typeFilter"
                class="form-input w-48"
                @change="applyFilters"
                :disabled="roleFilter !== 'technicien'"
              >
                <option value="">Tous les types</option>
                <option value="cableur">Câbleurs</option>
                <option value="terrassier">Terrassiers</option>
                <option value="autre">Autres</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Recherche
              </label>
              <input
                v-model="searchTerm"
                type="text"
                class="form-input w-64"
                placeholder="Nom, email..."
                @input="applyFilters"
              />
            </div>
          </div>
        </div>

        <!-- Liste des utilisateurs -->
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
                    Utilisateur
                  </th>
                  <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                  >
                    Rôle
                  </th>
                  <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                  >
                    Statut
                  </th>
                  <th
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                  >
                    Créé le
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
                  v-for="user in filteredUsers"
                  :key="user.id"
                  class="hover:bg-gray-50"
                >
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div
                        class="h-10 w-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-medium"
                      >
                        {{ user.nom?.[0] || "U" }}
                      </div>
                      <div class="ml-4">
                        <div class="text-sm font-medium text-gray-900">
                          {{ user.nom }}
                        </div>
                        <div class="text-sm text-gray-500">
                          {{ user.email }}
                        </div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex flex-col space-y-1">
                      <span
                        :class="getRoleClasses(user.role)"
                        class="px-2 py-1 text-xs font-medium rounded-full inline-block w-fit"
                      >
                        {{ getRoleText(user.role) }}
                      </span>
                      <span
                        v-if="user.role === 'technicien' && user.type_technicien"
                        :class="getTypeClasses(user.type_technicien)"
                        class="px-2 py-1 text-xs font-medium rounded-full inline-block w-fit"
                      >
                        {{ getTypeText(user.type_technicien) }}
                      </span>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span
                      :class="
                        user.is_active
                          ? 'bg-green-100 text-green-800'
                          : 'bg-red-100 text-red-800'
                      "
                      class="px-2 py-1 text-xs font-medium rounded-full"
                    >
                      {{ user.is_active ? "Actif" : "Inactif" }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ formatDate(user.date_creation) }}
                  </td>
                  <td
                    class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2"
                  >
                    <NuxtLink
                      :to="`/users/${user.id}`"
                      class="text-blue-600 hover:text-blue-800"
                    >
                      Voir
                    </NuxtLink>
                    <button
                      @click="editUser(user)"
                      class="text-gray-600 hover:text-gray-800"
                    >
                      Modifier
                    </button>
                    <button
                      v-if="user.id !== currentUser?.id"
                      @click="toggleUserStatus(user)"
                      :class="
                        user.is_active
                          ? 'text-red-600 hover:text-red-800'
                          : 'text-green-600 hover:text-green-800'
                      "
                    >
                      {{ user.is_active ? "Désactiver" : "Activer" }}
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="filteredUsers.length === 0" class="text-center py-8">
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
              Aucun utilisateur trouvé
            </h3>
            <p class="mt-1 text-sm text-gray-500">
              Aucun utilisateur ne correspond à vos critères.
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
          class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
        >
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
              {{
                showCreateModal
                  ? "Nouvel utilisateur"
                  : "Modifier l'utilisateur"
              }}
            </h3>

            <UserForm
              :user="selectedUser"
              @submit="handleUserSubmit"
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
  middleware: ["auth", "admin"],
});

const { user: currentUser } = useAuth();
const { users, fetchUsers, updateUser, loading } = useUsers();

const showCreateModal = ref(false);
const showEditModal = ref(false);
const selectedUser = ref(null);
const roleFilter = ref("");
const typeFilter = ref("");
const searchTerm = ref("");

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
        user.nom_complet?.toLowerCase().includes(term) ||
        user.email?.toLowerCase().includes(term)
    );
  }

  return filtered;
});

const getRoleClasses = (role) => {
  return (
    {
      admin: "bg-purple-100 text-purple-800",
      technicien: "bg-blue-100 text-blue-800",
      manager: "bg-green-100 text-green-800",
    }[role] || "bg-gray-100 text-gray-800"
  );
};

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

const getTypeClasses = (type) => {
  return (
    {
      cableur: "bg-indigo-100 text-indigo-800",
      terrassier: "bg-orange-100 text-orange-800",
      autre: "bg-gray-100 text-gray-800",
    }[type] || "bg-gray-100 text-gray-800"
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

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString("fr-FR");
};

const editUser = (user) => {
  selectedUser.value = user;
  showEditModal.value = true;
};

const toggleUserStatus = async (user) => {
  const action = user.is_active ? "désactiver" : "activer";

  if (confirm(`Êtes-vous sûr de vouloir ${action} cet utilisateur ?`)) {
    await updateUser({
      id: user.id,
      actif: !user.is_active,
    });

    await fetchUsers(); // Recharger la liste
  }
};

const handleUserSubmit = async (userData) => {
  if (showCreateModal.value) {
    // Logique de création (à implémenter dans le store)
  } else {
    await updateUser(userData);
  }

  closeModals();
  await fetchUsers();
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
