export const useUsersStore = defineStore('users', () => {
  const users = ref([])
  const techniciens = ref([])
  const currentUser = ref(null)
  const loading = ref(false)
  
  const { $api } = useNuxtApp()
  
  const fetchUsers = async () => {
    loading.value = true
    try {
      const response = await $api('/users.php')
      if (response.success) {
        users.value = response.data
      }
    } catch (error) {
      console.error('Erreur lors de la récupération des utilisateurs:', error)
    } finally {
      loading.value = false
    }
  }
  
  const fetchTechniciens = async () => {
    loading.value = true
    try {
      const response = await $api('/users.php?techniciens=1')
      if (response.success) {
        techniciens.value = response.data
      }
    } catch (error) {
      console.error('Erreur lors de la récupération des techniciens:', error)
    } finally {
      loading.value = false
    }
  }
  
  const fetchUser = async (id) => {
    loading.value = true
    try {
      const response = await $api(`/users.php?id=${id}`)
      if (response.success) {
        currentUser.value = response.data
      }
    } catch (error) {
      console.error('Erreur lors de la récupération de l\'utilisateur:', error)
    } finally {
      loading.value = false
    }
  }
  
  const createUser = async (userData) => {
    try {
      const response = await $api('/users.php', {
        method: 'POST',
        body: userData
      })
      
      if (response.success) {
        users.value.push(response.data)
        return { success: true, data: response.data }
      }
      
      return { success: false, message: response.message }
    } catch (error) {
      return { success: false, message: 'Erreur lors de la création' }
    }
  }
  
  const updateUser = async (userData) => {
    try {
      const response = await $api('/users.php', {
        method: 'PUT',
        body: userData
      })
      
      if (response.success) {
        const index = users.value.findIndex(u => u.id === userData.id)
        if (index !== -1) {
          users.value[index] = response.data
        }
        if (currentUser.value?.id === userData.id) {
          currentUser.value = response.data
        }
        return { success: true, data: response.data }
      }
      
      return { success: false, message: response.message }
    } catch (error) {
      return { success: false, message: 'Erreur lors de la mise à jour' }
    }
  }
  
  return {
    users: readonly(users),
    techniciens: readonly(techniciens),
    currentUser: readonly(currentUser),
    loading: readonly(loading),
    
    fetchUsers,
    fetchTechniciens,
    fetchUser,
    createUser,
    updateUser
  }
})