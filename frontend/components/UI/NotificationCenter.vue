<template>
  <div class="notification-center">
    <!-- Icône de notification avec badge -->
    <div class="relative">
      <Button
        icon="pi pi-bell"
        severity="secondary"
        text
        rounded
        @click="toggleNotifications"
        v-tooltip.bottom="'Notifications'"
        :badge="unreadCount > 0 ? unreadCount.toString() : undefined"
        :badge-severity="unreadCount > 0 ? 'danger' : undefined"
      />

      <!-- Indicateur pulse pour nouvelles notifications -->
      <div
        v-if="hasNewNotifications"
        class="absolute top-1 right-1 w-3 h-3 bg-red-500 rounded-full animate-pulse"
      ></div>
    </div>

    <!-- Panel des notifications -->
    <OverlayPanel ref="notificationPanel" appendTo="body" :showCloseIcon="true" class="w-96">
      <template #header>
        <div class="flex items-center justify-between w-full p-4 border-b">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            Notifications
          </h3>
          <div class="flex gap-2">
            <Button
              v-if="unreadCount > 0"
              label="Tout marquer lu"
              size="small"
              text
              @click="markAllAsRead"
            />
            <Button
              icon="pi pi-cog"
              size="small"
              text
              rounded
              @click="openSettings"
              v-tooltip="'Paramètres'"
            />
          </div>
        </div>
      </template>

      <div class="max-h-96 overflow-y-auto">
        <!-- Filtres des notifications -->
        <div class="p-3 border-b bg-gray-50 dark:bg-gray-800">
          <div class="flex gap-2 flex-wrap">
            <Button
              :label="`Tous (${notifications.length})`"
              :severity="filtreActif === 'tous' ? 'primary' : 'secondary'"
              size="small"
              @click="filtreActif = 'tous'"
            />
            <Button
              :label="`Critiques (${notifications.filter(n => n.priorite === 'critique').length})`"
              :severity="filtreActif === 'critiques' ? 'danger' : 'secondary'"
              size="small"
              @click="filtreActif = 'critiques'"
            />
            <Button
              :label="`Délais (${notifications.filter(n => n.type === 'delai').length})`"
              :severity="filtreActif === 'delais' ? 'warning' : 'secondary'"
              size="small"
              @click="filtreActif = 'delais'"
            />
          </div>
        </div>

        <!-- Liste des notifications -->
        <div v-if="notificationsFiltrees.length === 0" class="p-6 text-center text-gray-500 dark:text-gray-400">
          <i class="pi pi-bell-slash text-3xl mb-3"></i>
          <p>Aucune notification {{ filtreActif !== 'tous' ? `de type "${filtreActif}"` : '' }}</p>
        </div>

        <div v-else class="space-y-1">
          <div
            v-for="notification in notificationsFiltrees"
            :key="notification.id"
            class="notification-item p-4 border-b border-gray-100 dark:border-gray-700 cursor-pointer transition-colors"
            :class="{
              'bg-blue-50 dark:bg-blue-900/20': !notification.lue,
              'hover:bg-gray-50 dark:hover:bg-gray-700': true
            }"
            @click="clickNotification(notification)"
          >
            <div class="flex items-start gap-3">
              <!-- Icône selon le type -->
              <div class="flex-shrink-0 mt-1">
                <i
                  :class="getNotificationIcon(notification)"
                  class="text-lg"
                ></i>
              </div>

              <!-- Contenu -->
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between">
                  <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                    {{ notification.titre }}
                  </h4>
                  <div class="flex items-center gap-2 ml-2">
                    <Badge
                      :value="notification.priorite"
                      :severity="getPrioiteSeverity(notification.priorite)"
                      size="small"
                    />
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                      {{ formatTempsEcoule(notification.dateCreation) }}
                    </span>
                  </div>
                </div>

                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                  {{ notification.message }}
                </p>

                <!-- Actions rapides -->
                <div v-if="notification.actions" class="flex gap-2 mt-2">
                  <Button
                    v-for="action in notification.actions"
                    :key="action.id"
                    :label="action.label"
                    :icon="action.icon"
                    size="small"
                    :severity="action.severity || 'secondary'"
                    @click.stop="executeAction(action, notification)"
                  />
                </div>
              </div>

              <!-- Indicateur non lu -->
              <div v-if="!notification.lue" class="flex-shrink-0">
                <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer avec actions globales -->
      <template #footer>
        <div class="p-3 border-t bg-gray-50 dark:bg-gray-800">
          <div class="flex justify-between items-center">
            <span class="text-xs text-gray-500 dark:text-gray-400">
              {{ unreadCount }} non lues sur {{ notifications.length }}
            </span>
            <div class="flex gap-2">
              <Button
                label="Paramètres"
                icon="pi pi-cog"
                size="small"
                text
                @click="openSettings"
              />
              <Button
                label="Historique"
                icon="pi pi-history"
                size="small"
                text
                @click="openHistory"
              />
            </div>
          </div>
        </div>
      </template>
    </OverlayPanel>

    <!-- Dialog des paramètres -->
    <Dialog
      v-model:visible="showSettings"
      header="Paramètres des notifications"
      :modal="true"
      class="w-full max-w-md"
    >
      <div class="space-y-4">
        <div>
          <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Types de notifications</label>
          <div class="mt-2 space-y-2">
            <div v-for="type in typesNotifications" :key="type.id" class="flex items-center">
              <Checkbox
                v-model="type.actif"
                :inputId="type.id"
                binary
              />
              <label :for="type.id" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                {{ type.label }}
              </label>
            </div>
          </div>
        </div>

        <div>
          <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Fréquence de vérification</label>
          <Dropdown
            v-model="frequenceVerification"
            :options="frequenceOptions"
            option-label="label"
            option-value="value"
            class="w-full mt-2"
          />
        </div>

        <div class="flex items-center">
          <Checkbox
            v-model="notificationsDesktop"
            inputId="desktop"
            binary
          />
          <label for="desktop" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
            Notifications bureau (navigateur)
          </label>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end gap-2">
          <Button label="Annuler" text @click="showSettings = false" />
          <Button label="Sauvegarder" @click="saveSettings" />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useToast } from 'primevue/usetoast'

