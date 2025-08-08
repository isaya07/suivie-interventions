export default defineNuxtRouteMiddleware(() => {
  const { user } = useAuth()
  
  if (!user.value || user.value.role !== 'admin') {
    throw createError({
      statusCode: 403,
      statusMessage: 'Accès interdit'
    })
  }
})