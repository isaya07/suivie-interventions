<template>
  <div
    class="sidebar-container"
    :class="[
      'fixed lg:relative inset-y-0 left-0 z-50 w-64 bg-white dark:bg-gray-800 shadow-lg border-r border-gray-200 dark:border-gray-700 transform transition-transform duration-300',
      visible ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
    ]"
  >
    <!-- Header de la sidebar -->
    <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
      <div class="flex items-center">
        <i class="pi pi-cog text-blue-600 dark:text-blue-400 text-xl mr-2"></i>
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
          Navigation
        </h2>
      </div>
      <Button
        icon="pi pi-times"
        text
        rounded
        size="small"
        class="lg:hidden !text-gray-400 hover:!text-gray-600"
        @click="$emit('update:visible', false)"
      />
    </div>

    <!-- Navigation principale -->
    <div class="flex-1 overflow-y-auto p-4">
      <PanelMenu
        :model="menuItems"
        class="w-full border-none bg-transparent"
      >
        <template #item="{ item }">
          <div class="panel-menu-item" :class="{ 'active': isActiveRoute(item.route) }">
            <NuxtLink
              v-if="item.route"
              :to="item.route"
              class="flex items-center w-full p-3 text-sm font-medium rounded-lg transition-all duration-200"
              :class="[
                isActiveRoute(item.route)
                  ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20'
                  : 'text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-50 dark:hover:bg-gray-700/50'
              ]"
              @click="handleNavigation"
            >
              <i v-if="item.icon" :class="item.icon" class="mr-3 text-base"></i>
              <span>{{ item.label }}</span>
              <Badge
                v-if="item.badge"
                :value="item.badge"
                size="small"
                severity="info"
                class="ml-auto"
              />
            </NuxtLink>
            <div
              v-else
              class="flex items-center w-full p-3 text-sm font-medium text-gray-700 dark:text-gray-300"
            >
              <i v-if="item.icon" :class="item.icon" class="mr-3 text-base"></i>
              <span>{{ item.label }}</span>
              <Badge
                v-if="item.badge"
                :value="item.badge"
                size="small"
                severity="info"
                class="ml-auto"
              />
            </div>
          </div>
        </template>
      </PanelMenu>
    </div>

    <!-- Footer de la sidebar -->
    <div class="p-4 border-t border-gray-200 dark:border-gray-700">
      <div class="text-xs text-gray-500 dark:text-gray-400 text-center">
        Gestion Interventions v2.0
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  visible: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['update:visible'])

const { user } = useAuth()
const route = useRoute()
const router = useRouter()

/**
 * Configuration du menu pour PanelMenu
 * Structure hiérarchique adaptée pour la sidebar
 */
const menuItems = computed(() => {
  const baseItems = [
    {
      label: 'Dashboard',
      icon: 'pi pi-chart-bar',
      route: '/dashboard'
    },
    {
      label: 'Branchements',
      icon: 'pi pi-bolt',
      items: [
        {
          label: 'Tous les branchements',
          icon: 'pi pi-list',
          route: '/interventions/electrique'
        },
        {
          label: 'Nouveau branchement',
          icon: 'pi pi-plus',
          route: '/interventions/electrique/create'
        },
        {
          label: 'Tableau de bord',
          icon: 'pi pi-chart-pie',
          route: '/interventions/electrique/dashboard'
        },
        {
          separator: true
        },
        {
          label: 'Types de branchements',
          icon: 'pi pi-tag',
          items: [
            {
              label: 'Branchements seuls',
              icon: 'pi pi-bolt',
              route: '/interventions/electrique?type=BRANCHEMENT_SEUL'
            },
            {
              label: 'Avec terrassement',
              icon: 'pi pi-cog',
              route: '/interventions/electrique?type=BRANCHEMENT_TERRASSEMENT'
            },
            {
              label: 'Triphasés',
              icon: 'pi pi-flash',
              route: '/interventions/electrique?type=BRANCHEMENT_TRIPHASE'
            }
          ]
        },
        {
          label: 'Filtres rapides',
          icon: 'pi pi-filter',
          items: [
            {
              label: 'En cours',
              icon: 'pi pi-clock',
              route: '/interventions/electrique?status=en_cours'
            },
            {
              label: 'Terminés',
              icon: 'pi pi-check',
              route: '/interventions/electrique?status=terminee'
            },
            {
              label: 'En attente',
              icon: 'pi pi-pause',
              route: '/interventions/electrique?status=en_attente'
            }
          ]
        }
      ]
    },
    {
      label: 'Planning',
      icon: 'pi pi-calendar',
      items: [
        {
          label: 'Planning standard',
          icon: 'pi pi-calendar',
          route: '/planning'
        },
        {
          label: 'Optimiseur de planning',
          icon: 'pi pi-cog',
          route: '/planning/optimizer',
          badge: 'Nouveau'
        },
        {
          separator: true
        },
        {
          label: 'Historique optimisations',
          icon: 'pi pi-chart-line',
          route: '/planning/optimizer/history'
        }
      ]
    },
    {
      label: 'Notifications',
      icon: 'pi pi-bell',
      items: [
        {
          label: 'Centre de notifications',
          icon: 'pi pi-bell',
          route: '/notifications'
        },
        {
          label: 'Historique',
          icon: 'pi pi-history',
          route: '/notifications/history'
        }
      ]
    },
    {
      label: 'Clients',
      icon: 'pi pi-users',
      items: [
        {
          label: 'Tous les clients',
          icon: 'pi pi-list',
          route: '/clients'
        },
        {
          label: 'Nouveau client',
          icon: 'pi pi-plus',
          route: '/clients/create'
        }
      ]
    }
  ]

  // Ajout conditionnel selon les permissions
  if (user.value?.role === 'admin' || user.value?.role === 'manager') {
    baseItems.push({
      label: 'Utilisateurs',
      icon: 'pi pi-user-edit',
      items: [
        {
          label: 'Tous les utilisateurs',
          icon: 'pi pi-list',
          route: '/users'
        },
        {
          label: 'Nouvel utilisateur',
          icon: 'pi pi-plus',
          route: '/users/create'
        }
      ]
    })

    baseItems.push({
      label: 'Système',
      icon: 'pi pi-cog',
      items: [
        {
          label: 'Performance',
          icon: 'pi pi-chart-line',
          route: '/system/performance'
        },
        {
          label: 'Métriques système',
          icon: 'pi pi-server',
          route: '/system/metrics'
        }
      ]
    })
  }

  return baseItems
})

