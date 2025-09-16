<template>
  <div>
    <AppHeader />

    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
      <div class="px-4 py-6 sm:px-0">
        <!-- En-tête du dashboard -->
        <div class="mb-8">
          <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Tableau de Bord Branchements</h1>
          <p class="text-gray-600 dark:text-gray-400">Vue d'ensemble des branchements électriques Enedis</p>
        </div>

        <!-- Statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
          <Card class="text-center">
            <template #content>
              <div class="flex items-center justify-center">
                <div class="p-3 bg-blue-100 rounded-full mr-4">
                  <i class="pi pi-clipboard text-blue-600 text-xl"></i>
                </div>
                <div>
                  <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.total }}</div>
                  <div class="text-sm text-gray-600 dark:text-gray-400">Total branchements</div>
                </div>
              </div>
            </template>
          </Card>

          <Card class="text-center">
            <template #content>
              <div class="flex items-center justify-center">
                <div class="p-3 bg-yellow-100 rounded-full mr-4">
                  <i class="pi pi-clock text-yellow-600 text-xl"></i>
                </div>
                <div>
                  <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.enCours }}</div>
                  <div class="text-sm text-gray-600 dark:text-gray-400">En cours</div>
                </div>
              </div>
            </template>
          </Card>

          <Card class="text-center">
            <template #content>
              <div class="flex items-center justify-center">
                <div class="p-3 bg-green-100 rounded-full mr-4">
                  <i class="pi pi-check-circle text-green-600 text-xl"></i>
                </div>
                <div>
                  <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.terminees }}</div>
                  <div class="text-sm text-gray-600 dark:text-gray-400">Terminées</div>
                </div>
              </div>
            </template>
          </Card>

          <Card class="text-center">
            <template #content>
              <div class="flex items-center justify-center">
                <div class="p-3 bg-red-100 rounded-full mr-4">
                  <i class="pi pi-exclamation-triangle text-red-600 text-xl"></i>
                </div>
                <div>
                  <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.enAttente }}</div>
                  <div class="text-sm text-gray-600 dark:text-gray-400">En attente</div>
                </div>
              </div>
            </template>
          </Card>
        </div>

        <!-- Métriques spécifiques aux branchements Enedis -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <Card>
            <template #header>
              <div class="flex items-center justify-between p-4 border-b">
                <h4 class="text-md font-semibold text-gray-900 dark:text-gray-100 flex items-center">
                  <i class="pi pi-cog text-orange-600 mr-2"></i>
                  Phase Terrassement
                </h4>
              </div>
            </template>
            <template #content>
              <div class="p-4 space-y-3">
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600 dark:text-gray-400">En cours</span>
                  <Badge :value="branchementStats.terrassement.enCours" severity="warning" />
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600 dark:text-gray-400">Terminés</span>
                  <Badge :value="branchementStats.terrassement.termines" severity="success" />
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600 dark:text-gray-400">Durée moy.</span>
                  <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ branchementStats.terrassement.dureeMoyenne }}h</span>
                </div>
              </div>
            </template>
          </Card>

          <Card>
            <template #header>
              <div class="flex items-center justify-between p-4 border-b">
                <h4 class="text-md font-semibold text-gray-900 dark:text-gray-100 flex items-center">
                  <i class="pi pi-bolt text-blue-600 mr-2"></i>
                  Phase Branchement
                </h4>
              </div>
            </template>
            <template #content>
              <div class="p-4 space-y-3">
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600 dark:text-gray-400">En cours</span>
                  <Badge :value="branchementStats.branchement.enCours" severity="warning" />
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600 dark:text-gray-400">Terminés</span>
                  <Badge :value="branchementStats.branchement.termines" severity="success" />
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600 dark:text-gray-400">Durée moy.</span>
                  <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ branchementStats.branchement.dureeMoyenne }}h</span>
                </div>
              </div>
            </template>
          </Card>

          <Card>
            <template #header>
              <div class="flex items-center justify-between p-4 border-b">
                <h4 class="text-md font-semibold text-gray-900 dark:text-gray-100 flex items-center">
                  <i class="pi pi-chart-line text-green-600 mr-2"></i>
                  Performance
                </h4>
              </div>
            </template>
            <template #content>
              <div class="p-4 space-y-3">
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600 dark:text-gray-400">Taux de réussite</span>
                  <span class="text-sm font-bold text-green-600">{{ branchementStats.performance.tauxReussite }}%</span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600 dark:text-gray-400">Écart budget moy.</span>
                  <span class="text-sm font-medium" :class="branchementStats.performance.ecartBudget >= 0 ? 'text-red-600' : 'text-green-600'">
                    {{ branchementStats.performance.ecartBudget > 0 ? '+' : '' }}{{ branchementStats.performance.ecartBudget }}%
                  </span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600 dark:text-gray-400">CA du mois</span>
                  <span class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ formatCurrency(branchementStats.performance.caMois) }}</span>
                </div>
              </div>
            </template>
          </Card>
        </div>

        <!-- Graphiques et activité récente -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
          <!-- Graphique des statuts -->
          <Card>
            <template #header>
              <div class="flex items-center justify-between p-4 border-b">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Répartition des statuts</h3>
                <Button
                  icon="pi pi-refresh"
                  text
                  rounded
                  size="small"
                  @click="refreshData"
                  v-tooltip.top="'Actualiser'"
                />
              </div>
            </template>
            <template #content>
              <div v-if="loading" class="flex justify-center py-8">
                <ProgressSpinner style="width: 50px; height: 50px" strokeWidth="4" />
              </div>
              <div v-else class="p-4 space-y-4">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-600 dark:text-gray-400">En cours</span>
                  <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ stats.enCours }}</span>
                </div>
                <ProgressBar
                  :value="stats.total > 0 ? (stats.enCours / stats.total) * 100 : 0"
                  :showValue="false"
                  style="height: 8px"
                  class="mb-2"
                />

                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-600 dark:text-gray-400">Terminées</span>
                  <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ stats.terminees }}</span>
                </div>
                <ProgressBar
                  :value="stats.total > 0 ? (stats.terminees / stats.total) * 100 : 0"
                  :showValue="false"
                  style="height: 8px"
                  class="mb-2 !bg-green-500"
                />

                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-600 dark:text-gray-400">En attente</span>
                  <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ stats.enAttente }}</span>
                </div>
                <ProgressBar
                  :value="stats.total > 0 ? (stats.enAttente / stats.total) * 100 : 0"
                  :showValue="false"
                  style="height: 8px"
                />
              </div>
            </template>
          </Card>

          <!-- Activité récente -->
          <Card>
            <template #header>
              <div class="flex items-center justify-between p-4 border-b">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Activité récente</h3>
                <Button
                  label="Voir tout"
                  icon="pi pi-arrow-right"
                  text
                  size="small"
                  @click="$router.push('/interventions')"
                />
              </div>
            </template>
            <template #content>
              <div v-if="loading" class="flex justify-center py-8">
                <ProgressSpinner style="width: 50px; height: 50px" strokeWidth="4" />
              </div>
              <div v-else-if="recentInterventions.length === 0" class="p-4 text-center text-gray-500 dark:text-gray-400">
                <i class="pi pi-info-circle text-2xl mb-2"></i>
                <p>Aucune intervention récente</p>
              </div>
              <div v-else class="p-4 space-y-4 max-h-80 overflow-y-auto">
                <div
                  v-for="intervention in recentInterventions"
                  :key="intervention.id"
                  class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors cursor-pointer"
                  @click="$router.push(`/interventions/${intervention.id}`)"
                >
                  <div class="flex-shrink-0 mr-3">
                    <Badge
                      :value="intervention.statut"
                      :severity="getStatusSeverity(intervention.statut)"
                      size="small"
                    />
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                      {{ intervention.titre }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                      {{ intervention.client_nom }} • {{ formatDate(intervention.date_creation) }}
                    </p>
                  </div>
                  <div class="flex-shrink-0">
                    <i class="pi pi-chevron-right text-gray-400"></i>
                  </div>
                </div>
              </div>
            </template>
          </Card>
        </div>

        <!-- Actions rapides -->
        <Card>
          <template #header>
            <div class="p-4 border-b">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Actions rapides</h3>
            </div>
          </template>
          <template #content>
            <div class="p-4">
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <Button
                  label="Nouvelle intervention"
                  icon="pi pi-plus"
                  class="p-button-primary justify-start"
                  @click="$router.push('/interventions/create')"
                />

                <Button
                  label="Mes branchements"
                  icon="pi pi-bolt"
                  class="p-button-outlined justify-start"
                  @click="$router.push('/interventions/electrique')"
                />

                <Button
                  label="Gestion clients"
                  icon="pi pi-users"
                  class="p-button-outlined justify-start"
                  @click="$router.push('/clients')"
                />

                <Button
                  v-if="user?.role === 'admin' || user?.role === 'manager'"
                  label="Gestion utilisateurs"
                  icon="pi pi-user-edit"
                  class="p-button-outlined justify-start"
                  @click="$router.push('/users')"
                />
              </div>
            </div>
          </template>
        </Card>
      </div>
    </main>
  </div>
</template>

<script setup>
definePageMeta({
  middleware: ['auth']
})

const { user } = useAuth()
const { interventions, fetchInterventions, loading } = useInterventions()

const stats = ref({
  total: 0,
  enCours: 0,
  terminees: 0,
  enAttente: 0
})

const recentInterventions = ref([])

const branchementStats = ref({
  terrassement: {
    enCours: 0,
    termines: 0,
    dureeMoyenne: 0
  },
  branchement: {
    enCours: 0,
    termines: 0,
    dureeMoyenne: 0
  },
  performance: {
    tauxReussite: 0,
    ecartBudget: 0,
    caMois: 0
  }
})

const getStatusSeverity = (status) => {
  switch (status) {
    case 'En cours': return 'warning'
    case 'Terminée': return 'success'
    case 'En attente': return 'info'
    case 'Annulée': return 'danger'
    default: return 'info'
  }
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR'
  }).format(amount)
}

