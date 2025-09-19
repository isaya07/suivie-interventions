import { describe, it, expect, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { resetAllMocks, createMockIntervention } from '../../setup'
import type { InterventionListItem } from '../../../types'

// Mock du composant InterventionCard
const MockInterventionCard = {
  template: `
    <div class="intervention-card" data-testid="intervention-card">
      <div class="intervention-header">
        <h3 class="intervention-title" data-testid="intervention-title">
          {{ intervention.numero }} - {{ intervention.titre }}
        </h3>
        <span
          class="intervention-status"
          :class="statusClass"
          data-testid="intervention-status"
        >
          {{ intervention.statut_global }}
        </span>
      </div>

      <div class="intervention-body">
        <div class="intervention-info">
          <p class="client-name" data-testid="client-name">
            <strong>Client:</strong> {{ intervention.client_nom }}
          </p>
          <p class="prestation-type" data-testid="prestation-type">
            <strong>Type:</strong> {{ intervention.type_prestation_nom }}
          </p>
          <p class="priority" data-testid="priority">
            <strong>Priorité:</strong>
            <span :class="priorityClass">{{ intervention.priorite }}</span>
          </p>
        </div>

        <div class="intervention-phases" data-testid="intervention-phases">
          <div v-if="intervention.has_terrassement" class="phase-info">
            <span>Terrassement:</span>
            <span
              class="phase-status"
              :class="getPhaseStatusClass(intervention.phase_terrassement_statut)"
              data-testid="terrassement-status"
            >
              {{ getPhaseStatusLabel(intervention.phase_terrassement_statut) }}
            </span>
          </div>
          <div class="phase-info">
            <span>Branchement:</span>
            <span
              class="phase-status"
              :class="getPhaseStatusClass(intervention.phase_branchement_statut)"
              data-testid="branchement-status"
            >
              {{ getPhaseStatusLabel(intervention.phase_branchement_statut) }}
            </span>
          </div>
        </div>

        <div class="intervention-dates" data-testid="intervention-dates">
          <p class="creation-date">
            <strong>Créé le:</strong> {{ formatDate(intervention.date_creation) }}
          </p>
          <p v-if="intervention.date_terrassement_prevue" class="planned-date">
            <strong>Terrassement prévu:</strong> {{ formatDate(intervention.date_terrassement_prevue) }}
          </p>
          <p v-if="intervention.date_branchement_prevue" class="planned-date">
            <strong>Branchement prévu:</strong> {{ formatDate(intervention.date_branchement_prevue) }}
          </p>
        </div>

        <div class="intervention-costs" data-testid="intervention-costs">
          <div class="cost-info">
            <span>Durée totale:</span>
            <span data-testid="total-duration">{{ intervention.duree_totale_heures }}h</span>
          </div>
          <div class="cost-info">
            <span>Coût estimé:</span>
            <span data-testid="estimated-cost">{{ formatCurrency(intervention.cout_total_estime) }}</span>
          </div>
          <div class="cost-info">
            <span>Coût réel:</span>
            <span data-testid="real-cost">{{ formatCurrency(intervention.cout_total_reel) }}</span>
          </div>
        </div>
      </div>

      <div class="intervention-actions" data-testid="intervention-actions">
        <button
          @click="$emit('view', intervention.id)"
          class="btn-view"
          data-testid="view-button"
        >
          Voir détails
        </button>
        <button
          @click="$emit('edit', intervention.id)"
          class="btn-edit"
          data-testid="edit-button"
          v-if="canEdit"
        >
          Modifier
        </button>
        <button
          @click="$emit('delete', intervention.id)"
          class="btn-delete"
          data-testid="delete-button"
          v-if="canDelete"
        >
          Supprimer
        </button>
      </div>
    </div>
  `,
  props: {
    intervention: {
      type: Object as () => InterventionListItem,
      required: true
    },
    canEdit: {
      type: Boolean,
      default: true
    },
    canDelete: {
      type: Boolean,
      default: false
    },
    showTechniciens: {
      type: Boolean,
      default: true
    }
  },
  emits: ['view', 'edit', 'delete'],
  setup(props) {
    const statusClass = computed(() => {
      const status = props.intervention.statut_global.toLowerCase().replace(' ', '-')
      return `status-${status}`
    })

    const priorityClass = computed(() => {
      const priority = props.intervention.priorite.toLowerCase()
      return `priority-${priority}`
    })

    const getPhaseStatusClass = (status: string) => {
      return `phase-${status.replace('_', '-')}`
    }

    const getPhaseStatusLabel = (status: string) => {
      const labels = {
        'en_attente': 'En attente',
        'en_cours': 'En cours',
        'terminee': 'Terminée',
        'annulee': 'Annulée'
      }
      return labels[status as keyof typeof labels] || status
    }

    const formatDate = (dateString: string) => {
      if (!dateString) return '-'
      return new Date(dateString).toLocaleDateString('fr-FR')
    }

    const formatCurrency = (amount: number) => {
      return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
      }).format(amount)
    }

    return {
      statusClass,
      priorityClass,
      getPhaseStatusClass,
      getPhaseStatusLabel,
      formatDate,
      formatCurrency
    }
  }
}

