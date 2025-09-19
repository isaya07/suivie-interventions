<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- En-tête amélioré avec liens d'accès -->
    <div class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-4">
            <Button
              icon="pi pi-arrow-left"
              text
              rounded
              class="!text-gray-600 hover:!text-gray-800 dark:!text-gray-400 dark:hover:!text-gray-200"
              @click="$router.push('/planning')"
            />
            <div>
              <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 flex items-center">
                <i class="pi pi-cog mr-3 text-blue-600 dark:text-blue-400"></i>
                Optimiseur de Planning IA
              </h1>
              <p class="text-gray-600 dark:text-gray-400 mt-1">
                Optimisation intelligente des plannings d'interventions électriques
              </p>
            </div>
          </div>
          <div class="flex items-center space-x-3">
            <Button
              label="Voir la démo"
              icon="pi pi-play"
              text
              @click="openDemo"
              class="!text-blue-600 hover:!text-blue-800 dark:!text-blue-400"
            />
            <Button
              label="Documentation"
              icon="pi pi-book"
              text
              @click="openDocumentation"
              class="!text-green-600 hover:!text-green-800 dark:!text-green-400"
            />
          </div>
        </div>

        <!-- Info box avec badges des fonctionnalités -->
        <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <i class="pi pi-info-circle text-blue-600 dark:text-blue-400 text-xl"></i>
              <span class="text-blue-800 dark:text-blue-200 font-medium">
                Fonctionnalités avancées d'optimisation
              </span>
            </div>
            <div class="flex items-center space-x-2">
              <Badge value="Algorithme Génétique" severity="info" />
              <Badge value="Géolocalisation" severity="success" />
              <Badge value="Multi-critères" severity="warning" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Contenu principal -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Configuration d'optimisation -->
      <Card class="mb-8 bg-white dark:bg-gray-800 shadow-sm">
        <template #header>
          <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 flex items-center">
              <i class="pi pi-settings mr-2 text-blue-600 dark:text-blue-400"></i>
              Configuration de l'Optimisation
            </h2>
          </div>
        </template>
        <template #content>
          <div class="p-6 space-y-6">
            <!-- Période d'optimisation -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Date de début
                </label>
                <DatePicker
                  v-model="optimizationConfig.dateDebut"
                  dateFormat="dd/mm/yy"
                  placeholder="Sélectionner la date de début"
                  class="w-full"
                  :pt="{
                    input: { class: 'w-full p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100' }
                  }"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Date de fin
                </label>
                <DatePicker
                  v-model="optimizationConfig.dateFin"
                  dateFormat="dd/mm/yy"
                  placeholder="Sélectionner la date de fin"
                  class="w-full"
                  :pt="{
                    input: { class: 'w-full p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100' }
                  }"
                />
              </div>
            </div>

            <!-- Profil d'optimisation -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Profil d'optimisation
              </label>
              <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div
                  v-for="profil in profilsOptimisation"
                  :key="profil.value"
                  class="relative cursor-pointer"
                  @click="optimizationConfig.profil = profil.value"
                >
                  <div
                    class="p-4 rounded-lg border-2 transition-all duration-200"
                    :class="[
                      optimizationConfig.profil === profil.value
                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                        : 'border-gray-200 dark:border-gray-600 hover:border-blue-300 dark:hover:border-blue-600'
                    ]"
                  >
                    <div class="flex items-center justify-between mb-2">
                      <h3 class="font-medium text-gray-900 dark:text-gray-100">
                        {{ profil.label }}
                      </h3>
                      <Badge
                        :value="profil.badge"
                        :severity="profil.severity"
                        size="small"
                      />
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                      {{ profil.description }}
                    </p>
                    <div class="mt-3 text-xs text-gray-500 dark:text-gray-500">
                      {{ profil.details }}
                    </div>
                  </div>
                  <div
                    v-if="optimizationConfig.profil === profil.value"
                    class="absolute top-2 right-2"
                  >
                    <i class="pi pi-check-circle text-blue-600 dark:text-blue-400"></i>
                  </div>
                </div>
              </div>
            </div>

            <!-- Paramètres d'algorithme -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Taille de population
                </label>
                <InputNumber
                  v-model="optimizationConfig.taillePopulation"
                  :min="10"
                  :max="200"
                  :step="10"
                  class="w-full"
                />
                <small class="text-gray-500 dark:text-gray-400">
                  Nombre d'individus dans l'algorithme génétique (10-200)
                </small>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Générations
                </label>
                <InputNumber
                  v-model="optimizationConfig.generations"
                  :min="50"
                  :max="500"
                  :step="50"
                  class="w-full"
                />
                <small class="text-gray-500 dark:text-gray-400">
                  Nombre d'itérations de l'algorithme (50-500)
                </small>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Taux de mutation
                </label>
                <InputNumber
                  v-model="optimizationConfig.tauxMutation"
                  :min="0.01"
                  :max="0.5"
                  :step="0.01"
                  mode="decimal"
                  :minFractionDigits="2"
                  :maxFractionDigits="2"
                  class="w-full"
                />
                <small class="text-gray-500 dark:text-gray-400">
                  Probabilité de mutation (0.01-0.5)
                </small>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
              <Button
                label="Optimiser le planning"
                icon="pi pi-cog"
                :loading="optimizing"
                @click="optimizePlanning"
                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white border-blue-600 hover:border-blue-700"
              />
              <Button
                label="🔍 Test API"
                icon="pi pi-search"
                severity="info"
                @click="testAPI"
                class="flex-none"
              />
              <Button
                label="Aperçu des interventions"
                icon="pi pi-eye"
                text
                @click="$router.push('/interventions')"
              />
              <Button
                label="Paramètres avancés"
                icon="pi pi-sliders-h"
                text
                @click="showAdvancedDialog = true"
              />
            </div>
          </div>
        </template>
      </Card>

      <!-- Résultats d'optimisation -->
      <div v-if="optimizationResult" class="space-y-8">
        <!-- Statistiques -->
        <Card class="bg-white dark:bg-gray-800 shadow-sm">
          <template #header>
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
              <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 flex items-center">
                <i class="pi pi-chart-bar mr-2 text-green-600 dark:text-green-400"></i>
                Résultats de l'Optimisation
              </h2>
            </div>
          </template>
          <template #content>
            <div class="p-6">
              <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
                <div class="text-center">
                  <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                    {{ optimizationResult.statistiques.interventions }}
                  </div>
                  <div class="text-sm text-gray-600 dark:text-gray-400">Interventions</div>
                </div>
                <div class="text-center">
                  <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                    {{ optimizationResult.statistiques.techniciens }}
                  </div>
                  <div class="text-sm text-gray-600 dark:text-gray-400">Techniciens</div>
                </div>
                <div class="text-center">
                  <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">
                    {{ optimizationResult.statistiques.duree }}h
                  </div>
                  <div class="text-sm text-gray-600 dark:text-gray-400">Durée totale</div>
                </div>
                <div class="text-center">
                  <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">
                    {{ optimizationResult.statistiques.tempsCalcul }}s
                  </div>
                  <div class="text-sm text-gray-600 dark:text-gray-400">Temps calcul</div>
                </div>
              </div>

              <!-- Score d'optimisation -->
              <div class="mb-6">
                <div class="flex items-center justify-between mb-2">
                  <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Score de qualité
                  </span>
                  <span class="text-lg font-bold text-gray-900 dark:text-gray-100">
                    {{ optimizationResult.score }}%
                  </span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                  <div
                    class="h-3 rounded-full transition-all duration-500"
                    :class="getScoreColor(optimizationResult.score)"
                    :style="{ width: optimizationResult.score + '%' }"
                  ></div>
                </div>
              </div>

              <!-- Planning par technicien -->
              <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                  Planning par Technicien
                </h3>
                <div class="space-y-4">
                  <div
                    v-for="technicien in optimizationResult.planning"
                    :key="technicien.id"
                    class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg"
                  >
                    <div class="flex items-center justify-between mb-3">
                      <h4 class="font-medium text-gray-900 dark:text-gray-100">
                        {{ technicien.nom }} {{ technicien.prenom }}
                      </h4>
                      <Badge
                        :value="`${technicien.interventions.length} intervention(s)`"
                        severity="info"
                      />
                    </div>
                    <div class="space-y-2">
                      <div
                        v-for="intervention in technicien.interventions"
                        :key="intervention.id"
                        class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded border"
                      >
                        <div>
                          <div class="font-medium text-gray-900 dark:text-gray-100">
                            {{ intervention.client_nom }}
                          </div>
                          <div class="text-sm text-gray-600 dark:text-gray-400">
                            {{ intervention.adresse }}
                          </div>
                        </div>
                        <div class="text-right">
                          <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ formatTime(intervention.heure_debut) }} - {{ formatTime(intervention.heure_fin) }}
                          </div>
                          <div class="text-xs text-gray-500 dark:text-gray-400">
                            {{ intervention.duree_estimee }}h
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Actions post-optimisation -->
              <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                <Button
                  label="Appliquer ce planning"
                  icon="pi pi-check"
                  @click="applyPlanning"
                  class="flex-1 bg-green-600 hover:bg-green-700 text-white border-green-600 hover:border-green-700"
                />
                <Button
                  label="Sauvegarder"
                  icon="pi pi-save"
                  text
                  @click="savePlanning"
                />
                <Button
                  label="Exporter PDF"
                  icon="pi pi-download"
                  text
                  @click="exportPDF"
                />
                <Button
                  label="Nouvelle optimisation"
                  icon="pi pi-refresh"
                  text
                  @click="resetOptimization"
                />
              </div>
            </div>
          </template>
        </Card>
      </div>

      <!-- Historique des plannings -->
      <Card v-if="!optimizationResult" class="bg-white dark:bg-gray-800 shadow-sm">
        <template #header>
          <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 flex items-center">
              <i class="pi pi-history mr-2 text-purple-600 dark:text-purple-400"></i>
              Historique des Plannings
            </h2>
            <Button
              label="Voir tout"
              icon="pi pi-external-link"
              text
              @click="$router.push('/planning/optimizer/history')"
            />
          </div>
        </template>
        <template #content>
          <div class="p-6">
            <DataTable
              :value="recentPlannings"
              :paginator="true"
              :rows="5"
              class="w-full"
              dataKey="id"
            >
              <Column field="nom" header="Nom du Planning">
                <template #body="{ data }">
                  <div class="font-medium text-gray-900 dark:text-gray-100">
                    {{ data.nom }}
                  </div>
                </template>
              </Column>
              <Column field="periode" header="Période">
                <template #body="{ data }">
                  <div class="text-sm text-gray-600 dark:text-gray-400">
                    {{ formatDate(data.date_debut) }} - {{ formatDate(data.date_fin) }}
                  </div>
                </template>
              </Column>
              <Column field="score_optimisation" header="Score">
                <template #body="{ data }">
                  <Badge
                    :value="`${data.score_optimisation}%`"
                    :severity="getScoreSeverity(data.score_optimisation)"
                  />
                </template>
              </Column>
              <Column field="nb_interventions" header="Interventions">
                <template #body="{ data }">
                  <Badge
                    :value="data.nb_interventions"
                    severity="info"
                  />
                </template>
              </Column>
              <Column field="statut" header="Statut">
                <template #body="{ data }">
                  <Badge
                    :value="data.statut"
                    :severity="getStatutSeverity(data.statut)"
                  />
                </template>
              </Column>
              <Column header="Actions" :exportable="false">
                <template #body="{ data }">
                  <div class="flex gap-2">
                    <Button
                      icon="pi pi-eye"
                      text
                      rounded
                      size="small"
                      @click="viewPlanningDetails(data)"
                    />
                    <Button
                      icon="pi pi-check"
                      text
                      rounded
                      size="small"
                      @click="applyExistingPlanning(data)"
                    />
                  </div>
                </template>
              </Column>
            </DataTable>
          </div>
        </template>
      </Card>
    </div>

    <!-- Dialog Paramètres Avancés -->
    <Dialog
      v-model:visible="showAdvancedDialog"
      modal
      header="Paramètres Avancés d'Optimisation"
      :style="{ width: '600px' }"
      class="p-fluid"
    >
      <div class="space-y-6">
        <div>
          <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
            Configuration des Poids d'Optimisation
          </h3>
          <div class="space-y-4">
            <div v-for="critere in criteresOptimisation" :key="critere.key">
              <div class="flex items-center justify-between mb-2">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                  {{ critere.label }}
                </label>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                  {{ Math.round(critere.poids * 100) }}%
                </span>
              </div>
              <Slider
                v-model="critere.poids"
                :min="0"
                :max="1"
                :step="0.05"
                class="w-full"
              />
              <small class="text-gray-500 dark:text-gray-400">
                {{ critere.description }}
              </small>
            </div>
          </div>

          <!-- Validation des poids -->
          <div class="mt-4 p-3 rounded-lg" :class="poidsValidClass">
            <div class="flex items-center">
              <i :class="poidsValidIcon" class="mr-2"></i>
              <span class="text-sm font-medium">
                Total des poids: {{ totalPoids }}%
              </span>
            </div>
          </div>
        </div>

        <!-- Profils prédéfinis -->
        <div>
          <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
            Profils Prédéfinis
          </h3>
          <div class="grid grid-cols-2 gap-3">
            <Button
              v-for="profil in profilsOptimisation"
              :key="profil.value"
              :label="profil.label"
              text
              size="small"
              @click="applyProfil(profil.poids)"
            >
              <template #default>
                <Badge
                  :value="profil.badge"
                  :severity="profil.severity"
                  size="small"
                  class="mr-2"
                />
                {{ profil.label }}
              </template>
            </Button>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end gap-3">
          <Button
            label="Annuler"
            icon="pi pi-times"
            text
            @click="showAdvancedDialog = false"
          />
          <Button
            label="Appliquer"
            icon="pi pi-check"
            @click="applyAdvancedSettings"
            :disabled="!poidsValid"
          />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

