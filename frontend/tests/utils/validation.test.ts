import { describe, it, expect } from 'vitest'

// Utilitaires de validation côté frontend
class ValidationUtils {
  static validateEmail(email: string): boolean {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    return emailRegex.test(email)
  }

  static validateRequired(value: string | null | undefined): boolean {
    return value != null && value.toString().trim().length > 0
  }

  static validateLength(value: string, min: number, max?: number): boolean {
    const length = value.trim().length
    if (length < min) return false
    if (max && length > max) return false
    return true
  }

  static validatePhone(phone: string): boolean {
    // Format français : 01 23 45 67 89, 0123456789, +33 1 23 45 67 89, etc.
    const cleanPhone = phone.replace(/[\s\-\.]/g, '')
    const phoneRegex = /^(?:(?:\+33|0)[1-9](?:[0-9]{8}))$/
    return phoneRegex.test(cleanPhone)
  }

  static validateSiret(siret: string): boolean {
    const cleanSiret = siret.replace(/[\s\-]/g, '')

    if (cleanSiret.length !== 14 || !/^\d+$/.test(cleanSiret)) {
      return false
    }

    // Algorithme de Luhn pour SIRET
    let sum = 0
    for (let i = 0; i < 14; i++) {
      let digit = parseInt(cleanSiret[i])
      if (i % 2 === 1) {
        digit *= 2
        if (digit > 9) {
          digit = digit % 10 + 1
        }
      }
      sum += digit
    }

    return sum % 10 === 0
  }

  static validateInterventionData(data: any): { isValid: boolean; errors: Record<string, string> } {
    const errors: Record<string, string> = {}

    // Validation titre
    if (!this.validateRequired(data.titre)) {
      errors.titre = 'Le titre est obligatoire'
    } else if (!this.validateLength(data.titre, 3, 255)) {
      errors.titre = 'Le titre doit contenir entre 3 et 255 caractères'
    }

    // Validation client
    if (!this.validateRequired(data.client_nom)) {
      errors.client_nom = 'Le nom du client est obligatoire'
    }

    // Validation type de prestation
    if (!data.type_prestation_id || data.type_prestation_id <= 0) {
      errors.type_prestation_id = 'Le type de prestation est obligatoire'
    }

    // Validation spécifications Enedis
    if (data.type_reglementaire && !['type_1', 'type_2'].includes(data.type_reglementaire)) {
      errors.type_reglementaire = 'Type réglementaire invalide'
    }

    if (data.mode_pose && !['aerien', 'souterrain', 'aerosouterrain', 'souterrain_boite', 'di_seule'].includes(data.mode_pose)) {
      errors.mode_pose = 'Mode de pose invalide'
    }

    // Validation cohérence Type 1/Type 2 vs DI
    if (data.type_reglementaire === 'type_1' && data.longueur_derivation_individuelle > 30) {
      errors.longueur_derivation_individuelle = 'Pour un Type 1, la DI ne peut pas dépasser 30m'
    }

    if (data.type_reglementaire === 'type_2' && data.longueur_derivation_individuelle <= 30) {
      errors.longueur_derivation_individuelle = 'Pour un Type 2, la DI doit être supérieure à 30m'
    }

    // Validation distances
    if (data.longueur_liaison_reseau < 0) {
      errors.longueur_liaison_reseau = 'La longueur LR ne peut pas être négative'
    }

    if (data.longueur_derivation_individuelle < 0) {
      errors.longueur_derivation_individuelle = 'La longueur DI ne peut pas être négative'
    }

    // Validation cohérence distances
    if (data.longueur_liaison_reseau > 0 && data.longueur_derivation_individuelle > 0 && data.distance_raccordement > 0) {
      const calculated = data.longueur_liaison_reseau + data.longueur_derivation_individuelle
      const tolerance = Math.max(1, data.distance_raccordement * 0.1)

      if (Math.abs(calculated - data.distance_raccordement) > tolerance) {
        errors.distance_raccordement = `Distance incohérente : LR(${data.longueur_liaison_reseau}m) + DI(${data.longueur_derivation_individuelle}m) ≠ Total(${data.distance_raccordement}m)`
      }
    }

    // Validation priorité
    if (data.priorite && !['Basse', 'Normale', 'Haute', 'Urgente'].includes(data.priorite)) {
      errors.priorite = 'Priorité invalide'
    }

    return {
      isValid: Object.keys(errors).length === 0,
      errors
    }
  }

