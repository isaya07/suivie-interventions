/**
 * Types TypeScript pour l'application de suivi d'interventions
 *
 * Définit toutes les interfaces et types utilisés dans l'application
 * Basé sur l'API backend et les structures de données Enedis
 */

// ============================================================================
// TYPES DE BASE
// ============================================================================

export type Status = 'En attente' | 'En cours' | 'En pause' | 'Terminée' | 'Annulée'
export type Priority = 'Basse' | 'Normale' | 'Haute' | 'Urgente'
export type Role = 'admin' | 'technicien' | 'manager' | 'client'
export type TypeReglementaire = 'type_1' | 'type_2'
export type ModePose = 'aerien' | 'souterrain' | 'aerosouterrain' | 'souterrain_boite' | 'di_seule'
export type TypeIntervention = 'Maintenance' | 'Réparation' | 'Installation' | 'Diagnostic' | 'Autre'
export type PhaseStatus = 'en_attente' | 'en_cours' | 'terminee' | 'annulee'

// ============================================================================
// INTERFACES UTILISATEUR
// ============================================================================

export interface User {
  id: number
  username: string
  email: string
  role: Role
  prenom: string
  nom: string
  telephone?: string
  taux_horaire?: number
  is_active: boolean
  date_creation: string
  date_modification?: string
}

export interface AuthUser extends User {
  token?: string
  permissions?: string[]
}

// ============================================================================
// INTERFACES CLIENT
// ============================================================================

export interface Client {
  id: number
  nom: string
  prenom: string
  email?: string
  telephone?: string
  adresse?: string
  ville?: string
  code_postal?: string
  entreprise?: string
  siret?: string
  notes?: string
  date_creation: string
  date_modification?: string
}

export interface ClientContact {
  id: number
  client_id: number
  nom: string
  prenom: string
  fonction?: string
  email?: string
  telephone?: string
  is_principal: boolean
}

// ============================================================================
// INTERFACES PRESTATIONS
// ============================================================================

export interface TypePrestation {
  id: number
  nom: string
  code: string
  description?: string
  has_terrassement: boolean
  duree_terrassement_heures?: number
  duree_branchement_heures?: number
  taux_terrassement?: number
  taux_branchement?: number
  type_reglementaire?: TypeReglementaire
  mode_pose?: ModePose
  is_active: boolean
}

// ============================================================================
// INTERFACES INTERVENTION
// ============================================================================

export interface InterventionBase {
  id: number
  numero: string
  titre: string
  description?: string
  priorite: Priority
  client_id?: number
  client_nom: string
  client_contact?: string
  client_telephone?: string
  createur_id: number
  type_prestation_id: number
  date_creation: string
  date_modification?: string
}

export interface InterventionSpecifications {
  type_reglementaire: TypeReglementaire
  mode_pose: ModePose
  longueur_liaison_reseau: number
  longueur_derivation_individuelle: number
  distance_raccordement: number
  puissance_souscrite?: number
}

export interface InterventionSuiviProcessus {
  date_reception_dossier?: string
  date_etude_technique?: string
  date_validation_devis?: string
  date_realisation_terrassement?: string
  date_realisation_cablage?: string
  date_mise_en_service?: string
}

export interface InterventionPhase {
  statut: PhaseStatus
  technicien_id?: number
  technicien_nom?: string
  taux_horaire: number
  date_debut?: string
  date_fin?: string
  duree_heures?: number
  cout?: number
  notes?: string
}

export interface InterventionTotaux {
  duree_totale_heures: number
  cout_total_reel: number
  cout_total_estime: number
  ecart_budget: number
  ecart_pourcentage: number
}

export interface Intervention extends InterventionBase {
  type_prestation: TypePrestation
  specifications: InterventionSpecifications
  suivi_processus: InterventionSuiviProcessus
  phase_terrassement: InterventionPhase
  phase_branchement: InterventionPhase
  planification: {
    date_terrassement_prevue?: string
    date_branchement_prevue?: string
  }
  totaux: InterventionTotaux
  has_terrassement: boolean
  statut_global: Status
}

// Intervention simplifiée pour les listes
export interface InterventionListItem {
  id: number
  numero: string
  titre: string
  description?: string
  priorite: Priority
  client_nom: string
  type_prestation_nom: string
  has_terrassement: boolean
  phase_terrassement_statut: PhaseStatus
  phase_branchement_statut: PhaseStatus
  statut_global: Status
  technicien_terrassement?: string
  technicien_branchement?: string
  date_terrassement_prevue?: string
  date_branchement_prevue?: string
  duree_totale_heures: number
  cout_total_reel: number
  cout_total_estime: number
  date_creation: string
}

// ============================================================================
// INTERFACES AUDIT ET HISTORIQUE
// ============================================================================

export interface AuditAction {
  id: number
  intervention_id: number
  user_id?: number
  username?: string
  user_name?: string
  action: string
  action_type: string
  is_critical: boolean
  commentaire?: string
  ancien_statut?: string
  nouveau_statut?: string
  changes_data?: Record<string, any>
  ip_address?: string
  created_at: string
}

export interface AuditStats {
  action_type: string
  count: number
  critical_count: number
}

// ============================================================================
// INTERFACES SESSION ET TRAVAIL
// ============================================================================

export interface SessionTravail {
  id: number
  intervention_id: number
  phase: 'terrassement' | 'branchement'
  technicien_id: number
  technicien_nom: string
  date_debut: string
  date_fin?: string
  duree_heures?: number
  notes?: string
  taux_horaire: number
  cout?: number
  is_active: boolean
}

// ============================================================================
// INTERFACES BACKUP
// ============================================================================

export interface BackupInfo {
  id: string
  timestamp: string
  type: 'full' | 'incremental'
  size: number
}