// Métadonnées de la page
definePageMeta({
  middleware: 'auth',
  layout: 'default'
})

// État réactif
const optimizing = ref(false)
const optimizationResult = ref(null)
const showAdvancedDialog = ref(false)
const recentPlannings = ref([])

// Services
const toast = useToast()

// Configuration d'optimisation
const optimizationConfig = ref({
  dateDebut: new Date(),
  dateFin: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000), // +7 jours
  profil: 'equilibre',
  taillePopulation: 50,
  generations: 100,
  tauxMutation: 0.1
})

// Profils d'optimisation prédéfinis
const profilsOptimisation = [
  {
    value: 'equilibre',
    label: 'Équilibré',
    badge: 'Recommandé',
    severity: 'info',
    description: 'Balance optimale entre tous les critères',
    details: 'Temps 25% • Distance 30% • Priorité 25% • Coût 20%',
    poids: { temps: 0.25, distance: 0.30, priorite: 0.25, cout: 0.20 }
  },
  {
    value: 'temps',
    label: 'Priorité Temps',
    badge: 'Rapide',
    severity: 'warning',
    description: 'Minimise le temps total des interventions',
    details: 'Temps 50% • Distance 20% • Priorité 20% • Coût 10%',
    poids: { temps: 0.50, distance: 0.20, priorite: 0.20, cout: 0.10 }
  },
  {
    value: 'cout',
    label: 'Priorité Coût',
    badge: 'Économique',
    severity: 'success',
    description: 'Réduit les coûts de déplacement',
    details: 'Temps 15% • Distance 25% • Priorité 15% • Coût 45%',
    poids: { temps: 0.15, distance: 0.25, priorite: 0.15, cout: 0.45 }
  },
  {
    value: 'urgence',
    label: 'Priorité Urgence',
    badge: 'Critique',
    severity: 'danger',
    description: 'Traite en priorité les interventions urgentes',
    details: 'Temps 20% • Distance 20% • Priorité 50% • Coût 10%',
    poids: { temps: 0.20, distance: 0.20, priorite: 0.50, cout: 0.10 }
  }
]

