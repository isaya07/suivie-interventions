<!--
  Page de détail d'un branchement électrique
    <div>
  Affiche toutes les informations et contrôles pour une intervention
  électrique spécifique avec gestion en temps réel des phases
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
    <!-- Indicateur de chargement -->
    <div v-if="loading" class="flex justify-center items-center min-h-64">
      <ProgressSpinner />
    </div>
    <!-- Contenu principal -->
    <div v-else-if="intervention">
      <InterventionElectrique
        :intervention-id="route.params.id"
        @intervention-updated="handleInterventionUpdate"
      />
      <!-- Boutons d'action -->
      <div class="flex flex-wrap gap-3 mt-8 justify-between">
        <div class="flex gap-3">
          <Button
            label="Retour à la liste"
            icon="pi pi-arrow-left"
            severity="secondary"
            @click="goBack"
          />
          <Button
            label="Imprimer rapport"
            icon="pi pi-print"
            severity="secondary"
            @click="printReport"
          />
        </div>
        <div class="flex gap-3">
          <Button
            v-if="canEdit"
            label="Modifier"
            icon="pi pi-pencil"
            @click="editIntervention"
          />
          <Button
            v-if="canComplete"
            label="Terminer intervention"
            icon="pi pi-check"
            severity="success"
            @click="completeIntervention"
          />
        </div>
      </div>
    </div>
    <!-- État vide (intervention non trouvée) -->
    <div v-else class="text-center py-12">
      <i class="pi pi-exclamation-triangle text-6xl text-surface-400 dark:text-surface-600 mb-4"></i>
      <h2 class="text-2xl font-bold text-surface-700 dark:text-surface-300 mb-2">
        Intervention non trouvée
      </h2>
      <p class="text-surface-600 dark:text-surface-400 mb-6">
        L'intervention demandée n'existe pas ou n'est plus accessible.
      </p>
      <Button
        label="Retour à la liste"
        icon="pi pi-arrow-left"
        @click="goBack"
      />
    </div>
    <!-- Dialog de confirmation pour terminer l'intervention -->
    <Dialog
      v-model:visible="showCompleteDialog"
      :modal="true"
      :closable="true"
      header="Terminer l'intervention"
      class="w-full max-w-md"
    >
      <div class="space-y-4">
        <p class="text-surface-700 dark:text-surface-300">
          Êtes-vous sûr de vouloir marquer cette intervention comme terminée ?
        </p>
        <p class="text-sm text-surface-600 dark:text-surface-400">
          Cette action clôturera définitivement l'intervention et calculera les coûts finaux.
        </p>
        <div class="space-y-2">
          <label class="text-sm font-medium text-surface-700 dark:text-surface-300">
            Notes finales (optionnel)
          </label>
          <Textarea
            v-model="finalNotes"
            rows="3"
            placeholder="Résumé final, observations..."
            class="w-full"
          />
        </div>
      </div>
      <template #footer>
        <div class="flex gap-2 justify-end">
          <Button
            label="Annuler"
            severity="secondary"
            @click="showCompleteDialog = false"
          />
          <Button
            label="Terminer"
            icon="pi pi-check"
            severity="success"
            @click="confirmComplete"
          />
        </div>
      </template>
    </Dialog>
  </div>
</template>
<script setup>
/**
 * Page de détail d'un branchement électrique
 *
 * Affiche les informations complètes d'un branchement électrique
 * avec gestion des phases, chronomètre et actions disponibles
 */
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import InterventionElectrique from '@/components/Interventions/InterventionElectrique.vue'
// Configuration de la page
definePageMeta({
  title: 'Branchement Électrique',
  middleware: 'auth',
  layout: 'default'
})
const route = useRoute()
const router = useRouter()
const { hasPermission } = useAuth()
const toast = useToast()
const { $api } = useNuxtApp()
// État réactif
const intervention = ref(null)
const loading = ref(true)
const error = ref(null)
const showCompleteDialog = ref(false)
const finalNotes = ref('')
// Fil d'Ariane
const breadcrumbItems = computed(() => [
  {
    label: 'Accueil',
    route: '/dashboard'
  },
  {
    label: 'Interventions',
    route: '/interventions'
  },
  {
    label: `Branchement électrique #${intervention.value?.numero || route.params.id}`
  }
])
// Permissions
const canEdit = computed(() => {
  return hasPermission('technicien') &&
         intervention.value &&
         ['en_attente', 'en_cours'].includes(intervention.value.statut_global)
})
const canComplete = computed(() => {
  return hasPermission('manager') &&
         intervention.value &&
         intervention.value.phase_branchement_statut === 'terminee' &&
         (!intervention.value.has_terrassement || intervention.value.phase_terrassement_statut === 'terminee')
})
/**
 * Charge les données de l'intervention
 */
