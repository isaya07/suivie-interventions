<!--
  Page de création d'un branchement électrique
  Formulaire spécialisé pour créer un branchement électrique avec :
  - Configuration des phases (terrassement + branchement)
  - Assignation des techniciens par phase
  - Planification et estimation des coûts
-->
<template>
  <div>
    <!-- Fil d'Ariane -->
    <Breadcrumb :model="breadcrumbItems" class="mb-6">
      <template #item="{ item }">
        <NuxtLink
          v-if="item.route"
          :to="item.route"
          class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-200"
        >
          {{ item.label }}
        </NuxtLink>
        <span v-else class="text-surface-600 dark:text-surface-400">
          {{ item.label }}
        </span>
      </template>
    </Breadcrumb>
    <!-- En-tête -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-surface-900 dark:text-surface-50 mb-2">
        Nouveau branchement électrique
      </h1>
      <p class="text-surface-600 dark:text-surface-400">
        Créez un branchement électrique avec gestion des phases
      </p>
    </div>
    <!-- Messages d'erreur -->
    <Message
      v-if="error"
      severity="error"
      :closable="true"
      class="mb-6"
      @close="error = null"
    >
      {{ error }}
    </Message>
    <!-- Formulaire -->
    <Card class="mb-6">
      <template #content>
        <form @submit.prevent="handleSubmit" class="space-y-8">
          <!-- Section : Informations de base -->
          <div>
            <h3 class="text-lg font-semibold text-surface-900 dark:text-surface-50 mb-4">
              Informations générales
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div>
                <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-2">
                  Titre de l'intervention *
                </label>
                <InputText
                  v-model="form.titre"
                  placeholder="Ex: Branchement électrique - 123 rue Example"
                  class="w-full"
                  :class="{'p-invalid': errors.titre}"
                />
                <small v-if="errors.titre" class="p-error">{{ errors.titre }}</small>
              </div>
              <div>
                <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-2">
                  Priorité
                </label>
                <Dropdown
                  v-model="form.priorite"
                  :options="priorityOptions"
                  option-label="label"
                  option-value="value"
                  placeholder="Sélectionner une priorité"
                  class="w-full"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-2">
                  Date de réception du dossier
                </label>
                <DatePicker
                  v-model="form.date_reception_dossier"
                  placeholder="Date de réception"
                  class="w-full"
                  date-format="dd/mm/yy"
                />
                <small class="text-sm text-gray-600 dark:text-gray-400">
                  <i class="pi pi-info-circle mr-1"></i>
                  Date de réception du dossier client
                </small>
              </div>
            </div>
            <div class="mt-4">
              <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-2">
                Description
              </label>
              <Textarea
                v-model="form.description"
                rows="3"
                placeholder="Détails de l'intervention, matériel nécessaire..."
                class="w-full"
              />
            </div>
          </div>
          <!-- Section : Client -->
          <div>
            <h3 class="text-lg font-semibold text-surface-900 dark:text-surface-50 mb-4">
              Informations client
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-2">
                  Nom du client *
                </label>
                <InputText
                  v-model="form.client_nom"
                  placeholder="Nom complet du client"
                  class="w-full"
                  :class="{'p-invalid': errors.client_nom}"
                />
                <small v-if="errors.client_nom" class="p-error">{{ errors.client_nom }}</small>
              </div>
              <div class="md:col-span-2">
                <TypeSelector
                  v-model="form.type_prestation"
                  label="Type de branchement Enedis *"
                  mode="cards"
                  :required="true"
                  help-text="Sélectionnez le type de branchement selon les spécifications techniques"
                  :show-pricing="true"
                  @change="onTypePrestationChange"
                />
                <small v-if="errors.type_prestation" class="p-error">{{ errors.type_prestation }}</small>
              </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
              <div>
                <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-2">
                  Mode de pose *
                </label>
                <Dropdown
                  v-model="form.mode_pose"
                  :options="modePoseOptions"
                  option-label="label"
                  option-value="value"
                  placeholder="Sélectionner le mode"
                  class="w-full"
                  :class="{'p-invalid': errors.mode_pose}"
                  @change="onModePoseChange"
                />
                <small v-if="errors.mode_pose" class="p-error">{{ errors.mode_pose }}</small>
              </div>
              <div>
                <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-2">
                  Type de prestation *
                </label>
                <Dropdown
                  v-model="form.type_prestation_id"
                  :options="prestationOptionsFiltrees"
                  option-label="label"
                  option-value="value"
                  placeholder="Sélectionner le type"
                  class="w-full"
                  :class="{'p-invalid': errors.type_prestation_id}"
                  @change="onPrestationChange"
                  :disabled="!form.type_reglementaire || !form.mode_pose"
                />
                <small v-if="errors.type_prestation_id" class="p-error">{{ errors.type_prestation_id }}</small>
              </div>
            </div>
            <div v-if="form.type_reglementaire || form.mode_pose" class="mt-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-2">
                    Longueur Liaison Réseau (LR) - mètres
                  </label>
                  <InputNumber
                    v-model="form.longueur_liaison_reseau"
                    placeholder="Ex: 15"
                    suffix=" m"
                    class="w-full"
                    :min="1"
                    :max="500"
                  />
                  <small class="text-sm text-gray-600 dark:text-gray-400">
                    <i class="pi pi-info-circle mr-1"></i>
                    Du réseau de distribution au CCPI (disjoncteur de branchement)
                  </small>
                </div>
                <div>
                  <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-2">
                    Longueur Dérivation Individuelle (DI) - mètres
                  </label>
                  <InputNumber
                    v-model="form.longueur_derivation_individuelle"
                    placeholder="Ex: 25"
                    suffix=" m"
                    class="w-full"
                    :min="1"
                    :max="200"
                  />
                  <small v-if="form.type_reglementaire === 'type_1'" class="text-sm text-orange-600 dark:text-orange-400">
                    <i class="pi pi-info-circle mr-1"></i>
                    Type 1 : DI ≤ 30m (fournie par Enedis)
                  </small>
                  <small v-else-if="form.type_reglementaire === 'type_2'" class="text-sm text-blue-600 dark:text-blue-400">
                    <i class="pi pi-info-circle mr-1"></i>
                    Type 2 : DI > 30m (fournie par le client)
                  </small>
                  <small v-else class="text-sm text-gray-600 dark:text-gray-400">
                    <i class="pi pi-info-circle mr-1"></i>
                    Du CCPI au tableau électrique (AGCP)
                  </small>
                </div>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-2">
                    Distance totale estimée (mètres)
                  </label>
                  <InputNumber
                    v-model="form.distance_raccordement"
                    placeholder="Ex: 40"
                    suffix=" m"
                    class="w-full"
                    :min="1"
                    :max="500"
                  />
                  <small class="text-sm text-gray-600 dark:text-gray-400">
                    <i class="pi pi-info-circle mr-1"></i>
                    Distance totale approximative du raccordement
                  </small>
                </div>
                <div v-if="showMaterielsSpecifiques">
                  <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-2">
                    Matériel spécifique requis
                  </label>
                  <Textarea
                    v-model="form.materiel_specifique_requis"
                    rows="2"
                    placeholder="Ex: Fourreau TPC Ø75, Coffret double..."
                    class="w-full"
                  />
                </div>
              </div>
            </div>
          </div>
          <!-- Section : Configuration des phases -->
          <div v-if="selectedPrestation">
            <h3 class="text-lg font-semibold text-surface-900 dark:text-surface-50 mb-4">
              Configuration des phases
            </h3>
            <!-- Phase Terrassement (si applicable) -->
            <div v-if="selectedPrestation.has_terrassement" class="mb-6 p-4 border border-orange-200 dark:border-orange-800 rounded-lg bg-orange-50 dark:bg-orange-950">
              <h4 class="font-medium text-orange-900 dark:text-orange-100 mb-3 flex items-center gap-2">
                <i class="pi pi-wrench text-orange-600"></i>
                Phase 1: Terrassement
              </h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-2">
                    Technicien terrassement
                  </label>
                  <Dropdown
                    v-model="form.technicien_terrassement_id"
                    :options="techniciensOptions"
                    option-label="label"
                    option-value="value"
                    placeholder="Assigner un technicien"
                    class="w-full"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-2">
                    Date prévue (optionnel)
                  </label>
                  <DatePicker
                    v-model="form.date_terrassement_prevue"
                    placeholder="Choisir une date"
                    class="w-full"
                    date-format="dd/mm/yy"
                  />
                </div>
              </div>
              <div class="mt-3 text-sm text-orange-700 dark:text-orange-300">
                <i class="pi pi-info-circle mr-1"></i>
                Durée estimée: {{ selectedPrestation.duree_terrassement_heures }}h -
                Coût estimé: {{ formatCurrency(selectedPrestation.duree_terrassement_heures * 38) }}
              </div>
            </div>
            <!-- Phase Branchement -->
            <div class="p-4 border border-blue-200 dark:border-blue-800 rounded-lg bg-blue-50 dark:bg-blue-950">
              <h4 class="font-medium text-blue-900 dark:text-blue-100 mb-3 flex items-center gap-2">
                <i class="pi pi-bolt text-blue-600"></i>
                Phase {{ selectedPrestation.has_terrassement ? '2' : '1' }}: Branchement électrique
              </h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-2">
                    Technicien branchement *
                  </label>
                  <Dropdown
                    v-model="form.technicien_branchement_id"
                    :options="techniciensOptions"
                    option-label="label"
                    option-value="value"
                    placeholder="Assigner un technicien"
                    class="w-full"
                    :class="{'p-invalid': errors.technicien_branchement_id}"
                  />
                  <small v-if="errors.technicien_branchement_id" class="p-error">{{ errors.technicien_branchement_id }}</small>
                </div>
                <div>
                  <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-2">
                    Date prévue (optionnel)
                  </label>
                  <DatePicker
                    v-model="form.date_branchement_prevue"
                    placeholder="Choisir une date"
                    class="w-full"
                    date-format="dd/mm/yy"
                  />
                </div>
              </div>
              <div class="mt-3 text-sm text-blue-700 dark:text-blue-300">
                <i class="pi pi-info-circle mr-1"></i>
                Durée estimée: {{ selectedPrestation.duree_branchement_heures }}h -
                Coût estimé: {{ formatCurrency(selectedPrestation.duree_branchement_heures * 45) }}
              </div>
            </div>
            <!-- Résumé des coûts -->
            <div class="mt-4 p-4 bg-surface-100 dark:bg-surface-800 rounded-lg">
              <h5 class="font-medium text-surface-900 dark:text-surface-50 mb-2">Estimation budgétaire</h5>
              <div class="space-y-1 text-sm">
                <div v-if="selectedPrestation.has_terrassement" class="flex justify-between">
                  <span>Terrassement ({{ selectedPrestation.duree_terrassement_heures }}h à 38€/h):</span>
                  <span>{{ formatCurrency(selectedPrestation.duree_terrassement_heures * 38) }}</span>
                </div>
                <div class="flex justify-between">
                  <span>Branchement ({{ selectedPrestation.duree_branchement_heures }}h à 45€/h):</span>
                  <span>{{ formatCurrency(selectedPrestation.duree_branchement_heures * 45) }}</span>
                </div>
                <div class="flex justify-between font-bold text-lg border-t border-surface-300 dark:border-surface-600 pt-2">
                  <span>Total estimé:</span>
                  <span>{{ formatCurrency(getTotalEstimate()) }}</span>
                </div>
              </div>
            </div>
          </div>
          <!-- Actions -->
          <div class="flex justify-end gap-3 pt-6 border-t border-surface-200 dark:border-surface-700">
            <Button
              label="Annuler"
              severity="secondary"
              @click="goBack"
              :disabled="loading"
            />
            <Button
              label="Créer l'intervention"
              icon="pi pi-plus"
              type="submit"
              :loading="loading"
            />
          </div>
        </form>
      </template>
    </Card>
  </div>