  static validateUserData(data: any): { isValid: boolean; errors: Record<string, string> } {
    const errors: Record<string, string> = {}

    // Validation nom d'utilisateur
    if (!this.validateRequired(data.username)) {
      errors.username = 'Le nom d\'utilisateur est obligatoire'
    } else if (!this.validateLength(data.username, 3, 50)) {
      errors.username = 'Le nom d\'utilisateur doit contenir entre 3 et 50 caractères'
    }

    // Validation email
    if (!this.validateRequired(data.email)) {
      errors.email = 'L\'email est obligatoire'
    } else if (!this.validateEmail(data.email)) {
      errors.email = 'Format d\'email invalide'
    }

    // Validation mot de passe (pour création)
    if (data.password !== undefined) {
      if (!this.validateRequired(data.password)) {
        errors.password = 'Le mot de passe est obligatoire'
      } else if (!this.validateLength(data.password, 6)) {
        errors.password = 'Le mot de passe doit contenir au moins 6 caractères'
      }
    }

    // Validation rôle
    if (data.role && !['admin', 'technicien', 'manager', 'client'].includes(data.role)) {
      errors.role = 'Rôle invalide'
    }

    // Validation prénom/nom
    if (!this.validateRequired(data.prenom)) {
      errors.prenom = 'Le prénom est obligatoire'
    }

    if (!this.validateRequired(data.nom)) {
      errors.nom = 'Le nom est obligatoire'
    }

    // Validation téléphone (optionnel)
    if (data.telephone && !this.validatePhone(data.telephone)) {
      errors.telephone = 'Format de téléphone invalide'
    }

    // Validation taux horaire
    if (data.taux_horaire !== undefined && (isNaN(data.taux_horaire) || data.taux_horaire < 0)) {
      errors.taux_horaire = 'Le taux horaire doit être un nombre positif'
    }

    return {
      isValid: Object.keys(errors).length === 0,
      errors
    }
  }

  static validateClientData(data: any): { isValid: boolean; errors: Record<string, string> } {
    const errors: Record<string, string> = {}

    // Validation nom/prénom
    if (!this.validateRequired(data.nom)) {
      errors.nom = 'Le nom est obligatoire'
    }

    if (!this.validateRequired(data.prenom)) {
      errors.prenom = 'Le prénom est obligatoire'
    }

    // Validation email (optionnel)
    if (data.email && !this.validateEmail(data.email)) {
      errors.email = 'Format d\'email invalide'
    }

    // Validation téléphone (optionnel)
    if (data.telephone && !this.validatePhone(data.telephone)) {
      errors.telephone = 'Format de téléphone invalide'
    }

    // Validation SIRET (optionnel)
    if (data.siret && !this.validateSiret(data.siret)) {
      errors.siret = 'Format de SIRET invalide'
    }

    // Validation code postal (optionnel)
    if (data.code_postal && !/^\d{5}$/.test(data.code_postal)) {
      errors.code_postal = 'Le code postal doit contenir 5 chiffres'
    }

    return {
      isValid: Object.keys(errors).length === 0,
      errors
    }
  }

