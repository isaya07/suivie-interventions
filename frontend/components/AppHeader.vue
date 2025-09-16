<template>
  <!-- Header principal de l'application avec navigation responsive -->
  <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
    <div class="max-w-7xl mx-auto">
      <div class="flex justify-between items-center py-3 px-4 sm:px-6 lg:px-8">
        <!-- Logo et titre de l'application -->
        <div class="flex items-center">
          <!-- Icône principale -->
          <div class="flex-shrink-0">
            <i class="pi pi-cog text-blue-600 dark:text-blue-400 text-2xl"></i>
          </div>
          <!-- Titre cliquable vers dashboard -->
          <div class="ml-4">
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
              <NuxtLink to="/dashboard" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                Gestion Interventions
              </NuxtLink>
            </h1>
          </div>
        </div>

        <!-- Navigation principale avec MegaMenu PrimeVue (masqué sur mobile) -->
        <div class="hidden md:block">
          <MegaMenu
            :model="menuItems"
            class="!bg-transparent !border-none !shadow-none"
          >
            <!-- Slot pour logo ou contenu de début (actuellement vide) -->
            <template #start>
              <!-- Espace pour logo si nécessaire -->
            </template>
            <!-- Template personnalisé pour chaque élément du menu -->
            <template #item="{ item, props, hasSubmenu, root }">
              <!-- Lien direct pour les éléments sans sous-menu -->
              <NuxtLink
                v-if="!hasSubmenu && item.route"
                :to="item.route"
                v-bind="props.action"
                class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all duration-200"
                :class="[
                  isActiveRoute(item.route)
                    ? 'text-blue-600 dark:text-gray-100 bg-blue-50 dark:bg-gray-700 shadow-sm'
                    : 'text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-700/50'
                ]"
              >
                <i v-if="item.icon" :class="item.icon" class="mr-2"></i>
                <span>{{ item.label }}</span>
                <!-- Badge optionnel pour notifications -->
                <Badge v-if="item.badge" :value="item.badge" size="small" severity="info" class="ml-2" />
              </NuxtLink>
              <!-- Élément avec sous-menu (dropdown) -->
              <a
                v-else
                v-bind="props.action"
                class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all duration-200 text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-700/50"
              >
                <i v-if="item.icon" :class="item.icon" class="mr-2"></i>
                <span>{{ item.label }}</span>
                <!-- Icône dropdown pour les sous-menus -->
                <i v-if="hasSubmenu" class="pi pi-chevron-down ml-2 text-xs"></i>
                <Badge v-if="item.badge" :value="item.badge" size="small" severity="info" class="ml-2" />
              </a>
            </template>
          </MegaMenu>
        </div>

        <!-- Zone des actions utilisateur (boutons et menu) -->
        <div class="flex items-center space-x-4">
          <!-- Bouton de basculement du mode sombre/clair -->
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

          <!-- Bouton notifications (fonctionnalité future) -->
          <Button
            icon="pi pi-bell"
            text
            rounded
            severity="secondary"
            aria-label="Notifications"
          />

          <!-- Menu déroulant de l'utilisateur connecté -->
          <div class="relative">
            <!-- Bouton d'ouverture du menu utilisateur -->
            <button
              @click="showUserMenu = !showUserMenu"
              class="flex items-center space-x-2 text-sm text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100 transition-colors"
            >
              <!-- Avatar avec initiales de l'utilisateur -->
              <Avatar
                :label="user?.nom_complet?.charAt(0) || 'U'"
                class="mr-2"
                size="small"
                style="background-color: #3b82f6; color: white"
                shape="circle"
              />
              <!-- Nom complet de l'utilisateur -->
              <span class="font-medium">{{ user?.nom_complet || 'Utilisateur' }}</span>
              <!-- Icône chevron avec animation de rotation -->
              <i class="pi pi-chevron-down" :class="{ 'rotate-180': showUserMenu }"></i>
            </button>

            <!-- Menu déroulant -->
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

          <!-- Menu mobile -->
          <Button
            icon="pi pi-bars"
            text
            class="md:hidden"
            @click="toggleMobileMenu"
            aria-label="Menu"
          />
        </div>
      </div>

      <!-- Menu mobile déroulant -->
      <div v-if="showMobileMenu" class="md:hidden border-t border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700">
        <div class="px-2 pt-2 pb-3 space-y-1">
          <NuxtLink
            v-for="item in mobileMenuItems"
            :key="item.to"
            :to="item.to"
            class="block px-3 py-2 rounded-md text-base font-medium transition-colors"
            :class="isActiveRoute(item.to) ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20' : 'text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-white dark:hover:bg-gray-600'"
            @click="showMobileMenu = false"
          >
            <i :class="item.icon" class="mr-2"></i>
            {{ item.label }}
          </NuxtLink>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