export interface BackupStatus {
  system_operational: boolean
  last_backup?: BackupInfo
  total_backups: number
  backup_directory: string
  backup_directory_size: number
  log_file_exists: boolean
  disk_space_available: number
  recommendations: string[]
}

// ============================================================================
// INTERFACES FORMULAIRES
// ============================================================================

export interface InterventionFormData {
  titre: string
  description?: string
  client_id?: number
  client_nom?: string
  client_contact?: string
  client_telephone?: string
  priorite: Priority
  type_prestation_id: number
  type_reglementaire: TypeReglementaire
  mode_pose: ModePose
  longueur_liaison_reseau: number
  longueur_derivation_individuelle: number
  distance_raccordement?: number
  puissance_souscrite?: number
  date_reception_dossier?: string
  date_etude_technique?: string
  date_validation_devis?: string
  technicien_terrassement_id?: number
  technicien_branchement_id?: number
  date_terrassement_prevue?: string
  date_branchement_prevue?: string
}

export interface UserFormData {
  username: string
  email: string
  password?: string
  role: Role
  prenom: string
  nom: string
  telephone?: string
  taux_horaire?: number
  is_active: boolean
}

export interface ClientFormData {
  nom: string
  prenom: string
  email?: string
  telephone?: string
  adresse?: string
  ville?: string
  code_postal?: string
  entreprise?: string
  siret?: string
  notes?: string
}

// ============================================================================
// INTERFACES FILTRES
// ============================================================================

export interface InterventionFilters {
  technicien_id?: number
  client_id?: number
  type_prestation_id?: number
  status?: Status[]
  priority?: Priority[]
  type_reglementaire?: TypeReglementaire[]
  mode_pose?: ModePose[]
  dateFrom?: string
  dateTo?: string
  distance_min?: number
  distance_max?: number
  search?: string
}

export interface UserFilters {
  role?: Role[]
  is_active?: boolean
  search?: string
}

// ============================================================================
// INTERFACES API
// ============================================================================

export interface ApiResponse<T = any> {
  success: boolean
  data?: T
  message?: string
  error?: string
  total?: number
}

export interface ApiError {
  message: string
  code?: number
  details?: Record<string, any>
}

export interface PaginationParams {
  page?: number
  limit?: number
  sort?: string
  order?: 'asc' | 'desc'
}

export interface PaginatedResponse<T> extends ApiResponse<T[]> {
  pagination: {
    page: number
    limit: number
    total: number
    totalPages: number
  }
}

// ============================================================================
// INTERFACES STORE
// ============================================================================

export interface AuthState {
  user: AuthUser | null
  isAuthenticated: boolean
  loading: boolean
  error: string | null
}

export interface InterventionsState {
  interventions: InterventionListItem[]
  currentIntervention: Intervention | null
  loading: boolean
  error: string | null
  filters: InterventionFilters
  pagination: {
    page: number
    limit: number
    total: number
  }
}

export interface UsersState {
  users: User[]
  techniciens: User[]
  loading: boolean
  error: string | null
  filters: UserFilters
}

export interface ClientsState {
  clients: Client[]
  loading: boolean
  error: string | null
  search: string
}

// ============================================================================
// INTERFACES VALIDATION
// ============================================================================

export interface ValidationError {
  field: string
  message: string
  code?: string
}

export interface ValidationResult {
  isValid: boolean
  errors: ValidationError[]
}

// ============================================================================
// INTERFACES MÉTRIQUES ET STATS
// ============================================================================

export interface InterventionStats {
  total: number
  en_attente: number
  en_cours: number
  terminees: number
  annulees: number
  moyenne_duree: number
  ecart_budget_moyen: number
}

export interface TechnicienStats {
  technicien_id: number
  technicien_nom: string
  interventions_total: number
  interventions_terminees: number
  heures_total: number
  taux_reussite: number
  cout_moyen: number
}

export interface DelaiStats {
  intervention_id: number
  type_reglementaire: TypeReglementaire
  delai_reception_etude: number
  delai_etude_validation: number
  delai_validation_realisation: number
  delai_total: number
  delai_reglementaire: number
  en_retard: boolean
  pourcentage_avancement: number
}

// ============================================================================
// TYPES UTILITAIRES
// ============================================================================

export type DeepPartial<T> = {
  [P in keyof T]?: DeepPartial<T[P]>
}

export type OptionalExcept<T, K extends keyof T> = Partial<T> & Pick<T, K>

export type CreateInput<T> = Omit<T, 'id' | 'date_creation' | 'date_modification'>
export type UpdateInput<T> = Partial<Omit<T, 'id' | 'date_creation'>>

// ============================================================================
// TYPES D'ÉVÉNEMENTS
// ============================================================================

export interface AppEvent {
  type: string
  payload?: any
  timestamp: Date
}

export interface NotificationEvent extends AppEvent {
  type: 'notification'
  payload: {
    title: string
    message: string
    variant: 'success' | 'error' | 'warning' | 'info'
    duration?: number
  }
}

export interface InterventionEvent extends AppEvent {
  type: 'intervention:created' | 'intervention:updated' | 'intervention:deleted' | 'phase:started' | 'phase:completed'
  payload: {
    intervention: Intervention | InterventionListItem
    user?: User
  }
}

// ============================================================================
// EXPORTS PAR DÉFAUT
// ============================================================================

export default {
  // Types de base
  Status,
  Priority,
  Role,
  TypeReglementaire,
  ModePose,
  TypeIntervention,
  PhaseStatus,

  // Interfaces principales
  User,
  Client,
  Intervention,
  TypePrestation,

  // Types de réponse API
  ApiResponse,
  ApiError
}