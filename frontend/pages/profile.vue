<template>
  <div>
    <AppHeader />

    <main class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
      <div class="px-4 py-6 sm:px-0">
        <!-- En-tête -->
        <div class="mb-8">
          <h1 class="text-2xl font-bold text-gray-900">Mon Profil</h1>
          <p class="text-gray-600">Gérer vos informations personnelles</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Informations personnelles -->
          <div class="lg:col-span-2">
            <div class="bg-white shadow rounded-lg">
              <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Informations personnelles</h2>
              </div>
              <form @submit.prevent="updateProfile" class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Prénom</label>
                    <input
                      v-model="form.prenom"
                      type="text"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                      required
                    >
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Nom</label>
                    <input
                      v-model="form.nom"
                      type="text"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                      required
                    >
                  </div>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700">Email</label>
                  <input
                    v-model="form.email"
                    type="email"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    required
                  >
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700">Téléphone</label>
                  <input
                    v-model="form.telephone"
                    type="tel"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  >
                </div>

                <div v-if="user?.role === 'technicien'">
                  <label class="block text-sm font-medium text-gray-700">Spécialité</label>
                  <input
                    v-model="form.specialite"
                    type="text"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    placeholder="Ex: Électricité, Plomberie, Informatique..."
                  >
                </div>

                <div class="flex justify-end">
                  <button
                    type="submit"
                    :disabled="loading"
                    class="bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-medium py-2 px-4 rounded-lg transition-colors"
                  >
                    <span v-if="loading">Enregistrement...</span>
                    <span v-else>Enregistrer les modifications</span>
                  </button>
                </div>
              </form>
            </div>
          </div>

          <!-- Informations du compte -->
          <div>
            <div class="bg-white shadow rounded-lg">
              <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Informations du compte</h2>
              </div>
              <div class="p-6 space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700">Nom d'utilisateur</label>
                  <p class="mt-1 text-sm text-gray-900">{{ user?.username }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Rôle</label>
                  <span class="mt-1 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                        :class="getRoleClass(user?.role)">
                    {{ getRoleText(user?.role) }}
                  </span>
                </div>
                <div v-if="user?.last_login">
                  <label class="block text-sm font-medium text-gray-700">Dernière connexion</label>
                  <p class="mt-1 text-sm text-gray-900">{{ formatDate(user.last_login) }}</p>
                </div>
              </div>
            </div>

            <!-- Changer le mot de passe -->
            <div class="bg-white shadow rounded-lg mt-6">
              <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Sécurité</h2>
              </div>
              <form @submit.prevent="changePassword" class="p-6 space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700">Nouveau mot de passe</label>
                  <input
                    v-model="passwordForm.newPassword"
                    type="password"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    required
                    minlength="6"
                  >
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Confirmer le mot de passe</label>
                  <input
                    v-model="passwordForm.confirmPassword"
                    type="password"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    required
                    minlength="6"
                  >
                </div>
                <button
                  type="submit"
                  :disabled="passwordLoading"
                  class="w-full bg-gray-600 hover:bg-gray-700 disabled:bg-gray-400 text-white font-medium py-2 px-4 rounded-lg transition-colors"
                >
                  <span v-if="passwordLoading">Changement...</span>
                  <span v-else>Changer le mot de passe</span>
                </button>
              </form>
            </div>
          </div>
        </div>

        <!-- Messages -->
        <div v-if="message" class="fixed bottom-4 right-4 max-w-sm">
          <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded"
               :class="message.type === 'error' ? 'bg-red-100 border-red-400 text-red-700' : ''">
            {{ message.text }}
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
definePageMeta({
  middleware: 'auth'
})

const { user } = useAuth()
const { updateUser } = useUsers()

const loading = ref(false)
const passwordLoading = ref(false)
const message = ref(null)

// Formulaire principal
const form = ref({
  prenom: '',
  nom: '',
  email: '',
  telephone: '',
  specialite: ''
})

// Formulaire mot de passe
const passwordForm = ref({
  newPassword: '',
  confirmPassword: ''
})

// Initialiser le formulaire avec les données utilisateur
onMounted(() => {
  if (user.value) {
    const [prenom, ...nomParts] = (user.value.nom_complet || '').split(' ')
    form.value = {
      prenom: prenom || '',
      nom: nomParts.join(' ') || '',
      email: user.value.email || '',
      telephone: user.value.telephone || '',
      specialite: user.value.specialite || ''
    }
  }
})

const updateProfile = async () => {
  loading.value = true
  try {
    const result = await updateUser(user.value.id, form.value)
    if (result.success) {
      showMessage('Profil mis à jour avec succès', 'success')
    } else {
      showMessage(result.message || 'Erreur lors de la mise à jour', 'error')
    }
  } catch (error) {
    showMessage('Erreur lors de la mise à jour', 'error')
  } finally {
    loading.value = false
  }
}

const changePassword = async () => {
  if (passwordForm.value.newPassword !== passwordForm.value.confirmPassword) {
    showMessage('Les mots de passe ne correspondent pas', 'error')
    return
  }

  passwordLoading.value = true
  try {
    // TODO: Implémenter l'endpoint de changement de mot de passe
    showMessage('Fonctionnalité à implémenter', 'error')
  } catch (error) {
    showMessage('Erreur lors du changement de mot de passe', 'error')
  } finally {
    passwordLoading.value = false
  }
}

const showMessage = (text, type = 'success') => {
  message.value = { text, type }
  setTimeout(() => {
    message.value = null
  }, 5000)
}

const getRoleClass = (role) => {
  const classes = {
    admin: 'bg-red-100 text-red-800',
    manager: 'bg-purple-100 text-purple-800',
    technicien: 'bg-blue-100 text-blue-800',
    client: 'bg-gray-100 text-gray-800'
  }
  return classes[role] || 'bg-gray-100 text-gray-800'
}

const getRoleText = (role) => {
  const roles = {
    admin: 'Administrateur',
    manager: 'Manager',
    technicien: 'Technicien',
    client: 'Client'
  }
  return roles[role] || role
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>