</template>
<script setup>
/**
 * Page de création de branchement électrique
 *
 * Formulaire spécialisé avec :
 * - Configuration des phases selon le type de prestation
 * - Assignation des techniciens spécialisés
 * - Estimation automatique des coûts
 * - Planification optionnelle
 */
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
// Configuration de la page
definePageMeta({
  title: 'Nouveau branchement électrique',
  middleware: 'auth',
  layout: 'default'
})
const router = useRouter()
const toast = useToast()
const { $api } = useNuxtApp()
// État réactif
const loading = ref(false)
const error = ref(null)
const form = ref({
  titre: '',
  description: '',
  priorite: 'normale',
  client_nom: '',
  type_reglementaire: null,
  mode_pose: null,
  type_prestation_id: null,
  distance_raccordement: null,
  longueur_liaison_reseau: null,
  longueur_derivation_individuelle: null,
  materiel_specifique_requis: '',
  technicien_terrassement_id: null,
  technicien_branchement_id: null,
  date_terrassement_prevue: null,
  date_branchement_prevue: null,
  date_reception_dossier: new Date()
})
const errors = ref({})
const prestationsDisponibles = ref([])
const techniciens = ref([])
// Fil d'Ariane
const breadcrumbItems = computed(() => [
  {
    label: 'Accueil',
    route: '/dashboard'
  },
  {
    label: 'Branchements électriques',
    route: '/interventions/electrique'
  },
  {
    label: 'Nouvelle intervention'
  }
])
// Options pour les dropdowns
const priorityOptions = [
  { label: 'Basse', value: 'basse' },
  { label: 'Normale', value: 'normale' },
  { label: 'Haute', value: 'haute' },
  { label: 'Urgente', value: 'urgente' }
]
const typeReglementaireOptions = [
  { label: 'Type 1 (< 30m, compteur intérieur)', value: 'type_1' },
  { label: 'Type 2 (> 30m, coffret limite propriété)', value: 'type_2' }
]
const modePoseOptions = [
  { label: 'Aérien', value: 'aerien' },
  { label: 'Souterrain', value: 'souterrain' },
  { label: 'Aérosouterrain', value: 'aerosouterrain' },
  { label: 'Souterrain sur boîte', value: 'souterrain_boite' },
  { label: 'DI Seule (Dérivation Individuelle)', value: 'di_seule' }
]
const prestationOptions = computed(() =>
  prestationsDisponibles.value.map(p => ({
    label: p.nom,
    value: p.id
  }))
)
const prestationOptionsFiltrees = computed(() => {
  if (!form.value.type_reglementaire || !form.value.mode_pose) {
    return []
  }
  return prestationsDisponibles.value
    .filter(p =>
      p.type_reglementaire === form.value.type_reglementaire &&
      p.mode_pose === form.value.mode_pose
    )
    .map(p => ({
      label: p.nom,
      value: p.id
    }))
})
const showMaterielsSpecifiques = computed(() => {
  return ['souterrain', 'aerosouterrain', 'souterrain_boite'].includes(form.value.mode_pose)
})
const techniciensOptions = computed(() =>
  techniciens.value.map(t => ({
    label: `${t.prenom} ${t.nom}`,
    value: t.id
  }))
)
const selectedPrestation = computed(() =>
  prestationsDisponibles.value.find(p => p.id === form.value.type_prestation_id)
)
/**
 * Charge les données nécessaires au formulaire
 */
