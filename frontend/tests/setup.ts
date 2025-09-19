/**
 * Configuration globale des tests Vitest
 * Initialise l'environnement de test et les mocks globaux
 */

import { vi } from 'vitest'
import { config } from '@vue/test-utils'

// Mock Nuxt runtime config
global.useRuntimeConfig = vi.fn(() => ({
  public: {
    apiBaseUrl: 'http://localhost/api'
  }
}))

// Mock Nuxt App
global.useNuxtApp = vi.fn(() => ({
  $api: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn()
  },
  $toast: {
    success: vi.fn(),
    error: vi.fn(),
    warning: vi.fn(),
    info: vi.fn()
  }
}))

// Mock Nuxt navigation
global.navigateTo = vi.fn()
global.useRouter = vi.fn(() => ({
  push: vi.fn(),
  replace: vi.fn(),
  go: vi.fn(),
  back: vi.fn(),
  forward: vi.fn()
}))

global.useRoute = vi.fn(() => ({
  params: {},
  query: {},
  path: '/',
  fullPath: '/',
  name: 'index'
}))

// Mock Pinia
global.useAuthStore = vi.fn()
global.useInterventionsStore = vi.fn()
global.useUsersStore = vi.fn()
global.useClientsStore = vi.fn()

// Mock composables Vue
global.ref = vi.fn((value) => ({ value }))
global.reactive = vi.fn((obj) => obj)
global.computed = vi.fn((fn) => ({ value: fn() }))
global.watch = vi.fn()
global.watchEffect = vi.fn()
global.onMounted = vi.fn()
global.onUnmounted = vi.fn()
global.nextTick = vi.fn(() => Promise.resolve())

// Configuration globale pour Vue Test Utils
config.global.mocks = {
  $t: (key: string) => key, // Mock i18n
  $tc: (key: string) => key,
  $te: () => true,
  $d: (value: any) => value,
  $n: (value: any) => value
}

// Mock PrimeVue components
const mockPrimeVueComponent = {
  template: '<div><slot /></div>'
}

config.global.stubs = {
  'DataTable': mockPrimeVueComponent,
  'Column': mockPrimeVueComponent,
  'Button': mockPrimeVueComponent,
  'Card': mockPrimeVueComponent,
  'Dialog': mockPrimeVueComponent,
  'Select': mockPrimeVueComponent,
  'InputText': mockPrimeVueComponent,
  'Textarea': mockPrimeVueComponent,
  'DatePicker': mockPrimeVueComponent,
  'MultiSelect': mockPrimeVueComponent,
  'ProgressSpinner': mockPrimeVueComponent,
  'ProgressBar': mockPrimeVueComponent,
  'Toast': mockPrimeVueComponent,
  'ConfirmDialog': mockPrimeVueComponent,
  'Message': mockPrimeVueComponent,
  'Dropdown': mockPrimeVueComponent,
  'FileUpload': mockPrimeVueComponent,
  'Avatar': mockPrimeVueComponent,
  'Badge': mockPrimeVueComponent,
  'MegaMenu': mockPrimeVueComponent,
  'Breadcrumb': mockPrimeVueComponent
}

// Mock window objects
Object.defineProperty(window, 'matchMedia', {
  writable: true,
  value: vi.fn().mockImplementation(query => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: vi.fn(), // deprecated
    removeListener: vi.fn(), // deprecated
    addEventListener: vi.fn(),
    removeEventListener: vi.fn(),
    dispatchEvent: vi.fn(),
  })),
})

// Mock localStorage
const localStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn(),
}
Object.defineProperty(window, 'localStorage', {
  value: localStorageMock
})

// Mock sessionStorage
const sessionStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn(),
}
Object.defineProperty(window, 'sessionStorage', {
  value: sessionStorageMock
})

// Mock fetch pour les tests d'API
global.fetch = vi.fn()

// Helper pour réinitialiser tous les mocks entre les tests
export function resetAllMocks() {
  vi.clearAllMocks()
  localStorageMock.getItem.mockClear()
  localStorageMock.setItem.mockClear()
  localStorageMock.removeItem.mockClear()
  localStorageMock.clear.mockClear()
  sessionStorageMock.getItem.mockClear()
  sessionStorageMock.setItem.mockClear()
  sessionStorageMock.removeItem.mockClear()
  sessionStorageMock.clear.mockClear()
}

// Helper pour créer des données de test
export function createMockIntervention(overrides = {}) {
  return {
    id: 1,
    numero: 'INT-2024-001',
    titre: 'Intervention Test',
    description: 'Description de test',
    priorite: 'Normale',
    client_nom: 'Client Test',
    type_prestation_nom: 'Branchement Type 1',
    statut_global: 'En attente',
    has_terrassement: true,
    phase_terrassement_statut: 'en_attente',
    phase_branchement_statut: 'en_attente',
    duree_totale_heures: 0,
    cout_total_reel: 0,
    cout_total_estime: 1200,
    date_creation: '2024-01-15T10:00:00.000Z',
    ...overrides
  }
}

export function createMockUser(overrides = {}) {
  return {
    id: 1,
    username: 'testuser',
    email: 'test@example.com',
    role: 'technicien',
    prenom: 'Test',
    nom: 'User',
    is_active: true,
    date_creation: '2024-01-01T00:00:00.000Z',
    ...overrides
  }
}

export function createMockClient(overrides = {}) {
  return {
    id: 1,
    nom: 'Dupont',
    prenom: 'Jean',
    email: 'jean.dupont@example.com',
    telephone: '0123456789',
    adresse: '123 Rue de la Paix',
    ville: 'Paris',
    code_postal: '75001',
    date_creation: '2024-01-01T00:00:00.000Z',
    ...overrides
  }
}

// Mock des stores Pinia avec des données de test
export function createMockAuthStore(overrides = {}) {
  return {
    user: createMockUser({ role: 'admin' }),
    isAuthenticated: true,
    loading: false,
    error: null,
    login: vi.fn(),
    logout: vi.fn(),
    checkAuth: vi.fn(),
    ...overrides
  }
}

export function createMockInterventionsStore(overrides = {}) {
  return {
    interventions: [createMockIntervention()],
    currentIntervention: null,
    loading: false,
    error: null,
    filters: {
      technicien_id: null,
      dateFrom: null,
      dateTo: null,
      status: []
    },
    fetchInterventions: vi.fn(),
    fetchIntervention: vi.fn(),
    createIntervention: vi.fn(),
    updateIntervention: vi.fn(),
    deleteIntervention: vi.fn(),
    setFilters: vi.fn(),
    clearFilters: vi.fn(),
    ...overrides
  }
}

// Configuration des types globaux pour TypeScript
declare global {
  var useRuntimeConfig: any
  var useNuxtApp: any
  var navigateTo: any
  var useRouter: any
  var useRoute: any
  var useAuthStore: any
  var useInterventionsStore: any
  var useUsersStore: any
  var useClientsStore: any
  var ref: any
  var reactive: any
  var computed: any
  var watch: any
  var watchEffect: any
  var onMounted: any
  var onUnmounted: any
  var nextTick: any
}