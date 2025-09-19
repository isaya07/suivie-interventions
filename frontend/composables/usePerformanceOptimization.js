/**
 * Composable pour l'optimisation des performances
 *
 * Fournit des outils pour :
 * - Mise en cache intelligente des données
 * - Lazy loading des composants
 * - Optimisation des requêtes API
 * - Gestion de la mémoire
 */

import { ref, computed, watch, onUnmounted } from 'vue'

// Cache global pour les données
const globalCache = new Map()
const cacheExpiry = new Map()

// Métrique de performance
const performanceMetrics = ref({
  cacheHits: 0,
  cacheMisses: 0,
  apiCalls: 0,
  averageResponseTime: 0,
  memoryUsage: 0,
  lastUpdate: new Date()
})

/**
 * Hook principal d'optimisation des performances
 */
export const usePerformanceOptimization = () => {
  // Refs réactives
  const isOptimizing = ref(false)
  const cacheStats = ref({
    size: 0,
    hitRate: 0,
    totalRequests: 0
  })

  /**
   * Cache intelligent avec TTL (Time To Live)
   */
  const useSmartCache = (key, fetchFunction, ttlMinutes = 5) => {
    const data = ref(null)
    const loading = ref(false)
    const error = ref(null)
    const lastFetch = ref(null)

    const ttlMs = ttlMinutes * 60 * 1000

    const fetchData = async (forceRefresh = false) => {
      const now = Date.now()
      const cacheKey = `cache_${key}`
      const expiryKey = `expiry_${key}`

      // Vérifier le cache si pas de refresh forcé
      if (!forceRefresh && globalCache.has(cacheKey)) {
        const expiry = cacheExpiry.get(expiryKey)
        if (expiry && now < expiry) {
          // Cache hit
          data.value = globalCache.get(cacheKey)
          performanceMetrics.value.cacheHits++
          updateCacheStats()
          return data.value
        }
      }

      // Cache miss - fetch fresh data
      loading.value = true
      error.value = null
      performanceMetrics.value.cacheMisses++
      performanceMetrics.value.apiCalls++

      const startTime = performance.now()

      try {
        const result = await fetchFunction()
        const endTime = performance.now()

        // Mettre à jour les métriques de réponse
        const responseTime = endTime - startTime
        performanceMetrics.value.averageResponseTime =
          (performanceMetrics.value.averageResponseTime + responseTime) / 2

        // Stocker en cache
        globalCache.set(cacheKey, result)
        cacheExpiry.set(expiryKey, now + ttlMs)

        data.value = result
        lastFetch.value = new Date()
        updateCacheStats()

        return result
      } catch (err) {
        error.value = err
        console.error(`Erreur lors du fetch de ${key}:`, err)
        throw err
      } finally {
        loading.value = false
      }
    }

    // Auto-fetch au montage si pas en cache
    if (!globalCache.has(`cache_${key}`)) {
      fetchData()
    } else {
      data.value = globalCache.get(`cache_${key}`)
      performanceMetrics.value.cacheHits++
    }

    return {
      data: readonly(data),
      loading: readonly(loading),
      error: readonly(error),
      refetch: fetchData,
      lastFetch: readonly(lastFetch)
    }
  }

  /**
   * Optimisation des requêtes avec debouncing
   */
  const useDebouncedApi = (apiFunction, delay = 300) => {
    const pendingRequests = ref(new Map())
    let timeoutId = null

    const debouncedCall = (key, ...args) => {
      return new Promise((resolve, reject) => {
        // Annuler la requête précédente
        if (timeoutId) {
          clearTimeout(timeoutId)
        }

        // Ajouter à la queue
        pendingRequests.value.set(key, { resolve, reject, args })

        // Programmer l'exécution
        timeoutId = setTimeout(async () => {
          const batch = Array.from(pendingRequests.value.entries())
          pendingRequests.value.clear()

          // Exécuter toutes les requêtes en parallèle
          const promises = batch.map(async ([reqKey, { resolve, reject, args }]) => {
            try {
              const result = await apiFunction(...args)
              resolve(result)
            } catch (error) {
              reject(error)
            }
          })

          await Promise.allSettled(promises)
        }, delay)
      })
    }

    return debouncedCall
  }

  /**
   * Lazy loading intelligent des composants
   */
  const useLazyComponent = (importFunction) => {
    const component = ref(null)
    const loading = ref(false)
    const error = ref(null)

    const loadComponent = async () => {
      if (component.value) return component.value

      loading.value = true
      error.value = null

      try {
        const module = await importFunction()
        component.value = module.default || module
        return component.value
      } catch (err) {
        error.value = err
        console.error('Erreur lors du lazy loading:', err)
        throw err
      } finally {
        loading.value = false
      }
    }

    return {
      component: readonly(component),
      loading: readonly(loading),
      error: readonly(error),
      load: loadComponent
    }
  }

  /**
   * Optimisation de la mémoire
   */
  const useMemoryOptimization = () => {
    const cleanupFunctions = []

    const addCleanup = (cleanupFn) => {
      cleanupFunctions.push(cleanupFn)
    }

    const cleanup = () => {
      cleanupFunctions.forEach(fn => {
        try {
          fn()
        } catch (error) {
          console.warn('Erreur lors du cleanup:', error)
        }
      })
      cleanupFunctions.length = 0
    }

    // Auto-cleanup lors du démontage
    onUnmounted(cleanup)

    return {
      addCleanup,
      cleanup,
      forceGarbageCollection: () => {
        // Nettoyage du cache expiré
        const now = Date.now()
        for (const [key, expiry] of cacheExpiry.entries()) {
          if (now > expiry) {
            const cacheKey = key.replace('expiry_', 'cache_')
            globalCache.delete(cacheKey)
            cacheExpiry.delete(key)
          }
        }

        // Mise à jour des métriques mémoire
        updateMemoryMetrics()
      }
    }
  }

  /**
   * Pagination optimisée
   */
  const useOptimizedPagination = (items, pageSize = 20) => {
    const currentPage = ref(1)
    const searchTerm = ref('')
    const sortField = ref('')
    const sortOrder = ref(1)

    // Filtrage et tri optimisés
    const filteredItems = computed(() => {
      let filtered = items.value || []

      // Filtrage
      if (searchTerm.value) {
        const term = searchTerm.value.toLowerCase()
        filtered = filtered.filter(item =>
          Object.values(item).some(value =>
            String(value).toLowerCase().includes(term)
          )
        )
      }

      // Tri
      if (sortField.value) {
        filtered.sort((a, b) => {
          const aVal = a[sortField.value]
          const bVal = b[sortField.value]

          if (aVal < bVal) return -1 * sortOrder.value
          if (aVal > bVal) return 1 * sortOrder.value
          return 0
        })
      }

      return filtered
    })

    // Pagination
    const paginatedItems = computed(() => {
      const start = (currentPage.value - 1) * pageSize
      const end = start + pageSize
      return filteredItems.value.slice(start, end)
    })

    const totalPages = computed(() =>
      Math.ceil(filteredItems.value.length / pageSize)
    )

    return {
      currentPage,
      searchTerm,
      sortField,
      sortOrder,
      paginatedItems: readonly(paginatedItems),
      totalPages: readonly(totalPages),
      filteredItems: readonly(filteredItems),
      setPage: (page) => currentPage.value = page,
      setSort: (field, order = 1) => {
        sortField.value = field
        sortOrder.value = order
      }
    }
  }

  /**
   * Préchargement intelligent
   */
  const usePreloader = () => {
    const preloadQueue = ref([])
    const preloadedData = ref(new Map())

    const preload = async (key, fetchFunction, priority = 'low') => {
      if (preloadedData.value.has(key)) {
        return preloadedData.value.get(key)
      }

      const request = {
        key,
        fetchFunction,
        priority,
        promise: null
      }

      // Exécuter immédiatement si haute priorité
      if (priority === 'high') {
        request.promise = fetchFunction()
        const result = await request.promise
        preloadedData.value.set(key, result)
        return result
      }

      // Ajouter à la queue pour priorité basse/moyenne
      preloadQueue.value.push(request)

      // Traiter la queue avec requestIdleCallback si disponible
      if ('requestIdleCallback' in window) {
        requestIdleCallback(processPreloadQueue)
      } else {
        setTimeout(processPreloadQueue, 0)
      }
    }

    const processPreloadQueue = async () => {
      if (preloadQueue.value.length === 0) return

      const request = preloadQueue.value.shift()
      try {
        const result = await request.fetchFunction()
        preloadedData.value.set(request.key, result)
      } catch (error) {
        console.warn(`Erreur lors du préchargement de ${request.key}:`, error)
      }

      // Continuer le traitement
      if (preloadQueue.value.length > 0) {
        requestIdleCallback(processPreloadQueue)
      }
    }

    return {
      preload,
      getPreloaded: (key) => preloadedData.value.get(key),
      clearPreloaded: (key) => preloadedData.value.delete(key)
    }
  }

  // Utilitaires internes
  const updateCacheStats = () => {
    cacheStats.value = {
      size: globalCache.size,
      hitRate: performanceMetrics.value.cacheHits /
               (performanceMetrics.value.cacheHits + performanceMetrics.value.cacheMisses) * 100,
      totalRequests: performanceMetrics.value.cacheHits + performanceMetrics.value.cacheMisses
    }
  }

  const updateMemoryMetrics = () => {
    if ('memory' in performance) {
      performanceMetrics.value.memoryUsage = performance.memory.usedJSHeapSize
    }
    performanceMetrics.value.lastUpdate = new Date()
  }

  // Monitoring en temps réel
  const startPerformanceMonitoring = () => {
    const interval = setInterval(() => {
      updateMemoryMetrics()
    }, 30000) // Toutes les 30 secondes

    onUnmounted(() => {
      clearInterval(interval)
    })
  }

  // Métriques de performance
  const getPerformanceReport = () => {
    updateMemoryMetrics()
    return {
      cache: cacheStats.value,
      api: {
        totalCalls: performanceMetrics.value.apiCalls,
        averageResponseTime: Math.round(performanceMetrics.value.averageResponseTime),
        cacheHitRate: cacheStats.value.hitRate
      },
      memory: {
        usage: performanceMetrics.value.memoryUsage,
        cacheSize: globalCache.size,
        lastUpdate: performanceMetrics.value.lastUpdate
      }
    }
  }

  return {
    // Cache intelligent
    useSmartCache,

    // Optimisations API
    useDebouncedApi,

    // Lazy loading
    useLazyComponent,

    // Gestion mémoire
    useMemoryOptimization,

    // Pagination
    useOptimizedPagination,

    // Préchargement
    usePreloader,

    // Monitoring
    startPerformanceMonitoring,
    getPerformanceReport,

    // État
    isOptimizing: readonly(isOptimizing),
    cacheStats: readonly(cacheStats),
    performanceMetrics: readonly(performanceMetrics)
  }
}