const loadFormData = async () => {
  try {
    // Charger les types de prestations électriques
    const prestationsResponse = await $api.get('/types_prestations.php?category=electrique')
    if (prestationsResponse.success) {
      prestationsDisponibles.value = prestationsResponse.data || []
    }
    // Charger les techniciens
    const techniciensResponse = await $api.get('/users.php?techniciens=1')
    if (techniciensResponse.success) {
      techniciens.value = techniciensResponse.data || []
    }
  } catch (err) {
    console.error('Erreur chargement données:', err)
    error.value = 'Erreur lors du chargement des données du formulaire'
  }
}
/**
 * Gestionnaires de changement des sélecteurs
 */
const onTypeReglementaireChange = () => {
  // Reset des sélections suivantes
  form.value.type_prestation_id = null
  resetAssignationsTechniques()
}
const onModePoseChange = () => {
  // Reset des sélections suivantes
  form.value.type_prestation_id = null
  resetAssignationsTechniques()
}
const onPrestationChange = () => {
  // Reset des assignations techniques quand on change de prestation
  resetAssignationsTechniques()
}
const resetAssignationsTechniques = () => {
  form.value.technicien_terrassement_id = null
  form.value.technicien_branchement_id = null
  form.value.date_terrassement_prevue = null
  form.value.date_branchement_prevue = null
}
/**
 * Calcule l'estimation budgétaire totale
 */