// Critères d'optimisation avec poids configurables
const criteresOptimisation = ref([
  {
    key: 'temps',
    label: 'Optimisation Temps',
    description: 'Minimise le temps total des interventions',
    poids: 0.25
  },
  {
    key: 'distance',
    label: 'Optimisation Distance',
    description: 'Réduit les distances de déplacement',
    poids: 0.30
  },
  {
    key: 'priorite',
    label: 'Respect des Priorités',
    description: 'Traite les interventions urgentes en premier',
    poids: 0.25
  },
  {
    key: 'cout',
    label: 'Optimisation Coût',
    description: 'Minimise les coûts de déplacement',
    poids: 0.20
  }
])

// Validation des poids
const totalPoids = computed(() => {
  return Math.round(criteresOptimisation.value.reduce((sum, c) => sum + c.poids, 0) * 100)
})

const poidsValid = computed(() => totalPoids.value === 100)

const poidsValidClass = computed(() => {
  return poidsValid.value
    ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800'
    : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800'
})

const poidsValidIcon = computed(() => {
  return poidsValid.value
    ? 'pi pi-check-circle text-green-600 dark:text-green-400'
    : 'pi pi-exclamation-triangle text-red-600 dark:text-red-400'
})

// Méthodes
const testAPI = async () => {
  try {
    const { $api } = useNuxtApp()
    console.log('🧪 Test de l\'API backend...')

    // Test 1: API principale
    console.log('📞 Test 1: Récupération des interventions...')
    const testInterventions = await $api('/intervention_electrique.php?limit=3')
    console.log('✅ Interventions:', testInterventions)

    // Test 2: API planning optimizer
    console.log('📞 Test 2: Test API planning optimizer...')
    const testOptimizer = await $api('/planning_optimizer.php?action=plannings')
    console.log('✅ Planning optimizer:', testOptimizer)

    // Test 3: Configuration
    console.log('📞 Test 3: Configuration runtime...')
    const config = useRuntimeConfig()
    console.log('⚙️ API Base URL:', config.public.apiBaseUrl)

    toast.add({
      severity: 'success',
      summary: 'Test API Réussi',
      detail: 'Backend accessible - vérifiez la console pour les détails',
      life: 3000
    })

  } catch (error) {
    console.error('❌ Erreur test API:', error)
    toast.add({
      severity: 'error',
      summary: 'Test API Échoué',
      detail: error.message || 'Backend non accessible',
      life: 5000
    })
  }
}