const loadIntervention = async () => {
  loading.value = true
  error.value = null
  try {
    const response = await $api.get(`/intervention_electrique.php?id=${route.params.id}`)
    if (response && response.success && response.data) {
      intervention.value = response.data
      // Mettre à jour le titre de la page
      if (intervention.value?.numero) {
        useHead({
          title: `Branchement électrique #${intervention.value.numero}`
        })
      }
    } else {
      // Si pas de données, créer des données de test basées sur l'ID
      console.warn('API failed, using test data for intervention', route.params.id)
      intervention.value = {
        id: parseInt(route.params.id),
        numero: `BR-2024-${String(route.params.id).padStart(3, '0')}`,
        titre: `Branchement électrique #${route.params.id}`,
        client_nom: 'Client Test',
        type_prestation_nom: 'Branchement Souterrain Type 2',
        type_prestation_code: 'SOUTERRAIN_TYPE_2',
        has_terrassement: true,
        phase_branchement_statut: 'en_attente',
        phase_terrassement_statut: 'en_cours',
        technicien_branchement_nom: 'Michel Martin',
        technicien_terrassement_nom: 'Jean Dupont',
        statut_global: 'en_cours',
        date_creation: new Date().toISOString(),
        type_reglementaire: 'type_2',
        mode_pose: 'souterrain',
        longueur_liaison_reseau: 15,
        longueur_derivation_individuelle: 35,
        distance_raccordement: 50
      }
      useHead({
        title: `Branchement électrique #${intervention.value.numero}`
      })
      toast.add({
        severity: 'info',
        summary: 'Mode test',
        detail: 'Données de test affichées (API non accessible)',
        life: 3000
      })
    }
  } catch (err) {
    console.error('Erreur loadIntervention:', err)
    // En cas d'erreur, créer des données de test
    intervention.value = {
      id: parseInt(route.params.id),
      numero: `BR-2024-${String(route.params.id).padStart(3, '0')}`,
      titre: `Branchement électrique #${route.params.id}`,
      client_nom: 'Client Test',
      type_prestation_nom: 'Branchement Souterrain Type 2',
      type_prestation_code: 'SOUTERRAIN_TYPE_2',
      has_terrassement: true,
      phase_branchement_statut: 'en_attente',
      phase_terrassement_statut: 'en_cours',
      technicien_branchement_nom: 'Michel Martin',
      technicien_terrassement_nom: 'Jean Dupont',
      statut_global: 'en_cours',
      date_creation: new Date().toISOString(),
      type_reglementaire: 'type_2',
      mode_pose: 'souterrain',
      longueur_liaison_reseau: 15,
      longueur_derivation_individuelle: 35,
      distance_raccordement: 50
    }
    useHead({
      title: `Branchement électrique #${intervention.value.numero}`
    })
    toast.add({
      severity: 'warn',
      summary: 'Mode hors ligne',
      detail: 'Données de test affichées (impossible de se connecter)',
      life: 5000
    })
  } finally {
    loading.value = false
  }
}
/**
 * Gestionnaire de mise à jour de l'intervention
 */
const handleInterventionUpdate = (updatedIntervention) => {
  intervention.value = updatedIntervention
  toast.add({
    severity: 'info',
    summary: 'Intervention mise à jour',
    detail: 'Les données ont été actualisées',
    life: 2000
  })
}
/**
 * Retourne à la liste des interventions
 */
const goBack = () => {
  router.push('/interventions')
}
/**
 * Ouvre la page d'édition
 */
const editIntervention = () => {
  router.push(`/interventions/edit/${route.params.id}`)
}
/**
 * Affiche le dialog de confirmation pour terminer
 */
const completeIntervention = () => {
  showCompleteDialog.value = true
}
/**
 * Confirme la terminaison de l'intervention
 */
const confirmComplete = async () => {
  try {
    const response = await $api.post('/intervention_electrique.php?action=terminer_phase', {
      intervention_id: route.params.id,
      notes: finalNotes.value
    })
    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Intervention terminée',
        detail: 'L\'intervention a été marquée comme terminée avec succès',
        life: 5000
      })
      // Recharger les données
      await loadIntervention()
      // Fermer le dialog
      showCompleteDialog.value = false
      finalNotes.value = ''
    } else {
      throw new Error(response.message)
    }
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: err.message,
      life: 5000
    })
  }
}
/**
 * Génère et imprime un rapport
 */
const printReport = () => {
  // Ouvrir une nouvelle fenêtre avec le rapport imprimable
  const reportUrl = `/interventions/electrique/${route.params.id}/report`
  window.open(reportUrl, '_blank', 'width=800,height=600')
}
// Charger les données au montage
onMounted(() => {
  loadIntervention()
})
// Surveiller les changements d'ID pour recharger
watch(() => route.params.id, () => {
  if (route.params.id) {
    loadIntervention()
  }
})
</script>
<style scoped>
/* Animation d'entrée sans @apply */
.max-w-7xl {
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
/* Responsive sans @apply */
@media (max-width: 768px) {
  .max-w-7xl {
    padding: 0.5rem;
  }
}
/* Style pour le breadcrumb sans @apply */
:deep(.p-breadcrumb) {
  background: transparent;
  border: 0;
  padding: 0;
}
:deep(.p-breadcrumb-list) {
  flex-wrap: wrap;
}
/* Messages d'état sans @apply */
:deep(.p-message) {
  box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
}
/* Indicateur de chargement sans @apply */
:deep(.p-progress-spinner) {
  width: 4rem;
  height: 4rem;
}
/* Boutons d'action sans @apply */
:deep(.p-button) {
  transition: all 0.2s;
}
:deep(.p-button:hover) {
  transform: scale(1.05);
}
</style>