const getTotalEstimate = () => {
  if (!selectedPrestation.value) return 0
  let total = 0
  if (selectedPrestation.value.has_terrassement) {
    total += selectedPrestation.value.duree_terrassement_heures * 38
  }
  total += selectedPrestation.value.duree_branchement_heures * 45
  return total
}
/**
 * Valide le formulaire
 */
const validateForm = () => {
  const newErrors = {}
  if (!form.value.titre?.trim()) {
    newErrors.titre = 'Le titre est requis'
  }
  if (!form.value.client_nom?.trim()) {
    newErrors.client_nom = 'Le nom du client est requis'
  }
  if (!form.value.type_reglementaire) {
    newErrors.type_reglementaire = 'Le type réglementaire est requis'
  }
  if (!form.value.mode_pose) {
    newErrors.mode_pose = 'Le mode de pose est requis'
  }
  if (!form.value.type_prestation_id) {
    newErrors.type_prestation_id = 'Le type de prestation est requis'
  }
  if (!form.value.technicien_branchement_id) {
    newErrors.technicien_branchement_id = 'Un technicien pour le branchement est requis'
  }
  errors.value = newErrors
  return Object.keys(newErrors).length === 0
}
/**
 * Soumission du formulaire
 */
const handleSubmit = async () => {
  if (!validateForm()) {
    return
  }
  loading.value = true
  error.value = null
  try {
    const payload = {
      ...form.value,
      // Conversion des dates si présentes
      date_reception_dossier: form.value.date_reception_dossier ?
        form.value.date_reception_dossier.toISOString().split('T')[0] : null,
      date_terrassement_prevue: form.value.date_terrassement_prevue ?
        form.value.date_terrassement_prevue.toISOString().split('T')[0] : null,
      date_branchement_prevue: form.value.date_branchement_prevue ?
        form.value.date_branchement_prevue.toISOString().split('T')[0] : null
    }
    const response = await $api.post('/intervention_electrique.php', payload)
    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Intervention créée',
        detail: 'Le branchement électrique a été créé avec succès',
        life: 5000
      })
      // Redirection vers la page de détail
      router.push(`/interventions/electrique/${response.data.id}`)
    } else {
      throw new Error(response.message || 'Erreur lors de la création')
    }
  } catch (err) {
    console.error('Erreur création intervention:', err)
    error.value = err.message
  } finally {
    loading.value = false
  }
}
/**
 * Retour à la page précédente
 */