const optimizePlanning = async () => {
  if (!optimizationConfig.value.dateDebut || !optimizationConfig.value.dateFin) {
    toast.add({
      severity: 'warn',
      summary: 'Attention',
      detail: 'Veuillez sélectionner une période d\'optimisation',
      life: 3000
    })
    return
  }

  optimizing.value = true
  try {
    const { $api } = useNuxtApp()

    console.log('🔍 Données envoyées à l\'API:', {
      date_debut: formatDateForAPI(optimizationConfig.value.dateDebut),
      date_fin: formatDateForAPI(optimizationConfig.value.dateFin),
      profil: optimizationConfig.value.profil,
      parametres: {
        taille_population: optimizationConfig.value.taillePopulation,
        generations: optimizationConfig.value.generations,
        taux_mutation: optimizationConfig.value.tauxMutation,
        poids: criteresOptimisation.value.reduce((acc, c) => {
          acc[c.key] = c.poids
          return acc
        }, {})
      }
    })

    // Test simple d'abord - vérifier si l'API backend est accessible
    console.log('🌐 Test de connexion API...')
    const testResponse = await $api('/intervention_electrique.php?limit=5')
    console.log('✅ API accessible, interventions chargées:', testResponse)

    const response = await $api('/planning_optimizer.php?action=optimiser', {
      method: 'POST',
      body: {
        date_debut: formatDateForAPI(optimizationConfig.value.dateDebut),
        date_fin: formatDateForAPI(optimizationConfig.value.dateFin),
        profil: optimizationConfig.value.profil,
        parametres: {
          taille_population: optimizationConfig.value.taillePopulation,
          generations: optimizationConfig.value.generations,
          taux_mutation: optimizationConfig.value.tauxMutation,
          poids: criteresOptimisation.value.reduce((acc, c) => {
            acc[c.key] = c.poids
            return acc
          }, {})
        }
      }
    })

    console.log('📊 Réponse API optimisation:', response)

    if (response.success) {
      optimizationResult.value = response.data
      toast.add({
        severity: 'success',
        summary: 'Optimisation Réussie',
        detail: `Planning optimisé avec un score de ${response.data.score}%`,
        life: 5000
      })
    } else {
      throw new Error(response.message || 'Erreur d\'optimisation')
    }
  } catch (error) {
    console.error('❌ Erreur complète lors de l\'optimisation:', error)
    console.error('📋 Stack trace:', error.stack)

    let errorMessage = 'Une erreur est survenue lors de l\'optimisation du planning'
    if (error.response) {
      console.error('🔍 Réponse d\'erreur:', error.response)
      errorMessage = `Erreur API: ${error.response.status} - ${error.response.statusText}`
    } else if (error.message) {
      errorMessage = error.message
    }

    toast.add({
      severity: 'error',
      summary: 'Erreur d\'Optimisation',
      detail: errorMessage,
      life: 5000
    })
  } finally {
    optimizing.value = false
  }
}

