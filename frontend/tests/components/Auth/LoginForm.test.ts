import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import { resetAllMocks, createMockAuthStore } from '../../setup'

// Mock du composant LoginForm
const MockLoginForm = {
  template: `
    <div class="login-form">
      <form @submit.prevent="handleLogin" data-testid="login-form">
        <div>
          <label for="email">Email</label>
          <input
            id="email"
            v-model="form.email"
            type="email"
            required
            data-testid="email-input"
            :class="{ 'error': !!errors.email }"
          />
          <p v-if="errors.email" class="error-message" data-testid="email-error">
            {{ errors.email }}
          </p>
        </div>

        <div>
          <label for="password">Mot de passe</label>
          <input
            id="password"
            v-model="form.password"
            type="password"
            required
            data-testid="password-input"
            :class="{ 'error': !!errors.password }"
          />
          <p v-if="errors.password" class="error-message" data-testid="password-error">
            {{ errors.password }}
          </p>
        </div>

        <button
          type="submit"
          :disabled="loading"
          data-testid="submit-button"
        >
          {{ loading ? 'Connexion...' : 'Se connecter' }}
        </button>

        <div v-if="globalError" class="global-error" data-testid="global-error">
          {{ globalError }}
        </div>
      </form>
    </div>
  `,
  props: {
    redirectTo: {
      type: String,
      default: '/'
    }
  },
  emits: ['login-success', 'login-error'],
  setup(props, { emit }) {
    const authStore = createMockAuthStore()

    const form = ref({
      email: '',
      password: ''
    })

    const errors = ref({
      email: '',
      password: ''
    })

    const loading = ref(false)
    const globalError = ref('')

    const validateForm = () => {
      errors.value = {
        email: '',
        password: ''
      }

      let isValid = true

      if (!form.value.email) {
        errors.value.email = 'L\'email est requis'
        isValid = false
      } else if (!/\S+@\S+\.\S+/.test(form.value.email)) {
        errors.value.email = 'Format d\'email invalide'
        isValid = false
      }

      if (!form.value.password) {
        errors.value.password = 'Le mot de passe est requis'
        isValid = false
      } else if (form.value.password.length < 6) {
        errors.value.password = 'Le mot de passe doit contenir au moins 6 caractères'
        isValid = false
      }

      return isValid
    }

    const handleLogin = async () => {
      globalError.value = ''

      if (!validateForm()) {
        return
      }

      loading.value = true

      try {
        await authStore.login({
          username: form.value.email,
          password: form.value.password
        })

        emit('login-success', authStore.user.value)

        // Redirection simulée
        if (props.redirectTo) {
          // navigateTo(props.redirectTo) // Mock sera appelé
        }
      } catch (error) {
        const errorMessage = error instanceof Error ? error.message : 'Erreur de connexion'
        globalError.value = errorMessage
        emit('login-error', errorMessage)
      } finally {
        loading.value = false
      }
    }

    return {
      form,
      errors,
      loading,
      globalError,
      handleLogin,
      authStore
    }
  }
}