const calculateStats = () => {
  const interventionsList = [...(interventions.value || [])]

  stats.value = {
    total: interventionsList.length,
    enCours: interventionsList.filter(i => i.statut === 'En cours').length,
    terminees: interventionsList.filter(i => i.statut === 'Terminée').length,
    enAttente: interventionsList.filter(i => i.statut === 'En attente').length
  }

  // Calcul des statistiques spécifiques aux branchements
  calculateBranchementStats(interventionsList)

  // Les 5 interventions les plus récentes
  recentInterventions.value = interventionsList
    .sort((a, b) => new Date(b.date_creation) - new Date(a.date_creation))
    .slice(0, 5)
}

const calculateBranchementStats = (interventionsList) => {
  // Simulation de données pour les phases de branchement
  // Dans un vrai contexte, ces données viendraient de l'API avec les détails des phases

  const terrassementInterventions = interventionsList.filter(i => i.phase_terrassement)
  const branchementInterventions = interventionsList.filter(i => i.phase_branchement)

  // Calculs pour la phase terrassement
  branchementStats.value.terrassement = {
    enCours: Math.floor(interventionsList.length * 0.3), // 30% en cours
    termines: Math.floor(interventionsList.length * 0.6), // 60% terminés
    dureeMoyenne: 4.5 // moyenne de 4.5h pour le terrassement
  }

  // Calculs pour la phase branchement
  branchementStats.value.branchement = {
    enCours: Math.floor(interventionsList.length * 0.25), // 25% en cours
    termines: Math.floor(interventionsList.length * 0.55), // 55% terminés
    dureeMoyenne: 3.2 // moyenne de 3.2h pour le branchement
  }

  // Calculs de performance
  const totalTerminees = stats.value.terminees
  const totalInterventions = stats.value.total

  branchementStats.value.performance = {
    tauxReussite: totalInterventions > 0 ? Math.round((totalTerminees / totalInterventions) * 100) : 0,
    ecartBudget: Math.round((Math.random() - 0.5) * 20), // Simulation d'écart de -10% à +10%
    caMois: 125000 + (Math.random() * 50000) // CA simulé entre 125k et 175k
  }
}

const refreshData = async () => {
  await fetchInterventions()
  calculateStats()
}

onMounted(async () => {
  await fetchInterventions()
  calculateStats()
})

watch(interventions, calculateStats, { deep: true })
</script>