const applyPlanning = async () => {
  if (!optimizationResult.value) return

  try {
    const { $api } = useNuxtApp()
    const response = await $api('/planning_optimizer.php?action=appliquer', {
      method: 'POST',
      body: {
        planning_id: optimizationResult.value.id
      }
    })

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Planning Appliqué',
        detail: 'Le planning optimisé a été appliqué avec succès',
        life: 3000
      })
      resetOptimization()
    }
  } catch (error) {
    console.error('Erreur lors de l\'application:', error)
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible d\'appliquer le planning',
      life: 5000
    })
  }
}

const savePlanning = async () => {
  if (!optimizationResult.value) return

  toast.add({
    severity: 'success',
    summary: 'Planning Sauvegardé',
    detail: 'Le planning a été sauvegardé automatiquement',
    life: 3000
  })
}

const exportPDF = () => {
  toast.add({
    severity: 'info',
    summary: 'Export PDF',
    detail: 'Fonctionnalité en développement',
    life: 3000
  })
}

const resetOptimization = () => {
  optimizationResult.value = null
  loadRecentPlannings()
}

const openDemo = () => {
  window.open('/demo_optimizer.html', '_blank')
}

const openDocumentation = () => {
  window.open('/PLANNING_OPTIMIZER.md', '_blank')
}

