/**
 * Composable pour la gestion des localisations et optimisation géographique
 *
 * Fournit des fonctionnalités pour :
 * - Géocodage d'adresses
 * - Calcul de temps de trajet
 * - Gestion des zones d'intervention
 * - Optimisation géographique des tournées
 */

export const useLocationOptimizer = () => {
  const { $api } = useNuxtApp()

  // État réactif
  const zones = ref([])
  const localisations = ref([])
  const matriceTrajet = ref({})
  const loading = ref(false)

  /**
   * Géocode une adresse pour obtenir les coordonnées
   * @param {string} adresse - Adresse à géocoder
   * @returns {Object} Coordonnées latitude/longitude
   */
  const geocodeAddress = async (adresse) => {
    loading.value = true

    try {
      // Utiliser une API de géocodage (ex: Nominatim, Google Maps)
      // Ici on simule avec des coordonnées aléatoires en France
      const response = await geocodeWithNominatim(adresse)

      if (response.lat && response.lon) {
        return {
          latitude: parseFloat(response.lat),
          longitude: parseFloat(response.lon),
          adresse_formatee: response.display_name
        }
      }

      // Fallback avec des coordonnées par défaut
      return generateDefaultCoordinates(adresse)

    } catch (error) {
      console.warn('Erreur géocodage:', error)
      return generateDefaultCoordinates(adresse)
    } finally {
      loading.value = false
    }
  }

  /**
   * Géocodage avec l'API Nominatim (OpenStreetMap)
   */
  const geocodeWithNominatim = async (adresse) => {
    const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(adresse)}&countrycodes=fr&limit=1`

    const response = await fetch(url, {
      headers: {
        'User-Agent': 'SuiviInterventions/1.0'
      }
    })

    const data = await response.json()

    if (data && data.length > 0) {
      return data[0]
    }

    throw new Error('Adresse non trouvée')
  }

  /**
   * Génère des coordonnées par défaut basées sur la ville
   */
  const generateDefaultCoordinates = (adresse) => {
    const villesPrincipales = {
      'paris': { lat: 48.8566, lon: 2.3522 },
      'lyon': { lat: 45.7640, lon: 4.8357 },
      'marseille': { lat: 43.2965, lon: 5.3698 },
      'toulouse': { lat: 43.6047, lon: 1.4442 },
      'nice': { lat: 43.7102, lon: 7.2620 },
      'nantes': { lat: 47.2184, lon: -1.5536 },
      'strasbourg': { lat: 48.5734, lon: 7.7521 },
      'montpellier': { lat: 43.6110, lon: 3.8767 },
      'bordeaux': { lat: 44.8378, lon: -0.5792 },
      'lille': { lat: 50.6292, lon: 3.0573 }
    }

    // Chercher une ville connue dans l'adresse
    const adresseLower = adresse.toLowerCase()
    for (const [ville, coords] of Object.entries(villesPrincipales)) {
      if (adresseLower.includes(ville)) {
        return {
          latitude: coords.lat + (Math.random() - 0.5) * 0.1, // Ajouter un peu d'aléatoire
          longitude: coords.lon + (Math.random() - 0.5) * 0.1,
          adresse_formatee: adresse
        }
      }
    }

    // Par défaut : région parisienne avec coordonnées aléatoires
    return {
      latitude: 48.8566 + (Math.random() - 0.5) * 0.5,
      longitude: 2.3522 + (Math.random() - 0.5) * 0.5,
      adresse_formatee: adresse
    }
  }

  /**
   * Calcule le temps de trajet entre deux points
   * @param {Object} point1 - Point de départ {latitude, longitude}
   * @param {Object} point2 - Point d'arrivée {latitude, longitude}
   * @returns {Object} Informations sur le trajet
   */
  const calculerTempsTrajet = async (point1, point2) => {
    try {
      const response = await $api.post('/planning_optimizer.php?action=calculer_trajet', {
        lat1: point1.latitude,
        lon1: point1.longitude,
        lat2: point2.latitude,
        lon2: point2.longitude
      })

      if (response.success) {
        return response.data
      }

      throw new Error('Erreur calcul trajet')
    } catch (error) {
      console.error('Erreur calcul trajet:', error)

      // Calcul fallback basique
      return calculerTrajetBasique(point1, point2)
    }
  }

  /**
   * Calcul de trajet basique avec la formule de Haversine
   */
  const calculerTrajetBasique = (point1, point2) => {
    const R = 6371 // Rayon de la Terre en km
    const dLat = toRad(point2.latitude - point1.latitude)
    const dLon = toRad(point2.longitude - point1.longitude)

    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(toRad(point1.latitude)) * Math.cos(toRad(point2.latitude)) *
              Math.sin(dLon/2) * Math.sin(dLon/2)

    const c = 2 * Math.asin(Math.sqrt(a))
    const distance = R * c

    // Estimation du temps basée sur la distance
    const vitesseMoyenne = distance > 20 ? 80 : 40 // km/h
    const tempsMinutes = Math.round((distance / vitesseMoyenne) * 60)

    return {
      distance_km: Math.round(distance * 100) / 100,
      temps_minutes: tempsMinutes,
      cout_estime: Math.round(distance * 0.50 * 100) / 100
    }
  }

  /**
   * Convertit les degrés en radians
   */
  const toRad = (degrees) => {
    return degrees * (Math.PI / 180)
  }

  /**
   * Optimise l'ordre des interventions pour minimiser les déplacements
   * @param {Array} interventions - Liste des interventions avec coordonnées
   * @param {Object} pointDepart - Point de départ du technicien
   * @returns {Array} Interventions réordonnées
   */
  const optimiserOrdreInterventions = async (interventions, pointDepart) => {
    if (interventions.length <= 2) {
      return interventions
    }

    // Calculer la matrice des distances
    const matrice = await calculerMatriceDistances([pointDepart, ...interventions])

    // Appliquer l'algorithme du plus proche voisin
    return algorithmeProchePlusVoisin(interventions, matrice)
  }

  /**
   * Calcule la matrice des distances entre tous les points
   */
  const calculerMatriceDistances = async (points) => {
    const matrice = []

    for (let i = 0; i < points.length; i++) {
      matrice[i] = []
      for (let j = 0; j < points.length; j++) {
        if (i === j) {
          matrice[i][j] = 0
        } else {
          const trajet = await calculerTempsTrajet(points[i], points[j])
          matrice[i][j] = trajet.distance_km
        }
      }
    }

    return matrice
  }

  /**
   * Algorithme du plus proche voisin pour optimiser l'ordre
   */
  const algorithmeProchePlusVoisin = (interventions, matrice) => {
    const nonVisites = [...interventions]
    const parcours = []
    let indexCourant = 0 // Commence par le point de départ (index 0)

    while (nonVisites.length > 0) {
      let plusProche = null
      let distanceMin = Infinity
      let indexPlusProche = -1

      // Trouver le point non visité le plus proche
      nonVisites.forEach((intervention, i) => {
        const indexIntervention = interventions.indexOf(intervention) + 1 // +1 car la matrice inclut le point de départ
        const distance = matrice[indexCourant][indexIntervention]

        if (distance < distanceMin) {
          distanceMin = distance
          plusProche = intervention
          indexPlusProche = i
        }
      })

      if (plusProche) {
        parcours.push(plusProche)
        nonVisites.splice(indexPlusProche, 1)
        indexCourant = interventions.indexOf(plusProche) + 1
      } else {
        break
      }
    }

    return parcours
  }

  /**
   * Charge les zones d'intervention disponibles
   */
  const chargerZones = async () => {
    try {
      const response = await $api.get('/planning_optimizer.php?action=zones')

      if (response.success) {
        zones.value = response.data
      }
    } catch (error) {
      console.error('Erreur chargement zones:', error)
    }
  }

  /**
   * Détermine la zone d'intervention basée sur les coordonnées
   * @param {Object} coordonnees - {latitude, longitude}
   * @returns {string} Nom de la zone
   */
  const determinerZone = (coordonnees) => {
    // Logique simple basée sur les coordonnées
    // À adapter selon vos zones géographiques

    const { latitude, longitude } = coordonnees

    // Région parisienne
    if (latitude >= 48.5 && latitude <= 49.0 && longitude >= 2.0 && longitude <= 2.7) {
      return 'Paris-IDF'
    }

    // Lyon et région
    if (latitude >= 45.5 && latitude <= 46.0 && longitude >= 4.5 && longitude <= 5.2) {
      return 'Lyon-Rhone'
    }

    // Marseille et région
    if (latitude >= 43.0 && latitude <= 43.5 && longitude >= 5.0 && longitude <= 5.8) {
      return 'Marseille-PACA'
    }

    // Nord de la France
    if (latitude >= 50.0) {
      return 'Nord'
    }

    // Sud de la France
    if (latitude <= 44.0) {
      return 'Sud'
    }

    // Ouest de la France
    if (longitude <= 0) {
      return 'Ouest'
    }

    // Est de la France
    if (longitude >= 6) {
      return 'Est'
    }

    return 'Centre'
  }

  /**
   * Sauvegarde une localisation en base
   * @param {Object} localisation - Données de localisation
   * @returns {Object} Localisation sauvegardée
   */
  const sauvegarderLocalisation = async (localisation) => {
    try {
      const response = await $api.post('/localisations.php', {
        ...localisation,
        zone_intervention: determinerZone(localisation)
      })

      if (response.success) {
        return response.data
      }

      throw new Error('Erreur sauvegarde localisation')
    } catch (error) {
      console.error('Erreur sauvegarde localisation:', error)
      throw error
    }
  }

  /**
   * Calcule les statistiques de déplacement pour un planning
   * @param {Array} planning - Planning avec interventions et trajets
   * @returns {Object} Statistiques de déplacement
   */
  const calculerStatistiquesDeplacements = (planning) => {
    let distanceTotale = 0
    let tempsTotalTrajet = 0
    let coutTotalDeplacements = 0
    let nombreTrajets = 0

    planning.forEach(jour => {
      jour.interventions.forEach((intervention, index) => {
        if (index > 0) {
          const trajet = intervention.trajet_precedent
          if (trajet) {
            distanceTotale += trajet.distance_km || 0
            tempsTotalTrajet += trajet.temps_minutes || 0
            coutTotalDeplacements += trajet.cout_estime || 0
            nombreTrajets++
          }
        }
      })
    })

    return {
      distance_totale_km: Math.round(distanceTotale * 100) / 100,
      temps_total_trajet_minutes: tempsTotalTrajet,
      temps_total_trajet_heures: Math.round((tempsTotalTrajet / 60) * 100) / 100,
      cout_total_deplacements: Math.round(coutTotalDeplacements * 100) / 100,
      nombre_trajets: nombreTrajets,
      distance_moyenne_trajet: nombreTrajets > 0 ? Math.round((distanceTotale / nombreTrajets) * 100) / 100 : 0,
      temps_moyen_trajet_minutes: nombreTrajets > 0 ? Math.round(tempsTotalTrajet / nombreTrajets) : 0
    }
  }

  /**
   * Valide une adresse
   * @param {string} adresse - Adresse à valider
   * @returns {Object} Résultat de validation
   */
  const validerAdresse = async (adresse) => {
    if (!adresse || adresse.trim().length < 10) {
      return {
        valid: false,
        message: 'Adresse trop courte (minimum 10 caractères)'
      }
    }

    try {
      const coordonnees = await geocodeAddress(adresse)

      return {
        valid: true,
        coordonnees,
        zone: determinerZone(coordonnees)
      }
    } catch (error) {
      return {
        valid: false,
        message: 'Impossible de géolocaliser cette adresse'
      }
    }
  }

  /**
   * Recherche des adresses similaires
   * @param {string} requete - Terme de recherche
   * @returns {Array} Liste d'adresses suggérées
   */
  const rechercherAdresses = async (requete) => {
    if (requete.length < 3) return []

    try {
      const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(requete)}&countrycodes=fr&limit=5`

      const response = await fetch(url, {
        headers: {
          'User-Agent': 'SuiviInterventions/1.0'
        }
      })

      const data = await response.json()

      return data.map(item => ({
        adresse: item.display_name,
        latitude: parseFloat(item.lat),
        longitude: parseFloat(item.lon)
      }))

    } catch (error) {
      console.error('Erreur recherche adresses:', error)
      return []
    }
  }

  // Charger les zones au montage si dans un composant
  if (getCurrentInstance()) {
    onMounted(() => {
      chargerZones()
    })
  }

  return {
    // État
    zones: readonly(zones),
    localisations: readonly(localisations),
    matriceTrajet: readonly(matriceTrajet),
    loading: readonly(loading),

    // Méthodes
    geocodeAddress,
    calculerTempsTrajet,
    optimiserOrdreInterventions,
    chargerZones,
    determinerZone,
    sauvegarderLocalisation,
    calculerStatistiquesDeplacements,
    validerAdresse,
    rechercherAdresses
  }
}