export default defineNuxtPlugin(() => {
  const config = useRuntimeConfig()
  
  const api = $fetch.create({
    baseURL: config.public.apiBaseUrl,
    credentials: 'include',
    
    onRequest({ request, options }) {
      // Ajouter le token d'authentification si disponible
      const token = useCookie('auth-token')
      if (token.value) {
        options.headers = {
          ...options.headers,
          'Authorization': `Bearer ${token.value}`
        }
      }
    },
    
    onResponseError({ response }) {
      if (response.status === 401) {
        // Rediriger vers login si non authentifié
        navigateTo('/login')
      }
    }
  })
  
  return {
    provide: {
      api
    }
  }
})