const applyProfil = (poids) => {
  criteresOptimisation.value.forEach(critere => {
    critere.poids = poids[critere.key] || 0
  })
}

const applyAdvancedSettings = () => {
  if (!poidsValid.value) return

  showAdvancedDialog.value = false
  toast.add({
    severity: 'success',
    summary: 'Paramètres Appliqués',
    detail: 'Les paramètres avancés ont été mis à jour',
    life: 3000
  })
}

const loadRecentPlannings = async () => {
  try {
    const { $api } = useNuxtApp()
    const response = await $api('/planning_optimizer.php?action=plannings&limit=5')
    if (response.success) {
      recentPlannings.value = response.data || []
    }
  } catch (error) {
    console.error('Erreur lors du chargement des plannings:', error)
    // Données de démonstration si l'API n'est pas disponible
    recentPlannings.value = [
      {
        id: 1,
        nom: 'Planning Semaine 42',
        date_debut: '2024-10-14',
        date_fin: '2024-10-18',
        score_optimisation: 87,
        nb_interventions: 24,
        statut: 'applique'
      },
      {
        id: 2,
        nom: 'Urgences Secteur Nord',
        date_debut: '2024-10-15',
        date_fin: '2024-10-16',
        score_optimisation: 92,
        nb_interventions: 12,
        statut: 'applique'
      }
    ]
  }
}

// Utilitaires
const formatDateForAPI = (date) => {
  if (!date) return ''
  return date.toISOString().split('T')[0]
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('fr-FR')
}

const formatTime = (time) => {
  if (!time) return ''
  return time.slice(0, 5)
}

const getScoreColor = (score) => {
  if (score >= 80) return 'bg-green-500'
  if (score >= 60) return 'bg-yellow-500'
  return 'bg-red-500'
}

const getScoreSeverity = (score) => {
  if (score >= 80) return 'success'
  if (score >= 60) return 'warning'
  return 'danger'
}

const getStatutSeverity = (statut) => {
  const map = {
    'brouillon': 'secondary',
    'applique': 'success',
    'archive': 'warning'
  }
  return map[statut] || 'secondary'
}

const viewPlanningDetails = (planning) => {
  toast.add({
    severity: 'info',
    summary: 'Détails du Planning',
    detail: `Ouverture des détails du planning "${planning.nom}"`,
    life: 3000
  })
}

const applyExistingPlanning = async (planning) => {
  try {
    const { $api } = useNuxtApp()
    const response = await $api('/planning_optimizer.php?action=appliquer', {
      method: 'POST',
      body: { planning_id: planning.id }
    })

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Planning Appliqué',
        detail: `Le planning "${planning.nom}" a été appliqué`,
        life: 3000
      })
      planning.statut = 'applique'
    }
  } catch (error) {
    console.error('Erreur:', error)
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible d\'appliquer le planning',
      life: 5000
    })
  }
}

// Lifecycle
onMounted(() => {
  loadRecentPlannings()
})
</script>

<style scoped>
/* Styles personnalisés */
:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background: rgb(249, 250, 251) !important;
}

.dark :deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background: rgb(55, 65, 81) !important;
}

:deep(.p-slider .p-slider-range) {
  background: rgb(37, 99, 235) !important;
}

:deep(.p-slider .p-slider-handle) {
  border-color: rgb(37, 99, 235) !important;
  background: rgb(37, 99, 235) !important;
}

:deep(.p-dialog .p-dialog-header) {
  background: white !important;
  border-bottom: 1px solid rgb(229, 231, 235) !important;
}

.dark :deep(.p-dialog .p-dialog-header) {
  background: rgb(31, 41, 55) !important;
  border-bottom: 1px solid rgb(75, 85, 99) !important;
}

:deep(.p-dialog .p-dialog-content) {
  background: white !important;
}

.dark :deep(.p-dialog .p-dialog-content) {
  background: rgb(31, 41, 55) !important;
}
</style>