// Import des composables pour l'authentification et la navigation
const { user, logout } = useAuth()
const route = useRoute()
const { isDarkMode, toggleDarkMode, initDarkMode } = useDarkMode()

// État local pour contrôler l'affichage des menus déroulants
const showUserMenu = ref(false)
const showMobileMenu = ref(false)

/**
 * Configuration du MegaMenu principal
 * Structure hiérarchique des éléments de navigation avec gestion des rôles
 */
const menuItems = computed(() => {
  // Éléments de base accessibles à tous les utilisateurs connectés
  const baseItems = [
    {
      label: 'Dashboard',
      icon: 'pi pi-chart-bar',
      route: '/dashboard'
    },
    {
      label: 'Branchements',
      icon: 'pi pi-bolt',
      // Sous-menu organisé en colonnes pour MegaMenu
      items: [
        [
          {
            label: 'Gestion des branchements',
            items: [
              { label: 'Tous les branchements', route: '/interventions/electrique', icon: 'pi pi-list' },
              { label: 'Nouveau branchement', route: '/interventions/electrique/create', icon: 'pi pi-plus' },
              { label: 'Tableau de bord', route: '/interventions/electrique/dashboard', icon: 'pi pi-chart-pie' }
            ]
          },
          {
            label: 'Types de branchements',
            items: [
              { label: 'Branchements seuls', route: '/interventions/electrique?type=BRANCHEMENT_SEUL', icon: 'pi pi-bolt' },
              { label: 'Avec terrassement', route: '/interventions/electrique?type=BRANCHEMENT_TERRASSEMENT', icon: 'pi pi-cog' },
              { label: 'Triphasés', route: '/interventions/electrique?type=BRANCHEMENT_TRIPHASE', icon: 'pi pi-flash' }
            ]
          }
        ],
        [
          {
            label: 'Filtres rapides',
            items: [
              { label: 'En cours', route: '/interventions/electrique?status=en_cours', icon: 'pi pi-clock' },
              { label: 'Terminés', route: '/interventions/electrique?status=terminee', icon: 'pi pi-check' },
              { label: 'En attente', route: '/interventions/electrique?status=en_attente', icon: 'pi pi-pause' }
            ]
          },
          {
            label: 'Chronomètre',
            items: [
              { label: 'Sessions actives', route: '/interventions/electrique/sessions', icon: 'pi pi-play' },
              { label: 'Historique temps', route: '/interventions/electrique/history', icon: 'pi pi-history' }
            ]
          }
        ]
      ]
    },
    {
      label: 'Clients',
      icon: 'pi pi-users',
      items: [
        [
          {
            label: 'Gestion',
            items: [
              { label: 'Tous les clients', route: '/clients', icon: 'pi pi-list' },
              { label: 'Nouveau client', route: '/clients/create', icon: 'pi pi-plus' }
            ]
          }
        ]
      ]
    }
  ]

  // Ajout conditionnel du menu utilisateurs selon les permissions
  if (user.value?.role === 'admin' || user.value?.role === 'manager') {
    baseItems.push({
      label: 'Utilisateurs',
      icon: 'pi pi-user-edit',
      items: [
        [
          {
            label: 'Gestion',
            items: [
              { label: 'Tous les utilisateurs', route: '/users', icon: 'pi pi-list' },
              { label: 'Nouvel utilisateur', route: '/users/create', icon: 'pi pi-plus' }
            ]
          }
        ]
      ]
    })
  }

  return baseItems
})

/**
 * Configuration du menu mobile (version simplifiée du MegaMenu)
 * Elements affichés dans le menu hamburger sur petits écrans
 */
const mobileMenuItems = computed(() => {
  // Éléments de base pour navigation mobile
  const items = [
    { to: '/dashboard', label: 'Dashboard', icon: 'pi pi-chart-bar' },
    { to: '/interventions/electrique', label: 'Branchements', icon: 'pi pi-bolt' },
    { to: '/clients', label: 'Clients', icon: 'pi pi-users' }
  ]

  // Ajout conditionnel selon les permissions utilisateur
  if (user.value?.role === 'admin' || user.value?.role === 'manager') {
    items.push({ to: '/users', label: 'Utilisateurs', icon: 'pi pi-user-edit' })
  }

  return items
})

