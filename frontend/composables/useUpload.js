export const useUpload = () => {
  const { $api } = useNuxtApp()
  const uploading = ref(false)
  
  const uploadFiles = async (files, interventionId) => {
    uploading.value = true
    
    try {
      const formData = new FormData()
      
      // Ajouter les fichiers au FormData
      Array.from(files).forEach((file, index) => {
        formData.append(`files[${index}]`, file)
      })
      
      formData.append('intervention_id', interventionId)
      
      const response = await $api('/upload.php', {
        method: 'POST',
        body: formData
      })
      
      if (response.success) {
        return { success: true, data: response.data }
      }
      
      return { success: false, message: response.message }
    } catch (error) {
      return { success: false, message: 'Erreur lors du téléchargement' }
    } finally {
      uploading.value = false
    }
  }
  
  const getInterventionFiles = async (interventionId) => {
    try {
      const response = await $api(`/upload.php?intervention_id=${interventionId}`)
      if (response.success) {
        return response.data
      }
      return []
    } catch (error) {
      console.error('Erreur lors de la récupération des fichiers:', error)
      return []
    }
  }
  
  const deleteFile = async (fileId) => {
    try {
      const response = await $api('/upload.php', {
        method: 'DELETE',
        body: { id: fileId }
      })
      
      return response.success
    } catch (error) {
      console.error('Erreur lors de la suppression du fichier:', error)
      return false
    }
  }
  
  const downloadFile = (fileId) => {
    const config = useRuntimeConfig()
    const url = `${config.public.apiBaseUrl}/download.php?id=${fileId}`
    window.open(url, '_blank')
  }
  
  return {
    uploading: readonly(uploading),
    uploadFiles,
    getInterventionFiles,
    deleteFile,
    downloadFile
  }
}