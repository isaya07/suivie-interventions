<template>
  <AuthLoginForm />
</template>

<script setup>
definePageMeta({
  layout: false,
  middleware: (to, from) => {
    const { isAuthenticated } = useAuth()
    
    if (isAuthenticated.value) {
      return navigateTo('/dashboard')
    }
  }
})
</script>