/**
 * Détermine si une route est active pour styliser le menu
 * @param {string} path - Chemin de la route à vérifier
 * @returns {boolean} - True si la route est active
 */
const isActiveRoute = (path) => {
  // Cas spécial pour dashboard (racine)
  if (path === '/dashboard') {
    return route.path === '/dashboard' || route.path === '/'
  }
  // Vérification par préfixe pour les autres routes
  return route.path.startsWith(path)
}

/**
 * Bascule l'affichage du menu mobile (hamburger)
 */
const toggleMobileMenu = () => {
  showMobileMenu.value = !showMobileMenu.value
}

/**
 * Gère la déconnexion de l'utilisateur
 * Ferme le menu utilisateur et appelle la fonction logout
 */
const handleLogout = async () => {
  showUserMenu.value = false
  await logout()
}


/**
 * Gestionnaire d'événements pour fermer les menus déroulants
 * Se déclenche lors d'un clic en dehors des menus
 * @param {Event} event - Événement click du DOM
 */
const closeMenus = (event) => {
  // Fermer le menu utilisateur si clic en dehors
  if (!event.target.closest('.relative')) {
    showUserMenu.value = false
  }
  // Fermer le menu mobile si clic en dehors
  if (!event.target.closest('.mobile-menu-button') && !event.target.closest('.mobile-menu')) {
    showMobileMenu.value = false
  }
}

// Hooks du cycle de vie Vue
onMounted(() => {
  // Écouter les clics pour fermer les menus
  document.addEventListener('click', closeMenus)
  // Initialiser le mode sombre selon les préférences
  initDarkMode()
})

onUnmounted(() => {
  // Nettoyer l'écouteur d'événements
  document.removeEventListener('click', closeMenus)
})
</script>

<style scoped>
:deep(.p-megamenu) {
  background: transparent !important;
  border: none !important;
  box-shadow: none !important;
}

:deep(.p-megamenu-panel) {
  background: white !important;
  border: 1px solid rgb(229, 231, 235) !important;
  border-radius: 0.75rem !important;
  box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1) !important;
  padding: 1rem !important;
  margin-top: 0.5rem !important;
}

.dark :deep(.p-megamenu-panel) {
  background: rgb(31, 41, 55) !important;
  border: 1px solid rgb(75, 85, 99) !important;
}

:deep(.p-megamenu-submenu-label) {
  font-weight: 600 !important;
  font-size: 0.875rem !important;
  color: rgb(107, 114, 128) !important;
  padding: 0.5rem 0 !important;
  text-transform: uppercase !important;
  letter-spacing: 0.05em !important;
}

.dark :deep(.p-megamenu-submenu-label) {
  color: rgb(156, 163, 175) !important;
}

:deep(.p-megamenu-submenu .p-menuitem-link) {
  padding: 0.75rem 1rem !important;
  border-radius: 0.5rem !important;
  transition: all 0.2s !important;
  color: rgb(55, 65, 81) !important;
  text-decoration: none !important;
}

.dark :deep(.p-megamenu-submenu .p-menuitem-link) {
  color: rgb(209, 213, 219) !important;
}

:deep(.p-megamenu-submenu .p-menuitem-link:hover) {
  background: rgb(239, 246, 255) !important;
  color: rgb(37, 99, 235) !important;
}

.dark :deep(.p-megamenu-submenu .p-menuitem-link:hover) {
  background: rgb(55, 65, 81) !important;
  color: rgb(243, 244, 246) !important;
}

/* États actifs dans les sous-menus */
:deep(.p-megamenu-submenu .p-menuitem-link.router-link-active) {
  background: rgb(239, 246, 255) !important;
  color: rgb(37, 99, 235) !important;
  font-weight: 600 !important;
}

.dark :deep(.p-megamenu-submenu .p-menuitem-link.router-link-active) {
  background: rgb(75, 85, 99) !important;
  color: rgb(243, 244, 246) !important;
  font-weight: 600 !important;
}

:deep(.p-megamenu-submenu .p-menuitem-icon) {
  margin-right: 0.5rem !important;
  width: 1rem !important;
}

:deep(.p-megamenu-root-list) {
  gap: 0.25rem !important;
}

/* Animation pour les menus déroulants */
:deep(.p-megamenu-panel) {
  animation: fadeInDown 0.15s ease-out !important;
}

@keyframes fadeInDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

</style>