/**
 * Détermine si une route est active
 */
const isActiveRoute = (path) => {
  if (!path) return false

  if (path === '/dashboard') {
    return route.path === '/dashboard' || route.path === '/'
  }

  return route.path.startsWith(path.split('?')[0])
}

/**
 * Gère la navigation et ferme la sidebar sur mobile
 */
const handleNavigation = () => {
  // Fermer la sidebar sur mobile après navigation
  if (window.innerWidth < 1024) {
    emit('update:visible', false)
  }
}
</script>

<style scoped>
/* Styles pour PanelMenu personnalisé */
:deep(.p-panelmenu) {
  border: none !important;
  background: transparent !important;
}

:deep(.p-panelmenu-panel) {
  border: none !important;
  margin-bottom: 0.25rem !important;
}

:deep(.p-panelmenu-header) {
  border: none !important;
  background: transparent !important;
  margin-bottom: 0.25rem !important;
}

:deep(.p-panelmenu-header-link) {
  background: transparent !important;
  border: none !important;
  padding: 0.75rem 1rem !important;
  border-radius: 0.5rem !important;
  transition: all 0.2s ease !important;
  color: rgb(55, 65, 81) !important;
  font-weight: 500 !important;
}

.dark :deep(.p-panelmenu-header-link) {
  color: rgb(209, 213, 219) !important;
}

:deep(.p-panelmenu-header-link:hover) {
  background: rgb(249, 250, 251) !important;
  color: rgb(37, 99, 235) !important;
}

.dark :deep(.p-panelmenu-header-link:hover) {
  background: rgb(55, 65, 81) !important;
  color: rgb(147, 197, 253) !important;
}

:deep(.p-panelmenu-content) {
  border: none !important;
  background: transparent !important;
  padding: 0 !important;
}

:deep(.p-panelmenu-submenu) {
  background: transparent !important;
  border: none !important;
  padding: 0 0 0 1rem !important;
}

:deep(.p-panelmenu-submenu .p-menuitem-link) {
  padding: 0.5rem 1rem !important;
  border-radius: 0.375rem !important;
  margin-bottom: 0.125rem !important;
  transition: all 0.2s ease !important;
  color: rgb(107, 114, 128) !important;
  font-size: 0.875rem !important;
}

.dark :deep(.p-panelmenu-submenu .p-menuitem-link) {
  color: rgb(156, 163, 175) !important;
}

:deep(.p-panelmenu-submenu .p-menuitem-link:hover) {
  background: rgb(243, 244, 246) !important;
  color: rgb(37, 99, 235) !important;
}

.dark :deep(.p-panelmenu-submenu .p-menuitem-link:hover) {
  background: rgb(55, 65, 81) !important;
  color: rgb(147, 197, 253) !important;
}

/* États actifs */
.panel-menu-item.active :deep(.p-panelmenu-header-link) {
  background: rgb(239, 246, 255) !important;
  color: rgb(37, 99, 235) !important;
}

.dark .panel-menu-item.active :deep(.p-panelmenu-header-link) {
  background: rgb(30, 58, 138) !important;
  color: rgb(147, 197, 253) !important;
}

/* Icônes */
:deep(.p-panelmenu-icon) {
  margin-right: 0.75rem !important;
  font-size: 1rem !important;
}

:deep(.p-menuitem-icon) {
  margin-right: 0.75rem !important;
  font-size: 0.875rem !important;
}

/* Animation d'expansion */
:deep(.p-toggleable-content) {
  overflow: hidden !important;
  transition: max-height 0.3s ease !important;
}

/* Séparateurs */
:deep(.p-menuitem-separator) {
  height: 1px !important;
  background: rgb(229, 231, 235) !important;
  margin: 0.5rem 0 !important;
  border: none !important;
}

.dark :deep(.p-menuitem-separator) {
  background: rgb(75, 85, 99) !important;
}

/* Responsiveness */
@media (max-width: 1023px) {
  .sidebar-container {
    position: fixed !important;
  }
}
</style>