const goBack = () => {
  router.push('/interventions/electrique')
}
/**
 * Gestion du changement de type de prestation
 */
const onTypePrestationChange = (value) => {
  form.value.type_prestation = value
  // Auto-déduction du type réglementaire selon la prestation choisie
  const prestation = prestationsDisponibles.value.find(p => p.id === value)
  if (prestation) {
    // Logique d'auto-déduction basée sur les caractéristiques de la prestation
    if (prestation.code === 'BRANCHEMENT_TERRASSEMENT' || prestation.has_terrassement) {
      form.value.type_reglementaire = 'type_2'
    } else if (prestation.code.includes('MONOPHASE')) {
      form.value.type_reglementaire = 'type_1'
    }
    // Mise à jour des coûts estimés
    calculateEstimations()
  }
}
/**
 * Calcule les estimations de coût et durée
 */
const calculateEstimations = () => {
  // Cette méthode peut être utilisée pour recalculer automatiquement
  // les estimations lorsque le type de prestation change
  // Implémentation selon la logique métier
}
/**
 * Formate un montant en euros
 */
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR'
  }).format(amount)
}
// Charger les données au montage
onMounted(() => {
  loadFormData()
})
</script>
<style scoped>
/* Animation d'entrée */
.max-w-6xl {
  animation: fadeInUp 0.6s ease-out;
}
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
/* Style pour les sections de phases */
.border-orange-200 {
  border-color: rgb(254 215 170);
}
.border-blue-200 {
  border-color: rgb(191 219 254);
}
.bg-orange-50 {
  background-color: rgb(255 247 237);
}
.bg-blue-50 {
  background-color: rgb(239 246 255);
}
/* Mode sombre */
:deep(.dark) .border-orange-800 {
  border-color: rgb(154 52 18);
}
:deep(.dark) .border-blue-800 {
  border-color: rgb(30 64 175);
}
:deep(.dark) .bg-orange-950 {
  background-color: rgb(67 20 7);
}
:deep(.dark) .bg-blue-950 {
  background-color: rgb(23 37 84);
}
</style>