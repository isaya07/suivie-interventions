<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Header principal -->
    <AppHeader @toggle-sidebar="toggleSidebar" />

    <div class="flex">
      <!-- Sidebar -->
      <AppSidebar :visible="sidebarVisible" @close="closeSidebar" />

      <!-- Contenu principal -->
      <main
        class="flex-1 transition-all duration-300"
        :class="sidebarVisible ? 'lg:ml-0' : 'lg:ml-0'"
      >
        <div class="p-4 sm:p-6 lg:p-8">
          <slot />
        </div>
      </main>
    </div>

    <!-- Overlay pour mobile -->
    <div
      v-if="sidebarVisible"
      class="fixed inset-0 z-40 bg-black bg-opacity-50 lg:hidden"
      @click="closeSidebar"
    ></div>

    <!-- Toast pour les notifications -->
    <Toast />
  </div>
</template>

<script setup>
const sidebarVisible = ref(false)

const toggleSidebar = () => {
  sidebarVisible.value = !sidebarVisible.value
}

const closeSidebar = () => {
  sidebarVisible.value = false
}

// Fermer la sidebar lors du changement de route sur mobile
const route = useRoute()
watch(() => route.path, () => {
  if (process.client && window.innerWidth < 1024) {
    sidebarVisible.value = false
  }
})
</script>