const toast = useToast()

// Refs
const notificationPanel = ref()
const showSettings = ref(false)
const filtreActif = ref('tous')
const hasNewNotifications = ref(false)

// Configuration
const frequenceVerification = ref(30000) // 30 secondes
const notificationsDesktop = ref(false)
const intervalId = ref(null)

// Options de fréquence
const frequenceOptions = [
  { label: '15 secondes', value: 15000 },
  { label: '30 secondes', value: 30000 },
  { label: '1 minute', value: 60000 },
  { label: '5 minutes', value: 300000 }
]

// Types de notifications configurables
const typesNotifications = ref([
  { id: 'delai', label: 'Alertes de délai', actif: true },
  { id: 'retard', label: 'Retards critiques', actif: true },
  { id: 'incoherence', label: 'Incohérences détectées', actif: true },
  { id: 'ressources', label: 'Recommandations ressources', actif: false },
  { id: 'maintenance', label: 'Maintenance système', actif: true }
])

// Notifications
const notifications = ref([
  {
    id: 1,
    type: 'retard',
    priorite: 'critique',
    titre: 'Retard critique - BR-2024-001',
    message: 'Branchement Dupont dépasse 35 jours, client mécontent',
    dateCreation: new Date(Date.now() - 3600000), // Il y a 1h
    lue: false,
    interventionId: 1,
    actions: [
      { id: 'voir', label: 'Voir', icon: 'pi pi-eye', severity: 'info' },
      { id: 'assigner', label: 'Assigner', icon: 'pi pi-user-plus', severity: 'warning' }
    ]
  },
  {
    id: 2,
    type: 'delai',
    priorite: 'moyenne',
    titre: 'Délai limite approche',
    message: '3 branchements approchent de la limite des 21 jours',
    dateCreation: new Date(Date.now() - 7200000), // Il y a 2h
    lue: false,
    actions: [
      { id: 'planifier', label: 'Planifier', icon: 'pi pi-calendar', severity: 'warning' }
    ]
  },
  {
    id: 3,
    type: 'incoherence',
    priorite: 'moyenne',
    titre: 'Incohérence Type/DI détectée',
    message: 'BR-2024-003 classé Type 1 mais DI = 35m',
    dateCreation: new Date(Date.now() - 10800000), // Il y a 3h
    lue: true,
    interventionId: 3,
    actions: [
      { id: 'corriger', label: 'Corriger', icon: 'pi pi-wrench', severity: 'danger' }
    ]
  }
])

// Computed
const unreadCount = computed(() => notifications.value.filter(n => !n.lue).length)

const notificationsFiltrees = computed(() => {
  let filtered = notifications.value

  switch (filtreActif.value) {
    case 'critiques':
      filtered = filtered.filter(n => n.priorite === 'critique')
      break
    case 'delais':
      filtered = filtered.filter(n => n.type === 'delai')
      break
  }

  return filtered.sort((a, b) => {
    // Priorité : non lues d'abord, puis par date
    if (a.lue !== b.lue) return a.lue ? 1 : -1
    return new Date(b.dateCreation) - new Date(a.dateCreation)
  })
})

// Méthodes
const toggleNotifications = (event) => {
  notificationPanel.value.toggle(event)
  if (hasNewNotifications.value) {
    hasNewNotifications.value = false
  }
}

const getNotificationIcon = (notification) => {
  const icons = {
    delai: 'pi pi-clock text-orange-500',
    retard: 'pi pi-exclamation-triangle text-red-500',
    incoherence: 'pi pi-flag text-purple-500',
    ressources: 'pi pi-users text-blue-500',
    maintenance: 'pi pi-wrench text-gray-500'
  }
  return icons[notification.type] || 'pi pi-info-circle text-gray-500'
}