describe('InterventionCard', () => {
  let mockIntervention: InterventionListItem

  beforeEach(() => {
    resetAllMocks()
    mockIntervention = createMockIntervention({
      id: 1,
      numero: 'INT-2024-001',
      titre: 'Installation électrique',
      client_nom: 'Dupont Jean',
      type_prestation_nom: 'Branchement Type 1',
      priorite: 'Haute',
      statut_global: 'En cours',
      has_terrassement: true,
      phase_terrassement_statut: 'terminee',
      phase_branchement_statut: 'en_cours',
      duree_totale_heures: 8.5,
      cout_total_estime: 1200,
      cout_total_reel: 950,
      date_creation: '2024-01-15T10:00:00.000Z',
      date_terrassement_prevue: '2024-01-20T08:00:00.000Z',
      date_branchement_prevue: '2024-01-22T14:00:00.000Z'
    })
  })

  describe('Rendu de base', () => {
    it('devrait afficher les informations de base de l\'intervention', () => {
      const wrapper = mount(MockInterventionCard, {
        props: { intervention: mockIntervention }
      })

      expect(wrapper.find('[data-testid="intervention-title"]').text())
        .toBe('INT-2024-001 - Installation électrique')
      expect(wrapper.find('[data-testid="intervention-status"]').text())
        .toBe('En cours')
      expect(wrapper.find('[data-testid="client-name"]').text())
        .toBe('Client: Dupont Jean')
      expect(wrapper.find('[data-testid="prestation-type"]').text())
        .toBe('Type: Branchement Type 1')
    })

    it('devrait afficher la priorité avec la classe CSS appropriée', () => {
      const wrapper = mount(MockInterventionCard, {
        props: { intervention: mockIntervention }
      })

      const priorityElement = wrapper.find('[data-testid="priority"] span')
      expect(priorityElement.text()).toBe('Haute')
      expect(priorityElement.classes()).toContain('priority-haute')
    })

    it('devrait afficher le statut avec la classe CSS appropriée', () => {
      const wrapper = mount(MockInterventionCard, {
        props: { intervention: mockIntervention }
      })

      const statusElement = wrapper.find('[data-testid="intervention-status"]')
      expect(statusElement.classes()).toContain('status-en-cours')
    })
  })

  describe('Affichage des phases', () => {
    it('devrait afficher les phases de terrassement et branchement si has_terrassement = true', () => {
      const wrapper = mount(MockInterventionCard, {
        props: { intervention: mockIntervention }
      })

      const phasesSection = wrapper.find('[data-testid="intervention-phases"]')
      expect(phasesSection.exists()).toBe(true)

      const terrassementStatus = wrapper.find('[data-testid="terrassement-status"]')
      expect(terrassementStatus.exists()).toBe(true)
      expect(terrassementStatus.text()).toBe('Terminée')
      expect(terrassementStatus.classes()).toContain('phase-terminee')

      const branchementStatus = wrapper.find('[data-testid="branchement-status"]')
      expect(branchementStatus.exists()).toBe(true)
      expect(branchementStatus.text()).toBe('En cours')
      expect(branchementStatus.classes()).toContain('phase-en-cours')
    })

    it('ne devrait pas afficher la phase de terrassement si has_terrassement = false', () => {
      const interventionSansTerrass = { ...mockIntervention, has_terrassement: false }
      const wrapper = mount(MockInterventionCard, {
        props: { intervention: interventionSansTerrass }
      })

      expect(wrapper.find('[data-testid="terrassement-status"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="branchement-status"]').exists()).toBe(true)
    })
  })

  describe('Affichage des dates', () => {
    it('devrait formater et afficher les dates correctement', () => {
      const wrapper = mount(MockInterventionCard, {
        props: { intervention: mockIntervention }
      })

      const datesSection = wrapper.find('[data-testid="intervention-dates"]')
      expect(datesSection.text()).toContain('Créé le: 15/01/2024')
      expect(datesSection.text()).toContain('Terrassement prévu: 20/01/2024')
      expect(datesSection.text()).toContain('Branchement prévu: 22/01/2024')
    })

    it('ne devrait pas afficher les dates prévues si elles ne sont pas définies', () => {
      const interventionSansDates = {
        ...mockIntervention,
        date_terrassement_prevue: null,
        date_branchement_prevue: null
      }
      const wrapper = mount(MockInterventionCard, {
        props: { intervention: interventionSansDates }
      })

      const datesSection = wrapper.find('[data-testid="intervention-dates"]')
      expect(datesSection.text()).toContain('Créé le:')
      expect(datesSection.text()).not.toContain('Terrassement prévu:')
      expect(datesSection.text()).not.toContain('Branchement prévu:')
    })
  })

  describe('Affichage des coûts', () => {
    it('devrait afficher les informations de coût formatées', () => {
      const wrapper = mount(MockInterventionCard, {
        props: { intervention: mockIntervention }
      })

      expect(wrapper.find('[data-testid="total-duration"]').text()).toBe('8.5h')
      expect(wrapper.find('[data-testid="estimated-cost"]').text()).toBe('1 200,00 €')
      expect(wrapper.find('[data-testid="real-cost"]').text()).toBe('950,00 €')
    })

    it('devrait gérer les coûts à zéro', () => {
      const interventionZeroCost = {
        ...mockIntervention,
        duree_totale_heures: 0,
        cout_total_estime: 0,
        cout_total_reel: 0
      }
      const wrapper = mount(MockInterventionCard, {
        props: { intervention: interventionZeroCost }
      })

      expect(wrapper.find('[data-testid="total-duration"]').text()).toBe('0h')
      expect(wrapper.find('[data-testid="estimated-cost"]').text()).toBe('0,00 €')
      expect(wrapper.find('[data-testid="real-cost"]').text()).toBe('0,00 €')
    })
  })

  describe('Actions et boutons', () => {
    it('devrait afficher le bouton "Voir détails" par défaut', () => {
      const wrapper = mount(MockInterventionCard, {
        props: { intervention: mockIntervention }
      })

      expect(wrapper.find('[data-testid="view-button"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="view-button"]').text()).toBe('Voir détails')
    })

    it('devrait afficher le bouton "Modifier" si canEdit = true', () => {
      const wrapper = mount(MockInterventionCard, {
        props: {
          intervention: mockIntervention,
          canEdit: true
        }
      })

      expect(wrapper.find('[data-testid="edit-button"]').exists()).toBe(true)
    })

    it('ne devrait pas afficher le bouton "Modifier" si canEdit = false', () => {
      const wrapper = mount(MockInterventionCard, {
        props: {
          intervention: mockIntervention,
          canEdit: false
        }
      })

      expect(wrapper.find('[data-testid="edit-button"]').exists()).toBe(false)
    })

    it('devrait afficher le bouton "Supprimer" si canDelete = true', () => {
      const wrapper = mount(MockInterventionCard, {
        props: {
          intervention: mockIntervention,
          canDelete: true
        }
      })

      expect(wrapper.find('[data-testid="delete-button"]').exists()).toBe(true)
    })

    it('ne devrait pas afficher le bouton "Supprimer" par défaut', () => {
      const wrapper = mount(MockInterventionCard, {
        props: { intervention: mockIntervention }
      })

      expect(wrapper.find('[data-testid="delete-button"]').exists()).toBe(false)
    })
  })

  describe('Événements', () => {
    it('devrait émettre l\'événement "view" lors du clic sur "Voir détails"', async () => {
      const wrapper = mount(MockInterventionCard, {
        props: { intervention: mockIntervention }
      })

      await wrapper.find('[data-testid="view-button"]').trigger('click')

      expect(wrapper.emitted('view')).toBeTruthy()
      expect(wrapper.emitted('view')?.[0]).toEqual([mockIntervention.id])
    })

    it('devrait émettre l\'événement "edit" lors du clic sur "Modifier"', async () => {
      const wrapper = mount(MockInterventionCard, {
        props: {
          intervention: mockIntervention,
          canEdit: true
        }
      })

      await wrapper.find('[data-testid="edit-button"]').trigger('click')

      expect(wrapper.emitted('edit')).toBeTruthy()
      expect(wrapper.emitted('edit')?.[0]).toEqual([mockIntervention.id])
    })

    it('devrait émettre l\'événement "delete" lors du clic sur "Supprimer"', async () => {
      const wrapper = mount(MockInterventionCard, {
        props: {
          intervention: mockIntervention,
          canDelete: true
        }
      })

      await wrapper.find('[data-testid="delete-button"]').trigger('click')

      expect(wrapper.emitted('delete')).toBeTruthy()
      expect(wrapper.emitted('delete')?.[0]).toEqual([mockIntervention.id])
    })
  })

  describe('Props et réactivité', () => {
    it('devrait réagir aux changements de props', async () => {
      const wrapper = mount(MockInterventionCard, {
        props: { intervention: mockIntervention }
      })

      expect(wrapper.find('[data-testid="intervention-status"]').text()).toBe('En cours')

      // Changer le statut
      const updatedIntervention = { ...mockIntervention, statut_global: 'Terminée' as const }
      await wrapper.setProps({ intervention: updatedIntervention })

      expect(wrapper.find('[data-testid="intervention-status"]').text()).toBe('Terminée')
    })

    it('devrait respecter les changements de permissions', async () => {
      const wrapper = mount(MockInterventionCard, {
        props: {
          intervention: mockIntervention,
          canEdit: false,
          canDelete: false
        }
      })

      expect(wrapper.find('[data-testid="edit-button"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="delete-button"]').exists()).toBe(false)

      await wrapper.setProps({ canEdit: true, canDelete: true })

      expect(wrapper.find('[data-testid="edit-button"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="delete-button"]').exists()).toBe(true)
    })
  })
})