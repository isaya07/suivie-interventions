<template>
  <div class="p-8">
    <h1 class="text-2xl font-bold mb-4">Test Configuration API</h1>
    <div class="space-y-4">
      <div>
        <strong>API Base URL:</strong> {{ apiUrl }}
      </div>
      <div>
        <strong>Full Auth URL:</strong> {{ fullAuthUrl }}
      </div>
      <button
        @click="testApi"
        class="bg-blue-500 text-white px-4 py-2 rounded"
      >
        Tester l'API
      </button>
      <div v-if="testResult" class="mt-4 p-4 border rounded">
        <pre>{{ JSON.stringify(testResult, null, 2) }}</pre>
      </div>
    </div>
  </div>
</template>
<script setup>
const config = useRuntimeConfig()
const { $api } = useNuxtApp()
const apiUrl = config.public.apiBaseUrl
const fullAuthUrl = apiUrl + '/auth.php?action=test'
const testResult = ref(null)
const testApi = async () => {
  try {
    testResult.value = await $api('/auth.php?action=test')
  } catch (error) {
    testResult.value = { error: error.message }
  }
}
</script>