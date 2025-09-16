/**
 * Composable pour la gestion du suivi du temps des branchements électriques
 *
 * Fournit une interface complète pour :
 * - Démarrage/arrêt du chronomètre par phase (terrassement/branchement)
 * - Gestion des sessions de travail avec persistance en base
 * - Calcul automatique des durées et coûts
 * - Synchronisation en temps réel avec l'API
 *
 * @example
 * const {
 *   isRunning,
 *   currentSession,
 *   sessionHistory,
 *   startTimer,
 *   stopTimer,
 *   getTotalDuration,
 *   getTotalCost
 * } = useTimeTracking(interventionId);
 */

import { ref, computed, onMounted, onUnmounted, readonly } from 'vue'

export const useTimeTracking = (interventionId) => {
  const { $api } = useNuxtApp()

  // État réactif du chronomètre
  const isRunning = ref(false)
  const currentSession = ref(null)
  const sessionHistory = ref([])
  const elapsedTime = ref(0)

  // Intervalle pour la mise à jour du chronomètre
  let timerInterval = null

  /**
   * Démarre une nouvelle session de travail pour une phase
   *
   * @param {string} phase - 'terrassement' ou 'branchement'
   * @param {number} technicienId - ID du technicien qui démarre la session
   * @param {string} notes - Notes optionnelles pour la session
   * @returns {Promise<boolean>} True si le démarrage a réussi
   */
  const startTimer = async (phase, technicienId, notes = '') => {
    try {
      const response = await $api.post(`/intervention_electrique.php?action=demarrer_phase`, {
        intervention_id: interventionId,
        phase: phase,
        technicien_id: technicienId,
        notes: notes
      })

      if (response.success) {
        currentSession.value = {
          id: interventionId + '_' + phase,
          phase: phase,
          debut: response.data.heure_debut
        }
        isRunning.value = true
        elapsedTime.value = 0

        // Démarrer le chronomètre local
        startLocalTimer()

        return true
      }

      throw new Error(response.message || 'Erreur lors du démarrage de la session')
    } catch (error) {
      console.error('Erreur startTimer:', error)
      throw error
    }
  }

  /**
   * Arrête la session de travail en cours
   *
   * @param {string} notes - Notes finales pour la session
   * @returns {Promise<boolean>} True si l'arrêt a réussi
   */
  const stopTimer = async (notes = '') => {
    try {
      if (!currentSession.value) {
        throw new Error('Aucune session en cours')
      }

      const response = await $api.post(`/intervention_electrique.php?action=arreter_phase`, {
        intervention_id: interventionId,
        phase: currentSession.value.phase,
        notes: notes
      })

      if (response.success) {
        // Arrêter le chronomètre local
        stopLocalTimer()

        // Mettre à jour l'historique
        await loadSessionHistory()

        // Réinitialiser la session courante
        currentSession.value = null
        isRunning.value = false
        elapsedTime.value = 0

        return true
      }

      throw new Error(response.message || 'Erreur lors de l\'arrêt de la session')
    } catch (error) {
      console.error('Erreur stopTimer:', error)
      throw error
    }
  }

  /**
   * Charge l'historique des sessions pour l'intervention
   */
  const loadSessionHistory = async () => {
    try {
      const response = await $api.get(`/intervention_electrique.php?action=sessions&intervention_id=${interventionId}`)

      if (response.success) {
        sessionHistory.value = response.sessions || []
      }
    } catch (error) {
      console.error('Erreur loadSessionHistory:', error)
      sessionHistory.value = []
    }
  }

  /**
   * Vérifie s'il y a une session en cours au chargement
   */
  const checkActiveSession = async () => {
    try {
      const response = await $api.get(`/intervention_electrique.php?action=get_active_session&intervention_id=${interventionId}`)

      if (response.success && response.session) {
        currentSession.value = response.session
        isRunning.value = true

        // Calculer le temps écoulé depuis le début
        const startTime = new Date(response.session.debut)
        const now = new Date()
        elapsedTime.value = Math.floor((now - startTime) / 1000)

        // Démarrer le chronomètre local
        startLocalTimer()
      }
    } catch (error) {
      console.error('Erreur checkActiveSession:', error)
    }
  }

  /**
   * Démarre le chronomètre local (mise à jour chaque seconde)
   */
  const startLocalTimer = () => {
    if (timerInterval) {
      clearInterval(timerInterval)
    }

    timerInterval = setInterval(() => {
      elapsedTime.value++
    }, 1000)
  }

  /**
   * Arrête le chronomètre local
   */
  const stopLocalTimer = () => {
    if (timerInterval) {
      clearInterval(timerInterval)
      timerInterval = null
    }
  }

  /**
   * Formate une durée en secondes au format HH:MM:SS
   *
   * @param {number} seconds - Durée en secondes
   * @returns {string} Durée formatée
   */
  const formatDuration = (seconds) => {
    const hours = Math.floor(seconds / 3600)
    const minutes = Math.floor((seconds % 3600) / 60)
    const secs = seconds % 60

    return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
  }

  /**
   * Calcule la durée totale pour une phase donnée
   *
   * @param {string} phase - 'terrassement' ou 'branchement'
   * @returns {number} Durée totale en heures
   */
  const getTotalDuration = (phase) => {
    const phaseSessions = sessionHistory.value.filter(s => s.phase === phase && s.duree_minutes)
    const totalMinutes = phaseSessions.reduce((total, session) => total + (session.duree_minutes || 0), 0)
    return totalMinutes / 60
  }

  /**
   * Calcule le coût total pour une phase donnée
   *
   * @param {string} phase - 'terrassement' ou 'branchement'
   * @param {number} tauxHoraire - Taux horaire du technicien
   * @returns {number} Coût total en euros
   */
  const getTotalCost = (phase, tauxHoraire) => {
    const duration = getTotalDuration(phase)
    return duration * tauxHoraire
  }

  // Propriétés calculées
  const formattedElapsedTime = computed(() => formatDuration(elapsedTime.value))

  const currentPhase = computed(() => currentSession.value?.phase || null)

  const terrassementSessions = computed(() =>
    sessionHistory.value.filter(s => s.phase === 'terrassement')
  )

  const branchementSessions = computed(() =>
    sessionHistory.value.filter(s => s.phase === 'branchement')
  )

  const totalTerrassementDuration = computed(() => getTotalDuration('terrassement'))
  const totalBranchementDuration = computed(() => getTotalDuration('branchement'))

  // Initialisation au montage du composant
  onMounted(async () => {
    await loadSessionHistory()
    await checkActiveSession()
  })

  // Nettoyage au démontage
  onUnmounted(() => {
    stopLocalTimer()
  })

  return {
    // État
    isRunning: readonly(isRunning),
    currentSession: readonly(currentSession),
    sessionHistory: readonly(sessionHistory),
    elapsedTime: readonly(elapsedTime),

    // Actions
    startTimer,
    stopTimer,
    loadSessionHistory,

    // Utilitaires
    formatDuration,
    getTotalDuration,
    getTotalCost,

    // Propriétés calculées
    formattedElapsedTime,
    currentPhase,
    terrassementSessions,
    branchementSessions,
    totalTerrassementDuration,
    totalBranchementDuration
  }
}