const getPrioiteSeverity = (priorite) => {
  switch (priorite) {
    case 'critique': return 'danger'
    case 'haute': return 'warning'
    case 'moyenne': return 'info'
    case 'basse': return 'secondary'
    default: return 'secondary'
  }
}

const formatTempsEcoule = (date) => {
  const now = new Date()
  const diff = now - new Date(date)
  const minutes = Math.floor(diff / 60000)
  const hours = Math.floor(minutes / 60)
  const days = Math.floor(hours / 24)

  if (days > 0) return `${days}j`
  if (hours > 0) return `${hours}h`
  if (minutes > 0) return `${minutes}m`
  return 'maintenant'
}

const clickNotification = (notification) => {
  if (!notification.lue) {
    notification.lue = true
  }

  // Navigation selon le type
  if (notification.interventionId) {
    navigateTo(`/interventions/electrique/${notification.interventionId}`)
  }
}

const markAllAsRead = () => {
  notifications.value.forEach(n => n.lue = true)
  toast.add({
    severity: 'success',
    summary: 'Notifications marquées',
    detail: 'Toutes les notifications ont été marquées comme lues',
    life: 2000
  })
}

const executeAction = (action, notification) => {
  switch (action.id) {
    case 'voir':
      navigateTo(`/interventions/electrique/${notification.interventionId}`)
      break
    case 'assigner':
      // Ouvrir modal d'assignation
      toast.add({
        severity: 'info',
        summary: 'Assignation',
        detail: 'Fonctionnalité d\'assignation en développement',
        life: 3000
      })
      break
    case 'planifier':
      navigateTo('/planning')
      break
    case 'corriger':
      navigateTo(`/interventions/electrique/${notification.interventionId}?mode=edit`)
      break
  }
}

const checkNewNotifications = async () => {
  try {
    // Simulation de vérification de nouvelles notifications
    // Dans un vrai contexte, ceci ferait un appel API
    const random = Math.random()
    if (random < 0.1) { // 10% de chance d'avoir une nouvelle notification
      const newNotification = {
        id: Date.now(),
        type: 'delai',
        priorite: 'moyenne',
        titre: 'Nouvelle alerte',
        message: 'Une nouvelle situation nécessite votre attention',
        dateCreation: new Date(),
        lue: false
      }

      notifications.value.unshift(newNotification)
      hasNewNotifications.value = true

      // Notification bureau si activée
      if (notificationsDesktop.value && 'Notification' in window) {
        new Notification('Nouvelle notification', {
          body: newNotification.message,
          icon: '/favicon.ico'
        })
      }
    }
  } catch (error) {
    console.error('Erreur lors de la vérification des notifications:', error)
  }
}

const openSettings = () => {
  showSettings.value = true
}

const openHistory = () => {
  navigateTo('/notifications/history')
}

const saveSettings = () => {
  // Sauvegarder les paramètres
  localStorage.setItem('notificationSettings', JSON.stringify({
    typesNotifications: typesNotifications.value,
    frequenceVerification: frequenceVerification.value,
    notificationsDesktop: notificationsDesktop.value
  }))

  // Redémarrer l'interval avec la nouvelle fréquence
  if (intervalId.value) {
    clearInterval(intervalId.value)
  }
  intervalId.value = setInterval(checkNewNotifications, frequenceVerification.value)

  showSettings.value = false
  toast.add({
    severity: 'success',
    summary: 'Paramètres sauvegardés',
    detail: 'Vos préférences de notification ont été enregistrées',
    life: 3000
  })
}

const loadSettings = () => {
  const saved = localStorage.getItem('notificationSettings')
  if (saved) {
    const settings = JSON.parse(saved)
    typesNotifications.value = settings.typesNotifications || typesNotifications.value
    frequenceVerification.value = settings.frequenceVerification || frequenceVerification.value
    notificationsDesktop.value = settings.notificationsDesktop || false
  }
}

const requestNotificationPermission = async () => {
  if ('Notification' in window && Notification.permission === 'default') {
    await Notification.requestPermission()
  }
}

// Lifecycle
onMounted(() => {
  loadSettings()
  requestNotificationPermission()

  // Démarrer la vérification périodique
  intervalId.value = setInterval(checkNewNotifications, frequenceVerification.value)
})

onUnmounted(() => {
  if (intervalId.value) {
    clearInterval(intervalId.value)
  }
})
</script>

<style scoped>
.notification-item {
  transition: all 0.2s ease;
}

.notification-item:hover {
  transform: translateX(2px);
}

:deep(.p-overlaypanel-content) {
  padding: 0;
}

:deep(.p-button-badge) {
  min-width: 1.2rem;
  height: 1.2rem;
  font-size: 0.7rem;
}
</style>