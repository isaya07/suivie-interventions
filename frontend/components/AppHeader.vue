<template>
  <!-- Header principal de l'application avec recherche globale -->
  <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
    <div class="flex justify-between items-center py-3 px-4 sm:px-6 lg:px-8">
      <!-- Zone gauche : Bouton sidebar + Logo -->
      <div class="flex items-center">
        <!-- Bouton pour toggler la sidebar -->
        <Button
          icon="pi pi-bars"
          text
          rounded
          size="small"
          class="mr-3 !text-gray-600 dark:!text-gray-400 hover:!text-gray-800 dark:hover:!text-gray-200"
          @click="$emit('toggle-sidebar')"
          aria-label="Toggle sidebar"
        />

        <!-- Logo et titre -->
        <div class="flex items-center">
          <i class="pi pi-cog text-blue-600 dark:text-blue-400 text-2xl"></i>
          <div class="ml-3">
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
              <NuxtLink to="/dashboard" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                Gestion Interventions
              </NuxtLink>
            </h1>
          </div>
        </div>
      </div>

      <!-- Zone centrale : Recherche globale -->
      <div class="flex-1 max-w-2xl mx-4 hidden md:block">
        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <i class="pi pi-search text-gray-400"></i>
          </div>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Rechercher interventions, clients, utilisateurs..."
            class="block w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
            @input="handleSearch"
            @keyup.enter="performSearch"
            @focus="showSearchResults = true"
          />

          <!-- Résultats de recherche -->
          <div
            v-if="showSearchResults && (searchResults.length > 0 || searchQuery.length > 0)"
            class="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50 max-h-96 overflow-y-auto"
          >
            <!-- Résultats -->
            <div v-if="searchResults.length > 0" class="py-2">
              <div
                v-for="result in searchResults"
                :key="`${result.type}-${result.id}`"
                class="px-4 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer flex items-center"
                @click="navigateToResult(result)"
              >
                <i :class="result.icon" class="mr-3 text-gray-400"></i>
                <div class="flex-1">
                  <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ result.title }}
                  </div>
                  <div class="text-xs text-gray-500 dark:text-gray-400">
                    {{ result.subtitle }}
                  </div>
                </div>
                <Badge :value="result.type" size="small" severity="secondary" />
              </div>
            </div>

            <!-- Aucun résultat -->
            <div v-else-if="searchQuery.length > 0" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
              <i class="pi pi-search text-2xl mb-2"></i>
              <div class="text-sm">Aucun résultat trouvé</div>
            </div>

            <!-- Suggestions -->
            <div v-else class="py-2">
              <div class="px-4 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                Suggestions
              </div>
              <div
                v-for="suggestion in quickSearchSuggestions"
                :key="suggestion.route"
                class="px-4 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer flex items-center"
                @click="navigateToSuggestion(suggestion)"
              >
                <i :class="suggestion.icon" class="mr-3 text-gray-400"></i>
                <span class="text-sm text-gray-700 dark:text-gray-300">{{ suggestion.label }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Zone droite : Actions utilisateur -->
      <div class="flex items-center space-x-3">
        <!-- Bouton recherche mobile -->
        <Button
          icon="pi pi-search"
          text
          rounded
          size="small"
          class="md:hidden !text-gray-600 dark:!text-gray-400 hover:!text-gray-800 dark:hover:!text-gray-200"
          @click="showMobileSearch = !showMobileSearch"
          aria-label="Recherche"
        />

        <!-- Bouton mode sombre -->
        <Button
          :icon="isDarkMode ? 'pi pi-sun' : 'pi pi-moon'"
          outlined
          rounded
          size="small"
          class="!text-gray-600 dark:!text-gray-400 hover:!text-gray-800 dark:hover:!text-gray-200 !border-gray-300 dark:!border-gray-600 hover:!bg-gray-50 dark:hover:!bg-gray-700"
          @click="toggleDarkMode"
          :aria-label="isDarkMode ? 'Mode clair' : 'Mode sombre'"
          v-tooltip.bottom="isDarkMode ? 'Mode clair' : 'Mode sombre'"
        />

        <!-- Centre de notifications -->
        <NotificationCenter />

        <!-- Menu utilisateur -->
        <div class="relative">
          <button
            @click="showUserMenu = !showUserMenu"
            class="flex items-center space-x-2 text-sm text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100 transition-colors"
          >
            <Avatar
              :label="user?.nom_complet?.charAt(0) || 'U'"
              size="small"
              style="background-color: #3b82f6; color: white"
              shape="circle"
            />
            <span class="font-medium hidden sm:block">{{ user?.nom_complet || 'Utilisateur' }}</span>
            <i class="pi pi-chevron-down text-xs transition-transform duration-200" :class="{ 'rotate-180': showUserMenu }"></i>
          </button>

          <!-- Menu déroulant utilisateur -->
          <div
            v-show="showUserMenu"
            class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg py-1 z-50 border border-gray-200 dark:border-gray-600"
            @click.stop
          >
            <div class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-600">
              <div class="font-medium text-gray-900 dark:text-gray-100">{{ user?.nom_complet }}</div>
              <div class="text-xs">{{ user?.role }}</div>
            </div>
            <NuxtLink
              to="/profile"
              class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
              @click="showUserMenu = false"
            >
              <i class="pi pi-user mr-2"></i>
              Mon profil
            </NuxtLink>
            <button
              @click="handleLogout"
              class="block w-full text-left px-4 py-2 text-sm text-red-700 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
            >
              <i class="pi pi-sign-out mr-2"></i>
              Déconnexion
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Recherche mobile -->
    <div v-if="showMobileSearch" class="md:hidden border-t border-gray-200 dark:border-gray-600 p-4">
      <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <i class="pi pi-search text-gray-400"></i>
        </div>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Rechercher..."
          class="block w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          @input="handleSearch"
          @keyup.enter="performSearch"
        />
      </div>
    </div>
  </header>
</template>

<script setup>
// Définition des événements émis
const emit = defineEmits(['toggle-sidebar'])

// Import des composables
const { user, logout } = useAuth()
const router = useRouter()
const { isDarkMode, toggleDarkMode, initDarkMode } = useDarkMode()

// État local pour l'interface
const showUserMenu = ref(false)
const showMobileSearch = ref(false)
const showSearchResults = ref(false)

// État pour la recherche
const searchQuery = ref('')
const searchResults = ref([])
const searchLoading = ref(false)

/**
 * Suggestions de recherche rapide
 */
const quickSearchSuggestions = computed(() => [
  {
    label: 'Nouveaux branchements',
    icon: 'pi pi-plus',
    route: '/interventions/electrique/create'
  },
  {
    label: 'Branchements en cours',
    icon: 'pi pi-clock',
    route: '/interventions/electrique?status=en_cours'
  },
  {
    label: 'Planning du jour',
    icon: 'pi pi-calendar',
    route: '/planning'
  },
  {
    label: 'Clients récents',
    icon: 'pi pi-users',
    route: '/clients'
  }
])

/**
 * Gère la recherche globale avec débouncing
 */
const handleSearch = useDebouncedRef(async () => {
  if (searchQuery.value.length < 2) {
    searchResults.value = []
    return
  }

  searchLoading.value = true

  try {
    // Recherche dans les interventions
    const interventionResults = await searchInterventions(searchQuery.value)

    // Recherche dans les clients
    const clientResults = await searchClients(searchQuery.value)

    // Recherche dans les utilisateurs (si admin/manager)
    let userResults = []
    if (user.value?.role === 'admin' || user.value?.role === 'manager') {
      userResults = await searchUsers(searchQuery.value)
    }

    // Combiner et formater les résultats
    searchResults.value = [
      ...interventionResults.map(item => ({
        id: item.id,
        type: 'intervention',
        title: `#${item.numero} - ${item.type_prestation}`,
        subtitle: `${item.client_nom} - ${item.statut}`,
        icon: 'pi pi-bolt',
        route: `/interventions/electrique/${item.id}`
      })),
      ...clientResults.map(item => ({
        id: item.id,
        type: 'client',
        title: item.nom_complet,
        subtitle: `${item.email} - ${item.telephone}`,
        icon: 'pi pi-users',
        route: `/clients/${item.id}`
      })),
      ...userResults.map(item => ({
        id: item.id,
        type: 'utilisateur',
        title: item.nom_complet,
        subtitle: `${item.role} - ${item.email}`,
        icon: 'pi pi-user',
        route: `/users/${item.id}`
      }))
    ].slice(0, 10) // Limiter à 10 résultats

  } catch (error) {
    console.error('Erreur lors de la recherche:', error)
    searchResults.value = []
  } finally {
    searchLoading.value = false
  }
}, 300)

/**
 * Recherche dans les interventions
 */
const searchInterventions = async (query) => {
  try {
    const { data } = await $fetch('/api/intervention.php', {
      method: 'GET',
      query: {
        search: query,
        limit: 5
      }
    })
    return data || []
  } catch {
    return []
  }
}

/**
 * Recherche dans les clients
 */
const searchClients = async (query) => {
  try {
    const { data } = await $fetch('/api/clients.php', {
      method: 'GET',
      query: {
        search: query,
        limit: 3
      }
    })
    return data || []
  } catch {
    return []
  }
}

/**
 * Recherche dans les utilisateurs
 */
const searchUsers = async (query) => {
  try {
    const { data } = await $fetch('/api/users.php', {
      method: 'GET',
      query: {
        search: query,
        limit: 2
      }
    })
    return data || []
  } catch {
    return []
  }
}

/**
 * Navigation vers un résultat de recherche
 */
const navigateToResult = (result) => {
  router.push(result.route)
  showSearchResults.value = false
  searchQuery.value = ''
}

/**
 * Navigation vers une suggestion
 */
const navigateToSuggestion = (suggestion) => {
  router.push(suggestion.route)
  showSearchResults.value = false
}

/**
 * Effectue une recherche complète (Enter)
 */
const performSearch = () => {
  if (searchQuery.value.length > 0) {
    router.push(`/search?q=${encodeURIComponent(searchQuery.value)}`)
    showSearchResults.value = false
  }
}

/**
 * Gère la déconnexion
 */
const handleLogout = async () => {
  showUserMenu.value = false
  await logout()
}

/**
 * Ferme les menus déroulants lors d'un clic extérieur
 */
const closeMenus = (event) => {
  if (!event.target.closest('.relative')) {
    showUserMenu.value = false
  }
  if (!event.target.closest('[data-search-container]')) {
    showSearchResults.value = false
  }
}

// Watcher pour fermer les résultats de recherche quand on vide la query
watch(searchQuery, (newQuery) => {
  if (!newQuery) {
    showSearchResults.value = false
  }
})

// Hooks du cycle de vie
onMounted(() => {
  document.addEventListener('click', closeMenus)
  initDarkMode()
})

onUnmounted(() => {
  document.removeEventListener('click', closeMenus)
})
</script>

<style scoped>
/* Animation pour le chevron du menu utilisateur */
.pi-chevron-down {
  transition: transform 0.2s ease;
}

.rotate-180 {
  transform: rotate(180deg);
}

/* Animation pour les résultats de recherche */
.search-results-enter-active,
.search-results-leave-active {
  transition: all 0.15s ease;
}

.search-results-enter-from,
.search-results-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* Styles pour la barre de recherche */
.search-input:focus {
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Responsive: Cacher le nom utilisateur sur très petits écrans */
@media (max-width: 480px) {
  .user-name {
    display: none;
  }
}
</style>