describe('LoginForm', () => {
  beforeEach(() => {
    resetAllMocks()
  })

  describe('Rendu initial', () => {
    it('devrait afficher le formulaire de connexion', () => {
      const wrapper = mount(MockLoginForm)

      expect(wrapper.find('[data-testid="login-form"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="email-input"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="password-input"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="submit-button"]').exists()).toBe(true)
    })

    it('devrait avoir des champs vides initialement', () => {
      const wrapper = mount(MockLoginForm)

      const emailInput = wrapper.find('[data-testid="email-input"]')
      const passwordInput = wrapper.find('[data-testid="password-input"]')

      expect(emailInput.element.value).toBe('')
      expect(passwordInput.element.value).toBe('')
    })

    it('devrait afficher le texte par défaut du bouton', () => {
      const wrapper = mount(MockLoginForm)

      const submitButton = wrapper.find('[data-testid="submit-button"]')
      expect(submitButton.text()).toBe('Se connecter')
      expect(submitButton.attributes('disabled')).toBeUndefined()
    })
  })

  describe('Validation des champs', () => {
    it('devrait afficher une erreur pour un email vide', async () => {
      const wrapper = mount(MockLoginForm)

      await wrapper.find('[data-testid="login-form"]').trigger('submit')
      await nextTick()

      expect(wrapper.find('[data-testid="email-error"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="email-error"]').text()).toBe('L\'email est requis')
    })

    it('devrait afficher une erreur pour un email invalide', async () => {
      const wrapper = mount(MockLoginForm)

      await wrapper.find('[data-testid="email-input"]').setValue('email-invalide')
      await wrapper.find('[data-testid="login-form"]').trigger('submit')
      await nextTick()

      expect(wrapper.find('[data-testid="email-error"]').text()).toBe('Format d\'email invalide')
    })

    it('devrait afficher une erreur pour un mot de passe vide', async () => {
      const wrapper = mount(MockLoginForm)

      await wrapper.find('[data-testid="email-input"]').setValue('test@example.com')
      await wrapper.find('[data-testid="login-form"]').trigger('submit')
      await nextTick()

      expect(wrapper.find('[data-testid="password-error"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="password-error"]').text()).toBe('Le mot de passe est requis')
    })

    it('devrait afficher une erreur pour un mot de passe trop court', async () => {
      const wrapper = mount(MockLoginForm)

      await wrapper.find('[data-testid="email-input"]').setValue('test@example.com')
      await wrapper.find('[data-testid="password-input"]').setValue('123')
      await wrapper.find('[data-testid="login-form"]').trigger('submit')
      await nextTick()

      expect(wrapper.find('[data-testid="password-error"]').text())
        .toBe('Le mot de passe doit contenir au moins 6 caractères')
    })

    it('ne devrait pas afficher d\'erreurs pour des données valides', async () => {
      const wrapper = mount(MockLoginForm)

      await wrapper.find('[data-testid="email-input"]').setValue('admin')
      await wrapper.find('[data-testid="password-input"]').setValue('password')

      // Attendre que la soumission se termine
      await wrapper.find('[data-testid="login-form"]').trigger('submit')
      await nextTick()
      await nextTick() // Attendre l'async

      expect(wrapper.find('[data-testid="email-error"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="password-error"]').exists()).toBe(false)
    })
  })

  describe('Soumission du formulaire', () => {
    it('devrait appeler le store auth lors de la soumission', async () => {
      const wrapper = mount(MockLoginForm)

      await wrapper.find('[data-testid="email-input"]').setValue('admin')
      await wrapper.find('[data-testid="password-input"]').setValue('password')
      await wrapper.find('[data-testid="login-form"]').trigger('submit')

      // Le mock store devrait être appelé
      await nextTick()
      expect(wrapper.vm.authStore.login).toHaveBeenCalledWith({
        username: 'admin',
        password: 'password'
      })
    })

    it('devrait émettre login-success en cas de succès', async () => {
      const wrapper = mount(MockLoginForm)

      await wrapper.find('[data-testid="email-input"]').setValue('admin')
      await wrapper.find('[data-testid="password-input"]').setValue('password')
      await wrapper.find('[data-testid="login-form"]').trigger('submit')

      await nextTick()
      await nextTick()

      expect(wrapper.emitted('login-success')).toBeTruthy()
      expect(wrapper.emitted('login-success')?.[0]).toBeTruthy()
    })

    it('devrait afficher une erreur en cas d\'échec', async () => {
      const wrapper = mount(MockLoginForm)

      // Mock d'un échec de connexion
      wrapper.vm.authStore.login.mockRejectedValue(new Error('Identifiants invalides'))

      await wrapper.find('[data-testid="email-input"]').setValue('wrong@example.com')
      await wrapper.find('[data-testid="password-input"]').setValue('wrongpassword')
      await wrapper.find('[data-testid="login-form"]').trigger('submit')

      await nextTick()
      await nextTick()

      expect(wrapper.find('[data-testid="global-error"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="global-error"]').text()).toBe('Identifiants invalides')
      expect(wrapper.emitted('login-error')).toBeTruthy()
    })

    it('devrait gérer les états de chargement', async () => {
      const wrapper = mount(MockLoginForm)

      await wrapper.find('[data-testid="email-input"]').setValue('admin')
      await wrapper.find('[data-testid="password-input"]').setValue('password')

      // Déclencher la soumission
      const submitPromise = wrapper.find('[data-testid="login-form"]').trigger('submit')

      await nextTick()

      // Pendant le chargement
      expect(wrapper.find('[data-testid="submit-button"]').text()).toBe('Connexion...')
      expect(wrapper.find('[data-testid="submit-button"]').attributes('disabled')).toBeDefined()

      // Attendre la fin
      await submitPromise
      await nextTick()
      await nextTick()

      // Après le chargement
      expect(wrapper.find('[data-testid="submit-button"]').text()).toBe('Se connecter')
      expect(wrapper.find('[data-testid="submit-button"]').attributes('disabled')).toBeUndefined()
    })
  })

  describe('Props et événements', () => {
    it('devrait accepter une prop redirectTo', () => {
      const wrapper = mount(MockLoginForm, {
        props: {
          redirectTo: '/dashboard'
        }
      })

      expect(wrapper.props('redirectTo')).toBe('/dashboard')
    })

    it('devrait émettre les événements appropriés', async () => {
      const wrapper = mount(MockLoginForm)

      await wrapper.find('[data-testid="email-input"]').setValue('admin')
      await wrapper.find('[data-testid="password-input"]').setValue('password')
      await wrapper.find('[data-testid="login-form"]').trigger('submit')

      await nextTick()
      await nextTick()

      // Vérifier que les événements sont émis
      expect(wrapper.emitted()).toHaveProperty('login-success')
    })
  })

  describe('Nettoyage des erreurs', () => {
    it('devrait nettoyer l\'erreur globale lors d\'une nouvelle soumission', async () => {
      const wrapper = mount(MockLoginForm)

      // Provoquer une erreur
      wrapper.vm.authStore.login.mockRejectedValue(new Error('Erreur test'))

      await wrapper.find('[data-testid="email-input"]').setValue('test@example.com')
      await wrapper.find('[data-testid="password-input"]').setValue('password')
      await wrapper.find('[data-testid="login-form"]').trigger('submit')

      await nextTick()
      await nextTick()

      expect(wrapper.find('[data-testid="global-error"]').exists()).toBe(true)

      // Nouvelle soumission avec succès
      wrapper.vm.authStore.login.mockResolvedValue({ success: true })

      await wrapper.find('[data-testid="login-form"]').trigger('submit')
      await nextTick()

      // L'erreur globale devrait être nettoyée avant la nouvelle tentative
      expect(wrapper.vm.globalError).toBe('')
    })
  })
})