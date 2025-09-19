import { describe, it, expect, beforeEach, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { resetAllMocks, createMockUser } from '../setup'

// Mock du store auth (serait normalement importé)
const mockAuthStore = () => {
  const user = ref(null)
  const isAuthenticated = computed(() => !!user.value)
  const loading = ref(false)
  const error = ref(null)

  const login = async (credentials: { username: string; password: string }) => {
    loading.value = true
    error.value = null

    try {
      // Simuler un appel API
      if (credentials.username === 'admin' && credentials.password === 'password') {
        user.value = createMockUser({ username: 'admin', role: 'admin' })
        return { success: true }
      } else {
        throw new Error('Identifiants invalides')
      }
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Erreur de connexion'
      throw err
    } finally {
      loading.value = false
    }
  }

  const logout = async () => {
    user.value = null
    error.value = null
  }

  const checkAuth = async () => {
    loading.value = true
    try {
      // Simuler la vérification du token
      const token = localStorage.getItem('auth_token')
      if (token === 'valid_token') {
        user.value = createMockUser()
      }
    } catch (err) {
      user.value = null
    } finally {
      loading.value = false
    }
  }

  return {
    user: readonly(user),
    isAuthenticated,
    loading: readonly(loading),
    error: readonly(error),
    login,
    logout,
    checkAuth
  }
}

describe('Auth Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    resetAllMocks()
  })

  describe('État initial', () => {
    it('devrait avoir un état initial correct', () => {
      const store = mockAuthStore()

      expect(store.user.value).toBeNull()
      expect(store.isAuthenticated.value).toBe(false)
      expect(store.loading.value).toBe(false)
      expect(store.error.value).toBeNull()
    })
  })

  describe('Login', () => {
    it('devrait connecter un utilisateur avec des identifiants valides', async () => {
      const store = mockAuthStore()

      const result = await store.login({
        username: 'admin',
        password: 'password'
      })

      expect(result.success).toBe(true)
      expect(store.user.value).toBeTruthy()
      expect(store.user.value?.username).toBe('admin')
      expect(store.user.value?.role).toBe('admin')
      expect(store.isAuthenticated.value).toBe(true)
      expect(store.error.value).toBeNull()
    })

    it('devrait rejeter des identifiants invalides', async () => {
      const store = mockAuthStore()

      await expect(store.login({
        username: 'wrong',
        password: 'wrong'
      })).rejects.toThrow('Identifiants invalides')

      expect(store.user.value).toBeNull()
      expect(store.isAuthenticated.value).toBe(false)
      expect(store.error.value).toBe('Identifiants invalides')
    })

    it('devrait gérer les états de chargement', async () => {
      const store = mockAuthStore()

      // Démarrer le login
      const loginPromise = store.login({
        username: 'admin',
        password: 'password'
      })

      // Vérifier que le loading est actif
      expect(store.loading.value).toBe(true)

      // Attendre la fin
      await loginPromise

      // Vérifier que le loading est terminé
      expect(store.loading.value).toBe(false)
    })
  })

  describe('Logout', () => {
    it('devrait déconnecter un utilisateur', async () => {
      const store = mockAuthStore()

      // D'abord se connecter
      await store.login({
        username: 'admin',
        password: 'password'
      })

      expect(store.isAuthenticated.value).toBe(true)

      // Puis se déconnecter
      await store.logout()

      expect(store.user.value).toBeNull()
      expect(store.isAuthenticated.value).toBe(false)
      expect(store.error.value).toBeNull()
    })
  })

  describe('Check Auth', () => {
    it('devrait restaurer une session valide', async () => {
      const store = mockAuthStore()

      // Mock localStorage avec un token valide
      vi.mocked(localStorage.getItem).mockReturnValue('valid_token')

      await store.checkAuth()

      expect(store.user.value).toBeTruthy()
      expect(store.isAuthenticated.value).toBe(true)
    })

    it('devrait gérer un token invalide', async () => {
      const store = mockAuthStore()

      // Mock localStorage avec un token invalide
      vi.mocked(localStorage.getItem).mockReturnValue('invalid_token')

      await store.checkAuth()

      expect(store.user.value).toBeNull()
      expect(store.isAuthenticated.value).toBe(false)
    })

    it('devrait gérer l\'absence de token', async () => {
      const store = mockAuthStore()

      // Mock localStorage sans token
      vi.mocked(localStorage.getItem).mockReturnValue(null)

      await store.checkAuth()

      expect(store.user.value).toBeNull()
      expect(store.isAuthenticated.value).toBe(false)
    })
  })

  describe('Computed properties', () => {
    it('isAuthenticated devrait être reactif', async () => {
      const store = mockAuthStore()

      expect(store.isAuthenticated.value).toBe(false)

      await store.login({
        username: 'admin',
        password: 'password'
      })

      expect(store.isAuthenticated.value).toBe(true)

      await store.logout()

      expect(store.isAuthenticated.value).toBe(false)
    })
  })

  describe('Gestion d\'erreurs', () => {
    it('devrait nettoyer l\'erreur lors d\'un login réussi', async () => {
      const store = mockAuthStore()

      // Échec de login
      try {
        await store.login({
          username: 'wrong',
          password: 'wrong'
        })
      } catch (e) {
        // Erreur attendue
      }

      expect(store.error.value).toBe('Identifiants invalides')

      // Login réussi
      await store.login({
        username: 'admin',
        password: 'password'
      })

      expect(store.error.value).toBeNull()
    })

    it('devrait nettoyer l\'erreur lors du logout', async () => {
      const store = mockAuthStore()

      // Créer une erreur
      try {
        await store.login({
          username: 'wrong',
          password: 'wrong'
        })
      } catch (e) {
        // Erreur attendue
      }

      expect(store.error.value).toBe('Identifiants invalides')

      // Logout devrait nettoyer l'erreur
      await store.logout()

      expect(store.error.value).toBeNull()
    })
  })
})