/**
 * Composable pour créer une fonction debouncée
 * Utile pour les champs de recherche et les appels API
 */
export const useDebouncedRef = (fn, delay = 300) => {
  let timeoutId = null

  return (...args) => {
    // Annuler le timeout précédent
    if (timeoutId) {
      clearTimeout(timeoutId)
    }

    // Créer un nouveau timeout
    timeoutId = setTimeout(() => {
      fn.apply(null, args)
    }, delay)
  }
}

/**
 * Composable pour créer une ref debouncée
 * La valeur est mise à jour après le délai spécifié
 */
export const useDebouncedValue = (initialValue, delay = 300) => {
  const immediate = ref(initialValue)
  const debounced = ref(initialValue)

  const debouncedFunction = useDebouncedRef(() => {
    debounced.value = immediate.value
  }, delay)

  watch(immediate, () => {
    debouncedFunction()
  })

  return {
    immediate,
    debounced
  }
}