/**
 * Optimisations globales pour toute l'application
 */
export const initGlobalOptimizations = () => {
  // Préchargement des routes critiques
  const preloadCriticalRoutes = () => {
    const criticalRoutes = [
      () => import('~/pages/dashboard.vue'),
      () => import('~/pages/interventions/electrique/index.vue'),
      () => import('~/pages/planning/index.vue')
    ]

    criticalRoutes.forEach((importFn, index) => {
      setTimeout(() => {
        importFn().catch(err =>
          console.warn('Erreur lors du préchargement de route:', err)
        )
      }, index * 100)
    })
  }

  // Optimisation des images
  const optimizeImages = () => {
    const images = document.querySelectorAll('img[data-src]')

    if ('IntersectionObserver' in window) {
      const imageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const img = entry.target
            img.src = img.dataset.src
            img.removeAttribute('data-src')
            imageObserver.unobserve(img)
          }
        })
      })

      images.forEach(img => imageObserver.observe(img))
    }
  }

  // Nettoyage automatique du cache
  const startCacheCleanup = () => {
    setInterval(() => {
      const now = Date.now()
      let cleaned = 0

      for (const [key, expiry] of cacheExpiry.entries()) {
        if (now > expiry) {
          const cacheKey = key.replace('expiry_', 'cache_')
          globalCache.delete(cacheKey)
          cacheExpiry.delete(key)
          cleaned++
        }
      }

      if (cleaned > 0) {
        console.log(`🧹 Cache cleanup: ${cleaned} entrées expirées supprimées`)
      }
    }, 300000) // Toutes les 5 minutes
  }

  // Initialiser les optimisations
  if (process.client) {
    preloadCriticalRoutes()
    optimizeImages()
    startCacheCleanup()
  }
}

// Hook spécialisé pour les interventions
export const useInterventionsOptimized = () => {
  const { useSmartCache, useOptimizedPagination } = usePerformanceOptimization()

  // Cache des interventions avec TTL de 2 minutes
  const {
    data: interventions,
    loading,
    error,
    refetch
  } = useSmartCache(
    'interventions_electriques',
    async () => {
      // Simulation d'appel API - à remplacer par vraie API
      const response = await fetch('/api/intervention_electrique.php')
      if (!response.ok) throw new Error('Erreur de chargement')
      return response.json()
    },
    2
  )

  // Pagination optimisée
  const pagination = useOptimizedPagination(interventions, 20)

  return {
    interventions,
    loading,
    error,
    refetch,
    ...pagination
  }
}