  static sanitizeInput(input: string): string {
    return input.trim()
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#x27;')
      .replace(/\//g, '&#x2F;')
  }
}

describe('ValidationUtils', () => {
  describe('validateEmail', () => {
    it('devrait accepter des emails valides', () => {
      expect(ValidationUtils.validateEmail('test@example.com')).toBe(true)
      expect(ValidationUtils.validateEmail('user.name@domain.co.uk')).toBe(true)
      expect(ValidationUtils.validateEmail('test+tag@example.org')).toBe(true)
    })

    it('devrait rejeter des emails invalides', () => {
      expect(ValidationUtils.validateEmail('invalid-email')).toBe(false)
      expect(ValidationUtils.validateEmail('test@')).toBe(false)
      expect(ValidationUtils.validateEmail('@example.com')).toBe(false)
      expect(ValidationUtils.validateEmail('test.example.com')).toBe(false)
      expect(ValidationUtils.validateEmail('')).toBe(false)
    })
  })

  describe('validateRequired', () => {
    it('devrait accepter des valeurs non vides', () => {
      expect(ValidationUtils.validateRequired('test')).toBe(true)
      expect(ValidationUtils.validateRequired('0')).toBe(true)
      expect(ValidationUtils.validateRequired('   content   ')).toBe(true)
    })

    it('devrait rejeter des valeurs vides', () => {
      expect(ValidationUtils.validateRequired('')).toBe(false)
      expect(ValidationUtils.validateRequired('   ')).toBe(false)
      expect(ValidationUtils.validateRequired(null)).toBe(false)
      expect(ValidationUtils.validateRequired(undefined)).toBe(false)
    })
  })

  describe('validateLength', () => {
    it('devrait valider la longueur minimale', () => {
      expect(ValidationUtils.validateLength('abc', 3)).toBe(true)
      expect(ValidationUtils.validateLength('abcd', 3)).toBe(true)
      expect(ValidationUtils.validateLength('ab', 3)).toBe(false)
    })

    it('devrait valider la longueur maximale', () => {
      expect(ValidationUtils.validateLength('abc', 0, 5)).toBe(true)
      expect(ValidationUtils.validateLength('abcde', 0, 5)).toBe(true)
      expect(ValidationUtils.validateLength('abcdef', 0, 5)).toBe(false)
    })

    it('devrait gérer les espaces en début/fin', () => {
      expect(ValidationUtils.validateLength('  abc  ', 3)).toBe(true)
      expect(ValidationUtils.validateLength('  ab  ', 3)).toBe(false)
    })
  })

  describe('validatePhone', () => {
    it('devrait accepter des numéros français valides', () => {
      expect(ValidationUtils.validatePhone('0123456789')).toBe(true)
      expect(ValidationUtils.validatePhone('01 23 45 67 89')).toBe(true)
      expect(ValidationUtils.validatePhone('01-23-45-67-89')).toBe(true)
      expect(ValidationUtils.validatePhone('01.23.45.67.89')).toBe(true)
      expect(ValidationUtils.validatePhone('+33123456789')).toBe(true)
    })

    it('devrait rejeter des numéros invalides', () => {
      expect(ValidationUtils.validatePhone('123456789')).toBe(false) // Trop court
      expect(ValidationUtils.validatePhone('0023456789')).toBe(false) // Commence par 00
      expect(ValidationUtils.validatePhone('12345')).toBe(false)
      expect(ValidationUtils.validatePhone('abcdefghij')).toBe(false)
      expect(ValidationUtils.validatePhone('')).toBe(false)
    })
  })

  describe('validateSiret', () => {
    it('devrait accepter des SIRET valides', () => {
      expect(ValidationUtils.validateSiret('73282932000074')).toBe(true) // SIRET valide
      expect(ValidationUtils.validateSiret('732 829 320 00074')).toBe(true)
      expect(ValidationUtils.validateSiret('732-829-320-00074')).toBe(true)
    })

    it('devrait rejeter des SIRET invalides', () => {
      expect(ValidationUtils.validateSiret('12345678901234')).toBe(false) // Mauvais checksum
      expect(ValidationUtils.validateSiret('123456789')).toBe(false) // Trop court
      expect(ValidationUtils.validateSiret('abcd1234567890')).toBe(false) // Caractères non numériques
      expect(ValidationUtils.validateSiret('')).toBe(false)
    })
  })

  describe('validateInterventionData', () => {
    it('devrait valider des données d\'intervention correctes', () => {
      const validData = {
        titre: 'Installation électrique',
        client_nom: 'Dupont Jean',
        type_prestation_id: 1,
        type_reglementaire: 'type_1',
        mode_pose: 'souterrain',
        longueur_liaison_reseau: 50,
        longueur_derivation_individuelle: 25,
        distance_raccordement: 75,
        priorite: 'Normale'
      }

      const result = ValidationUtils.validateInterventionData(validData)
      expect(result.isValid).toBe(true)
      expect(Object.keys(result.errors)).toHaveLength(0)
    })

    it('devrait détecter les données manquantes', () => {
      const invalidData = {
        titre: '',
        client_nom: '',
        type_prestation_id: 0
      }

      const result = ValidationUtils.validateInterventionData(invalidData)
      expect(result.isValid).toBe(false)
      expect(result.errors.titre).toBe('Le titre est obligatoire')
      expect(result.errors.client_nom).toBe('Le nom du client est obligatoire')
      expect(result.errors.type_prestation_id).toBe('Le type de prestation est obligatoire')
    })

    it('devrait détecter l\'incohérence Type 1 vs DI', () => {
      const invalidData = {
        titre: 'Test',
        client_nom: 'Client',
        type_prestation_id: 1,
        type_reglementaire: 'type_1',
        longueur_derivation_individuelle: 40 // > 30m pour Type 1
      }

      const result = ValidationUtils.validateInterventionData(invalidData)
      expect(result.isValid).toBe(false)
      expect(result.errors.longueur_derivation_individuelle)
        .toBe('Pour un Type 1, la DI ne peut pas dépasser 30m')
    })

    it('devrait détecter l\'incohérence des distances', () => {
      const invalidData = {
        titre: 'Test',
        client_nom: 'Client',
        type_prestation_id: 1,
        longueur_liaison_reseau: 50,
        longueur_derivation_individuelle: 30,
        distance_raccordement: 100 // 50 + 30 ≠ 100
      }

      const result = ValidationUtils.validateInterventionData(invalidData)
      expect(result.isValid).toBe(false)
      expect(result.errors.distance_raccordement).toContain('Distance incohérente')
    })
  })

  describe('validateUserData', () => {
    it('devrait valider des données utilisateur correctes', () => {
      const validData = {
        username: 'testuser',
        email: 'test@example.com',
        password: 'password123',
        role: 'technicien',
        prenom: 'Jean',
        nom: 'Dupont',
        telephone: '0123456789',
        taux_horaire: 45.5
      }

      const result = ValidationUtils.validateUserData(validData)
      expect(result.isValid).toBe(true)
      expect(Object.keys(result.errors)).toHaveLength(0)
    })

    it('devrait détecter les données utilisateur invalides', () => {
      const invalidData = {
        username: 'ab', // Trop court
        email: 'invalid-email',
        password: '123', // Trop court
        role: 'invalid_role',
        prenom: '',
        nom: '',
        telephone: 'invalid-phone',
        taux_horaire: -5
      }

      const result = ValidationUtils.validateUserData(invalidData)
      expect(result.isValid).toBe(false)
      expect(result.errors.username).toContain('entre 3 et 50 caractères')
      expect(result.errors.email).toBe('Format d\'email invalide')
      expect(result.errors.password).toContain('au moins 6 caractères')
      expect(result.errors.role).toBe('Rôle invalide')
      expect(result.errors.prenom).toBe('Le prénom est obligatoire')
      expect(result.errors.nom).toBe('Le nom est obligatoire')
      expect(result.errors.telephone).toBe('Format de téléphone invalide')
      expect(result.errors.taux_horaire).toBe('Le taux horaire doit être un nombre positif')
    })
  })

  describe('validateClientData', () => {
    it('devrait valider des données client correctes', () => {
      const validData = {
        nom: 'Dupont',
        prenom: 'Jean',
        email: 'jean.dupont@example.com',
        telephone: '0123456789',
        siret: '73282932000074',
        code_postal: '75001'
      }

      const result = ValidationUtils.validateClientData(validData)
      expect(result.isValid).toBe(true)
      expect(Object.keys(result.errors)).toHaveLength(0)
    })

    it('devrait détecter les données client invalides', () => {
      const invalidData = {
        nom: '',
        prenom: '',
        email: 'invalid-email',
        telephone: 'invalid-phone',
        siret: 'invalid-siret',
        code_postal: '123'
      }

      const result = ValidationUtils.validateClientData(invalidData)
      expect(result.isValid).toBe(false)
      expect(result.errors.nom).toBe('Le nom est obligatoire')
      expect(result.errors.prenom).toBe('Le prénom est obligatoire')
      expect(result.errors.email).toBe('Format d\'email invalide')
      expect(result.errors.telephone).toBe('Format de téléphone invalide')
      expect(result.errors.siret).toBe('Format de SIRET invalide')
      expect(result.errors.code_postal).toBe('Le code postal doit contenir 5 chiffres')
    })
  })

  describe('sanitizeInput', () => {
    it('devrait nettoyer les caractères dangereux', () => {
      expect(ValidationUtils.sanitizeInput('<script>alert("xss")</script>'))
        .toBe('&lt;script&gt;alert(&quot;xss&quot;)&lt;&#x2F;script&gt;')

      expect(ValidationUtils.sanitizeInput('Test & "quotes" with spaces'))
        .toBe('Test &amp; &quot;quotes&quot; with spaces')

      expect(ValidationUtils.sanitizeInput("  Test with 'single quotes'  "))
        .toBe('Test with &#x27;single quotes&#x27;')
    })

    it('devrait conserver le contenu normal', () => {
      expect(ValidationUtils.sanitizeInput('Texte normal sans caractères spéciaux'))
        .toBe('Texte normal sans caractères spéciaux')

      expect(ValidationUtils.sanitizeInput('123 ABC xyz'))
        .toBe('123 ABC xyz')
    })
  })
})