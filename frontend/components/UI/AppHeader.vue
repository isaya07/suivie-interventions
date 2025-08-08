<template>
  <header class="bg-white shadow-sm border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-16">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <h1 class="text-xl font-semibold text-gray-900">
              Gestion Interventions
            </h1>
          </div>
          
          <nav class="hidden md:ml-8 md:flex md:space-x-8">
            <NuxtLink
              to="/dashboard"
              class="text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium"
              active-class="text-blue-600 border-b-2 border-blue-600"
            >
              Dashboard
            </NuxtLink>
            <NuxtLink
              to="/interventions"
              class="text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium"
              active-class="text-blue-600 border-b-2 border-blue-600"
            >
              Interventions
            </NuxtLink>
            <NuxtLink
              v-if="user?.role === 'admin'"
              to="/users"
              class="text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium"
              active-class="text-blue-600 border-b-2 border-blue-600"
            >
              Utilisateurs
            </NuxtLink>
          </nav>
        </div>
        
        <div class="flex items-center space-x-4">
          <!-- Notifications -->
          <button class="text-gray-400 hover:text-gray-500 p-1">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5-5-5 5h5zm0 0v-7a6 6 0 00-12 0v7m12 0H3" />
            </svg>
          </button>
          
          <!-- Menu utilisateur -->
          <div class="relative">
            <button
              @click="showUserMenu = !showUserMenu"
              class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              <div class="h-8 w-8 rounded-full bg-blue-600 flex items-center justify-center text-white font-medium">
                {{ user?.nom?.[0] || 'U' }}
              </div>
              <span class="ml-2 text-gray-700">{{ user?.nom }}</span>
              <svg class="ml-2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            
            <div
              v-if="showUserMenu"
              class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50"
            >
              <div class="py-1">
                <div class="px-4 py-2 text-sm text-gray-700 border-b border-gray-100">
                  <div class="font-medium">{{ user?.nom }}</div>
                  <div class="text-gray-500">{{ user?.email }}</div>
                </div>
                <button
                  @click="handleLogout"
                  class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                >
                  Déconnexion
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
const { user, logout } = useAuth()
const showUserMenu = ref(false)

const handleLogout = async () => {
  await logout()
  showUserMenu.value = false
}

// Fermer le menu au clic extérieur
onClickOutside = () => {
  showUserMenu.value = false
}
</script>