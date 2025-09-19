import { describe, it, expect, beforeEach, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { resetAllMocks, createMockIntervention } from '../setup'
import type { InterventionListItem, InterventionFilters } from '../../types'

// Mock du store interventions
const mockInterventionsStore = () => {
  const interventions = ref<InterventionListItem[]>([])
  const currentIntervention = ref(null)
  const loading = ref(false)
  const error = ref(null)
  const filters = ref<InterventionFilters>({
    technicien_id: null,
    dateFrom: null,
    dateTo: null,
    status: []
  })

  // Getters
  const filteredInterventions = computed(() => {
    return interventions.value.filter((intervention) => {
      // Filtre par technicien
      if (filters.value.technicien_id &&
          intervention.technicien_terrassement !== filters.value.technicien_id &&
          intervention.technicien_branchement !== filters.value.technicien_id) {
        return false
      }

      // Filtre par statut
      if (filters.value.status && filters.value.status.length > 0 &&
          !filters.value.status.includes(intervention.statut_global)) {
        return false
      }

      // Filtre par date
      if (filters.value.dateFrom || filters.value.dateTo) {
        const interventionDate = new Date(intervention.date_creation)
        if (filters.value.dateFrom && interventionDate < new Date(filters.value.dateFrom)) {
          return false
        }
        if (filters.value.dateTo && interventionDate > new Date(filters.value.dateTo)) {
          return false
        }
      }

      return true
    })
  })

  const interventionsByStatus = computed(() => {
    return {
      enAttente: interventions.value.filter(i => i.statut_global === 'En attente').length,
      enCours: interventions.value.filter(i => i.statut_global === 'En cours').length,
      enPause: interventions.value.filter(i => i.statut_global === 'En pause').length,
      terminees: interventions.value.filter(i => i.statut_global === 'Terminée').length,
      annulees: interventions.value.filter(i => i.statut_global === 'Annulée').length,
      total: interventions.value.length
    }
  })

  // Actions
  const fetchInterventions = async (params = {}) => {
    loading.value = true
    error.value = null

    try {
      // Simuler un appel API
      await new Promise(resolve => setTimeout(resolve, 100))

      const mockData = [
        createMockIntervention({ id: 1, statut_global: 'En attente' }),
        createMockIntervention({ id: 2, statut_global: 'En cours' }),
        createMockIntervention({ id: 3, statut_global: 'Terminée' })
      ]

      interventions.value = mockData
      return mockData
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Erreur de chargement'
      throw err
    } finally {
      loading.value = false
    }
  }

  const fetchIntervention = async (id: number) => {
    loading.value = true
    error.value = null

    try {
      await new Promise(resolve => setTimeout(resolve, 50))

      const intervention = createMockIntervention({ id })
      currentIntervention.value = intervention
      return intervention
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Erreur de chargement'
      throw err
    } finally {
      loading.value = false
    }
  }

  const createIntervention = async (data: any) => {
    loading.value = true
    error.value = null

    try {
      await new Promise(resolve => setTimeout(resolve, 100))

      if (!data.titre || !data.client_nom) {
        throw new Error('Données incomplètes')
      }

      const newIntervention = createMockIntervention({
        id: Date.now(),
        ...data
      })

      interventions.value.push(newIntervention)
      return newIntervention
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Erreur de création'
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateIntervention = async (id: number, data: any) => {
    loading.value = true
    error.value = null

    try {
      await new Promise(resolve => setTimeout(resolve, 100))

      const index = interventions.value.findIndex(i => i.id === id)
      if (index === -1) {
        throw new Error('Intervention non trouvée')
      }

      const updatedIntervention = { ...interventions.value[index], ...data }
      interventions.value[index] = updatedIntervention

      if (currentIntervention.value?.id === id) {
        currentIntervention.value = updatedIntervention
      }

      return updatedIntervention
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Erreur de mise à jour'
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteIntervention = async (id: number) => {
    loading.value = true
    error.value = null

    try {
      await new Promise(resolve => setTimeout(resolve, 100))

      const index = interventions.value.findIndex(i => i.id === id)
      if (index === -1) {
        throw new Error('Intervention non trouvée')
      }

      interventions.value.splice(index, 1)

      if (currentIntervention.value?.id === id) {
        currentIntervention.value = null
      }

      return true
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Erreur de suppression'
      throw err
    } finally {
      loading.value = false
    }
  }

  const setFilters = (newFilters: Partial<InterventionFilters>) => {
    filters.value = { ...filters.value, ...newFilters }
  }

  const clearFilters = () => {
    filters.value = {
      technicien_id: null,
      dateFrom: null,
      dateTo: null,
      status: []
    }
  }

  return {
    interventions: readonly(interventions),
    currentIntervention: readonly(currentIntervention),
    loading: readonly(loading),
    error: readonly(error),
    filters: readonly(filters),
    filteredInterventions,
    interventionsByStatus,
    fetchInterventions,
    fetchIntervention,
    createIntervention,
    updateIntervention,
    deleteIntervention,
    setFilters,
    clearFilters
  }
}

describe('Interventions Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    resetAllMocks()
  })

  describe('État initial', () => {
    it('devrait avoir un état initial correct', () => {
      const store = mockInterventionsStore()

      expect(store.interventions.value).toEqual([])
      expect(store.currentIntervention.value).toBeNull()
      expect(store.loading.value).toBe(false)
      expect(store.error.value).toBeNull()
      expect(store.filters.value).toEqual({
        technicien_id: null,
        dateFrom: null,
        dateTo: null,
        status: []
      })
    })
  })

  describe('fetchInterventions', () => {
    it('devrait charger les interventions avec succès', async () => {
      const store = mockInterventionsStore()

      const result = await store.fetchInterventions()

      expect(store.interventions.value).toHaveLength(3)
      expect(store.interventions.value[0].statut_global).toBe('En attente')
      expect(store.interventions.value[1].statut_global).toBe('En cours')
      expect(store.interventions.value[2].statut_global).toBe('Terminée')
      expect(store.error.value).toBeNull()
      expect(result).toBe(store.interventions.value)
    })

    it('devrait gérer les états de chargement', async () => {
      const store = mockInterventionsStore()

      const fetchPromise = store.fetchInterventions()
      expect(store.loading.value).toBe(true)

      await fetchPromise
      expect(store.loading.value).toBe(false)
    })
  })

  describe('fetchIntervention', () => {
    it('devrait charger une intervention spécifique', async () => {
      const store = mockInterventionsStore()

      const result = await store.fetchIntervention(123)

      expect(store.currentIntervention.value).toBeTruthy()
      expect(store.currentIntervention.value?.id).toBe(123)
      expect(result.id).toBe(123)
    })
  })

  describe('createIntervention', () => {
    it('devrait créer une nouvelle intervention', async () => {
      const store = mockInterventionsStore()

      const newData = {
        titre: 'Nouvelle intervention',
        client_nom: 'Client Test',
        priorite: 'Haute'
      }

      const result = await store.createIntervention(newData)

      expect(store.interventions.value).toHaveLength(1)
      expect(store.interventions.value[0].titre).toBe('Nouvelle intervention')
      expect(store.interventions.value[0].client_nom).toBe('Client Test')
      expect(result.titre).toBe('Nouvelle intervention')
    })

    it('devrait rejeter les données incomplètes', async () => {
      const store = mockInterventionsStore()

      await expect(store.createIntervention({})).rejects.toThrow('Données incomplètes')
      expect(store.interventions.value).toHaveLength(0)
      expect(store.error.value).toBe('Données incomplètes')
    })
  })

  describe('updateIntervention', () => {
    it('devrait mettre à jour une intervention existante', async () => {
      const store = mockInterventionsStore()

      // D'abord créer une intervention
      await store.createIntervention({
        titre: 'Original',
        client_nom: 'Client Test'
      })

      const interventionId = store.interventions.value[0].id

      // Puis la mettre à jour
      const updateData = { titre: 'Modifié' }
      await store.updateIntervention(interventionId, updateData)

      expect(store.interventions.value[0].titre).toBe('Modifié')
      expect(store.interventions.value[0].client_nom).toBe('Client Test') // Inchangé
    })

    it('devrait rejeter la mise à jour d\'une intervention inexistante', async () => {
      const store = mockInterventionsStore()

      await expect(store.updateIntervention(999, { titre: 'Test' }))
        .rejects.toThrow('Intervention non trouvée')
    })
  })

  describe('deleteIntervention', () => {
    it('devrait supprimer une intervention', async () => {
      const store = mockInterventionsStore()

      // Créer une intervention
      await store.createIntervention({
        titre: 'À supprimer',
        client_nom: 'Client Test'
      })

      expect(store.interventions.value).toHaveLength(1)

      const interventionId = store.interventions.value[0].id
      await store.deleteIntervention(interventionId)

      expect(store.interventions.value).toHaveLength(0)
    })

    it('devrait nettoyer currentIntervention si supprimée', async () => {
      const store = mockInterventionsStore()

      // Créer et charger une intervention
      await store.createIntervention({
        titre: 'Test',
        client_nom: 'Client Test'
      })

      const interventionId = store.interventions.value[0].id
      await store.fetchIntervention(interventionId)

      expect(store.currentIntervention.value).toBeTruthy()

      // Supprimer l'intervention
      await store.deleteIntervention(interventionId)

      expect(store.currentIntervention.value).toBeNull()
    })
  })

  describe('Filtres', () => {
    it('devrait appliquer les filtres par statut', async () => {
      const store = mockInterventionsStore()

      // Charger des interventions
      await store.fetchInterventions()

      // Filtrer par statut "En attente"
      store.setFilters({ status: ['En attente'] })

      expect(store.filteredInterventions.value).toHaveLength(1)
      expect(store.filteredInterventions.value[0].statut_global).toBe('En attente')
    })

    it('devrait appliquer les filtres par date', async () => {
      const store = mockInterventionsStore()

      await store.fetchInterventions()

      // Filtrer par date future (aucun résultat)
      store.setFilters({
        dateFrom: '2025-01-01'
      })

      expect(store.filteredInterventions.value).toHaveLength(0)
    })

    it('devrait nettoyer tous les filtres', async () => {
      const store = mockInterventionsStore()

      store.setFilters({
        status: ['En cours'],
        technicien_id: 1,
        dateFrom: '2024-01-01'
      })

      expect(store.filters.value.status).toEqual(['En cours'])
      expect(store.filters.value.technicien_id).toBe(1)

      store.clearFilters()

      expect(store.filters.value).toEqual({
        technicien_id: null,
        dateFrom: null,
        dateTo: null,
        status: []
      })
    })
  })

  describe('Computed properties', () => {
    it('interventionsByStatus devrait calculer les statistiques', async () => {
      const store = mockInterventionsStore()

      await store.fetchInterventions()

      const stats = store.interventionsByStatus.value

      expect(stats.total).toBe(3)
      expect(stats.enAttente).toBe(1)
      expect(stats.enCours).toBe(1)
      expect(stats.terminees).toBe(1)
      expect(stats.enPause).toBe(0)
      expect(stats.annulees).toBe(0)
    })

    it('filteredInterventions devrait être réactif', async () => {
      const store = mockInterventionsStore()

      await store.fetchInterventions()

      expect(store.filteredInterventions.value).toHaveLength(3)

      store.setFilters({ status: ['En cours'] })

      expect(store.filteredInterventions.value).toHaveLength(1)
      expect(store.filteredInterventions.value[0].statut_global).toBe('En cours')
    })
  })
})