/**
 * Composable pour la gestion du mode sombre/clair
 * Gère la persistance des préférences et l'application des styles CSS
 * Utilise un sélecteur CSS personnalisé '.my-app-dark' pour la compatibilité PrimeVue
 *
 * @returns {Object} Interface pour contrôler le mode sombre
 */
export const useDarkMode = () => {
  // État global du mode sombre partagé entre tous les composants
  const isDarkMode = useState('darkMode', () => false)

  /**
   * Bascule entre mode clair et mode sombre
   * Met à jour l'interface et sauvegarde la préférence
   */
  const toggleDarkMode = () => {
    isDarkMode.value = !isDarkMode.value
    updateDarkModeClass()

    // Persistence de la préférence utilisateur dans localStorage
    if (process.client) {
      localStorage.setItem('darkMode', isDarkMode.value.toString())
    }
  }

  /**
   * Met à jour les classes CSS sur l'élément HTML racine
   * Applique ou retire la classe 'my-app-dark' selon l'état
   */
  const updateDarkModeClass = () => {
    if (process.client) {
      const htmlElement = document.documentElement
      if (isDarkMode.value) {
        // Ajouter la classe pour activer le mode sombre
        htmlElement.classList.add('my-app-dark')
      } else {
        // Retirer la classe pour revenir au mode clair
        htmlElement.classList.remove('my-app-dark')
      }
    }
  }

  /**
   * Initialise le mode sombre au chargement de l'application
   * Priorité : préférence sauvegardée > préférence système > mode clair par défaut
   */
  const initDarkMode = () => {
    if (process.client) {
      // 1. Vérifier s'il y a une préférence sauvegardée
      const savedPreference = localStorage.getItem('darkMode')
      if (savedPreference !== null) {
        isDarkMode.value = savedPreference === 'true'
      } else {
        // 2. Utiliser la préférence système si pas de sauvegarde
        isDarkMode.value = window.matchMedia('(prefers-color-scheme: dark)').matches
      }
      // Appliquer les classes CSS correspondantes
      updateDarkModeClass()
    }
  }

  // Interface publique du composable
  return {
    isDarkMode: readonly(isDarkMode), // État en lecture seule pour éviter les mutations directes
    toggleDarkMode,   // Fonction pour basculer le mode
    initDarkMode      // Fonction d'initialisation
  }
}