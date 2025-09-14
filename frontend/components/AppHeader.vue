<template>
  <header class="bg-white shadow">
    <div class="max-w-7xl mx-auto">
      <div class="flex justify-between items-center py-4 px-4 sm:px-6 lg:px-8">
        <!-- Logo et titre -->
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </div>
          <div class="ml-4">
            <h1 class="text-xl font-semibold text-gray-900">
              <NuxtLink to="/dashboard" class="hover:text-blue-600">
                Gestion Interventions
              </NuxtLink>
            </h1>
          </div>
        </div>

        <!-- Navigation -->
        <nav class="hidden md:flex space-x-8">
          <NuxtLink
            to="/dashboard"
            class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition-colors"
            :class="{ 'text-blue-600 bg-blue-50': $route.path === '/dashboard' }"
          >
            Dashboard
          </NuxtLink>
          <NuxtLink
            to="/interventions"
            class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition-colors"
            :class="{ 'text-blue-600 bg-blue-50': $route.path.startsWith('/interventions') }"
          >
            Interventions
          </NuxtLink>
          <NuxtLink
            to="/clients"
            class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition-colors"
            :class="{ 'text-blue-600 bg-blue-50': $route.path.startsWith('/clients') }"
          >
            Clients
          </NuxtLink>
          <NuxtLink
            v-if="user?.role === 'admin' || user?.role === 'manager'"
            to="/users"
            class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition-colors"
            :class="{ 'text-blue-600 bg-blue-50': $route.path.startsWith('/users') }"
          >
            Utilisateurs
          </NuxtLink>
        </nav>

        <!-- Menu utilisateur -->
        <div class="flex items-center space-x-4">
          <!-- Notifications (optionnel) -->
          <button class="text-gray-400 hover:text-gray-600 transition-colors">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 17h5l-5-5v-2a5 5 0 00-10 0v2l-5 5h5a5 5 0 0010 0z" />
            </svg>
          </button>

          <!-- Menu déroulant utilisateur -->
          <div class="relative user-menu-container">
            <button
              @click="showUserMenu = !showUserMenu"
              class="flex items-center space-x-2 text-sm text-gray-700 hover:text-gray-900 transition-colors"
            >
              <div class="h-8 w-8 bg-blue-600 rounded-full flex items-center justify-center">
                <span class="text-white font-medium">
                  {{ user?.nom_complet?.charAt(0) || 'U' }}
                </span>
              </div>
              <span class="font-medium">{{ user?.nom_complet || 'Utilisateur' }}</span>
              <svg class="h-4 w-4" :class="{ 'rotate-180': showUserMenu }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>

            <!-- Menu déroulant -->
            <Transition
              enter-active-class="transition ease-out duration-100"
              enter-from-class="transform opacity-0 scale-95"
              enter-to-class="transform opacity-100 scale-100"
              leave-active-class="transition ease-in duration-75"
              leave-from-class="transform opacity-100 scale-100"
              leave-to-class="transform opacity-0 scale-95"
            >
              <div
                v-show="showUserMenu"
                class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 border border-gray-200"
                @click.stop
              >
                <div class="px-4 py-2 text-sm text-gray-500 border-b border-gray-200">
                  <div class="font-medium">{{ user?.nom_complet }}</div>
                  <div class="text-xs">{{ user?.role }}</div>
                </div>
                <NuxtLink
                  to="/profile"
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                  @click="showUserMenu = false"
                >
                  Mon profil
                </NuxtLink>
                <button
                  @click="handleLogout"
                  class="block w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-red-50 transition-colors"
                >
                  Déconnexion
                </button>
              </div>
            </Transition>
          </div>
        </div>

        <!-- Menu mobile -->
        <button
          @click="showMobileMenu = !showMobileMenu"
          class="md:hidden text-gray-700 hover:text-gray-900 mobile-menu-button"
        >
          <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>
      </div>

      <!-- Menu mobile déroulant -->
      <div v-show="showMobileMenu" class="md:hidden border-t border-gray-200 bg-gray-50 mobile-menu">
        <div class="px-2 pt-2 pb-3 space-y-1">
          <NuxtLink
            to="/dashboard"
            class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-white transition-colors"
            @click="showMobileMenu = false"
          >
            Dashboard
          </NuxtLink>
          <NuxtLink
            to="/interventions"
            class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-white transition-colors"
            @click="showMobileMenu = false"
          >
            Interventions
          </NuxtLink>
          <NuxtLink
            to="/clients"
            class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-white transition-colors"
            @click="showMobileMenu = false"
          >
            Clients
          </NuxtLink>
          <NuxtLink
            v-if="user?.role === 'admin' || user?.role === 'manager'"
            to="/users"
            class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-white transition-colors"
            @click="showMobileMenu = false"
          >
            Utilisateurs
          </NuxtLink>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
const { user, logout } = useAuth()

const showUserMenu = ref(false)
const showMobileMenu = ref(false)

const handleLogout = async () => {
  showUserMenu.value = false
  await logout()
}

// Fermer les menus quand on clique ailleurs
const closeMenus = (event) => {
  // Ne pas fermer si on clique sur le bouton du menu utilisateur ou dans le menu lui-même
  if (!event.target.closest('.user-menu-container')) {
    showUserMenu.value = false
  }

  // Fermer le menu mobile si on clique ailleurs
  if (!event.target.closest('.mobile-menu-button') && !event.target.closest('.mobile-menu')) {
    showMobileMenu.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', closeMenus)
})

onUnmounted(() => {
  document.removeEventListener('click', closeMenus)
})
</script>