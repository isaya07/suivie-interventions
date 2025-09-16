<template>
  <div>
    <AppHeader />

    <main class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
      <div class="px-4 py-6 sm:px-0">
        <!-- En-tête -->
        <div class="flex items-center justify-between mb-8">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Détails utilisateur</h1>
            <p class="text-gray-600">Informations détaillées de l'utilisateur</p>
          </div>

          <NuxtLink
            to="/users"
            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Retour à la liste
          </NuxtLink>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-8">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        </div>

        <!-- Contenu utilisateur -->
        <div v-else-if="user" class="space-y-6">
          <!-- Informations principales -->
          <Card>
            <template #content>
              <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Informations générales</h3>
              </div>
              <div class="px-6 py-4">
                <div class="flex items-center mb-6">
                  <div class="h-20 w-20 rounded-full bg-blue-600 flex items-center justify-center text-white text-2xl font-medium">
                    {{ user.nom?.[0] || user.username?.[0] || "U" }}
                  </div>
                  <div class="ml-6">
                    <h2 class="text-xl font-semibold text-gray-900">{{ user.nom_complet }}</h2>
                    <p class="text-gray-600">{{ user.email }}</p>
                    <div class="flex flex-wrap gap-2 mt-2">
                      <span
                        :class="getRoleClasses(user.role)"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                      >
                        {{ getRoleText(user.role) }}
                      </span>
                      <span
                        v-if="user.role === 'technicien' && user.type_technicien"
                        :class="getTypeClasses(user.type_technicien)"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                      >
                        {{ getTypeText(user.type_technicien) }}
                      </span>
                    </div>
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Nom d'utilisateur
                    </label>
                    <p class="text-sm text-gray-900">{{ user.username }}</p>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Téléphone
                    </label>
                    <p class="text-sm text-gray-900">{{ user.telephone || 'Non renseigné' }}</p>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Spécialité
                    </label>
                    <p class="text-sm text-gray-900">{{ user.specialite || 'Non renseignée' }}</p>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Statut
                    </label>
                    <span
                      :class="user.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                    >
                      {{ user.is_active ? 'Actif' : 'Inactif' }}
                    </span>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Créé le
                    </label>
                    <p class="text-sm text-gray-900">{{ formatDate(user.created_at) }}</p>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Dernière connexion
                    </label>
                    <p class="text-sm text-gray-900">
                      {{ user.last_login ? formatDate(user.last_login) : 'Jamais connecté' }}
                    </p>
                  </div>
                </div>
              </div>
            </template>
          </Card>

          <!-- Interventions de l'utilisateur (si technicien) -->
          <Card v-if="user.role === 'technicien'">
            <template #content>
              <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Interventions récentes</h3>
              </div>
              <div class="px-6 py-4">
                <div class="text-center py-8 text-gray-500">
                  <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                  </svg>
                  <p class="mt-2">Fonctionnalité à implémenter</p>
                </div>
              </div>
            </template>
          </Card>

          <!-- Actions -->
          <div class="flex justify-end space-x-4">
            <NuxtLink
              to="/users"
              class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors"
            >
              Retour
            </NuxtLink>

            <button
              @click="editUser"
              class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 transition-colors"
            >
              Modifier
            </button>
          </div>
        </div>

        <!-- Erreur -->
        <div v-else class="text-center py-8">
          <div class="text-red-600 mb-4">
            <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 18.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
          </div>
          <h3 class="text-lg font-medium text-gray-900 mb-2">Utilisateur non trouvé</h3>
          <p class="text-gray-500 mb-4">L'utilisateur demandé n'existe pas ou n'est plus accessible.</p>
          <NuxtLink to="/users" class="btn-primary">
            Retour à la liste
          </NuxtLink>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
definePageMeta({
  middleware: ['auth', 'admin']
})

const route = useRoute()
const router = useRouter()
const { fetchUser } = useUsers()

const userId = route.params.id
const loading = ref(true)
const user = ref(null)

const getRoleClasses = (role) => {
  return {
    admin: 'bg-purple-100 text-purple-800',
    technicien: 'bg-blue-100 text-blue-800',
    manager: 'bg-green-100 text-green-800',
    client: 'bg-gray-100 text-gray-800'
  }[role] || 'bg-gray-100 text-gray-800'
}

const getRoleText = (role) => {
  return {
    admin: 'Administrateur',
    technicien: 'Technicien',
    manager: 'Manager',
    client: 'Client'
  }[role] || 'Inconnu'
}

const getTypeClasses = (type) => {
  return {
    cableur: 'bg-indigo-100 text-indigo-800',
    terrassier: 'bg-orange-100 text-orange-800',
    autre: 'bg-gray-100 text-gray-800'
  }[type] || 'bg-gray-100 text-gray-800'
}

const getTypeText = (type) => {
  return {
    cableur: 'Câbleur',
    terrassier: 'Terrassier',
    autre: 'Autre'
  }[type] || 'Autre'
}

const formatDate = (dateString) => {
  if (!dateString) return 'Non définie'
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const editUser = () => {
  // Rediriger vers la page d'édition ou ouvrir un modal
  router.push(`/users/${userId}/edit`)
}

onMounted(async () => {
  try {
    // Pour l'instant, on simule la récupération d'un utilisateur
    // car le composable useUsers n'a pas de méthode fetchUser individuelle
    const { users, fetchUsers } = useUsers()

    await fetchUsers()
    user.value = users.value.find(u => u.id == userId)
  } catch (error) {
    console.error('Erreur lors du chargement de l\'utilisateur:', error)
  } finally {
    loading.value = false
  }
})
</script>