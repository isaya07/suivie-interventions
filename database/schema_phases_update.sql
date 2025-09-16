-- ================================================================
-- MISE À JOUR DU SCHÉMA POUR SYSTÈME D'INTERVENTIONS ÉLECTRIQUES
-- Gestion des phases (branchement + terrassement) avec taux différenciés
-- ================================================================

-- Table des types de prestations électriques
CREATE TABLE IF NOT EXISTS types_prestations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL COMMENT 'Nom de la prestation (ex: Branchement seul)',
    code VARCHAR(50) UNIQUE NOT NULL COMMENT 'Code unique pour identification',
    duree_branchement_heures DECIMAL(4,2) NOT NULL COMMENT 'Durée estimée phase branchement',
    duree_terrassement_heures DECIMAL(4,2) DEFAULT NULL COMMENT 'Durée estimée phase terrassement (null si pas applicable)',
    has_terrassement BOOLEAN DEFAULT FALSE COMMENT 'Indique si la prestation inclut du terrassement',
    description TEXT COMMENT 'Description détaillée de la prestation',
    actif BOOLEAN DEFAULT TRUE COMMENT 'Prestation active ou archivée',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_actif (actif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Types de prestations électriques disponibles';

-- Données de base pour les types de prestations
INSERT INTO types_prestations (nom, code, duree_branchement_heures, duree_terrassement_heures, has_terrassement, description) VALUES
('Branchement électrique seul', 'BRANCHEMENT_SEUL', 4.00, NULL, FALSE, 'Installation et raccordement électrique uniquement'),
('Branchement avec terrassement', 'BRANCHEMENT_TERRASSEMENT', 4.00, 4.00, TRUE, 'Installation électrique complète avec travaux de terrassement');

-- Mise à jour de la table users pour inclure les taux horaires
ALTER TABLE users
ADD COLUMN IF NOT EXISTS taux_horaire DECIMAL(6,2) DEFAULT NULL COMMENT 'Taux horaire du technicien en euros',
ADD COLUMN IF NOT EXISTS specialite_principale ENUM('cableur', 'terrassier', 'autre') DEFAULT 'autre' COMMENT 'Spécialité principale du technicien';

-- Mise à jour des taux horaires selon le type de technicien
UPDATE users SET
    taux_horaire = CASE
        WHEN type_technicien = 'cableur' THEN 45.00
        WHEN type_technicien = 'terrassier' THEN 38.00
        ELSE 40.00
    END,
    specialite_principale = CASE
        WHEN type_technicien = 'cableur' THEN 'cableur'
        WHEN type_technicien = 'terrassier' THEN 'terrassier'
        ELSE 'autre'
    END
WHERE taux_horaire IS NULL;

-- Extension majeure de la table interventions pour les phases
ALTER TABLE interventions
-- Référence au type de prestation
ADD COLUMN IF NOT EXISTS type_prestation_id INT DEFAULT NULL COMMENT 'Référence vers types_prestations',

-- === PHASE BRANCHEMENT (obligatoire) ===
ADD COLUMN IF NOT EXISTS phase_branchement_statut ENUM('en_attente', 'en_cours', 'terminee', 'annulee') DEFAULT 'en_attente' COMMENT 'Statut de la phase branchement',
ADD COLUMN IF NOT EXISTS phase_branchement_technicien_id INT DEFAULT NULL COMMENT 'Câbleur assigné à la phase branchement',
ADD COLUMN IF NOT EXISTS phase_branchement_taux_horaire DECIMAL(6,2) DEFAULT 45.00 COMMENT 'Taux horaire appliqué pour le branchement',
ADD COLUMN IF NOT EXISTS phase_branchement_date_debut DATETIME DEFAULT NULL COMMENT 'Début réel de la phase branchement',
ADD COLUMN IF NOT EXISTS phase_branchement_date_fin DATETIME DEFAULT NULL COMMENT 'Fin réelle de la phase branchement',
ADD COLUMN IF NOT EXISTS phase_branchement_duree_heures DECIMAL(5,2) DEFAULT NULL COMMENT 'Durée réelle en heures de la phase branchement',
ADD COLUMN IF NOT EXISTS phase_branchement_cout DECIMAL(8,2) DEFAULT NULL COMMENT 'Coût total de la phase branchement',
ADD COLUMN IF NOT EXISTS phase_branchement_notes TEXT DEFAULT NULL COMMENT 'Notes spécifiques à la phase branchement',

-- === PHASE TERRASSEMENT (optionnelle) ===
ADD COLUMN IF NOT EXISTS phase_terrassement_statut ENUM('en_attente', 'en_cours', 'terminee', 'annulee', 'non_applicable') DEFAULT 'non_applicable' COMMENT 'Statut de la phase terrassement',
ADD COLUMN IF NOT EXISTS phase_terrassement_technicien_id INT DEFAULT NULL COMMENT 'Terrassier assigné à la phase terrassement',
ADD COLUMN IF NOT EXISTS phase_terrassement_taux_horaire DECIMAL(6,2) DEFAULT 38.00 COMMENT 'Taux horaire appliqué pour le terrassement',
ADD COLUMN IF NOT EXISTS phase_terrassement_date_debut DATETIME DEFAULT NULL COMMENT 'Début réel de la phase terrassement',
ADD COLUMN IF NOT EXISTS phase_terrassement_date_fin DATETIME DEFAULT NULL COMMENT 'Fin réelle de la phase terrassement',
ADD COLUMN IF NOT EXISTS phase_terrassement_duree_heures DECIMAL(5,2) DEFAULT NULL COMMENT 'Durée réelle en heures de la phase terrassement',
ADD COLUMN IF NOT EXISTS phase_terrassement_cout DECIMAL(8,2) DEFAULT NULL COMMENT 'Coût total de la phase terrassement',
ADD COLUMN IF NOT EXISTS phase_terrassement_notes TEXT DEFAULT NULL COMMENT 'Notes spécifiques à la phase terrassement',

-- === TOTAUX ET CALCULS ===
ADD COLUMN IF NOT EXISTS duree_totale_heures DECIMAL(6,2) DEFAULT NULL COMMENT 'Durée totale réelle (branchement + terrassement)',
ADD COLUMN IF NOT EXISTS cout_total_reel DECIMAL(10,2) DEFAULT NULL COMMENT 'Coût total réel (somme des phases)',
ADD COLUMN IF NOT EXISTS cout_total_estime DECIMAL(10,2) DEFAULT NULL COMMENT 'Coût total estimé au départ',
ADD COLUMN IF NOT EXISTS ecart_budget DECIMAL(10,2) DEFAULT NULL COMMENT 'Écart entre coût réel et estimé',
ADD COLUMN IF NOT EXISTS ecart_pourcentage DECIMAL(5,2) DEFAULT NULL COMMENT 'Écart en pourcentage',

-- === PLANIFICATION ===
ADD COLUMN IF NOT EXISTS date_branchement_prevue DATETIME DEFAULT NULL COMMENT 'Date de branchement planifiée',
ADD COLUMN IF NOT EXISTS date_terrassement_prevue DATETIME DEFAULT NULL COMMENT 'Date de terrassement planifiée',

-- Index pour les performances
ADD INDEX IF NOT EXISTS idx_type_prestation (type_prestation_id),
ADD INDEX IF NOT EXISTS idx_phase_branchement_statut (phase_branchement_statut),
ADD INDEX IF NOT EXISTS idx_phase_terrassement_statut (phase_terrassement_statut),
ADD INDEX IF NOT EXISTS idx_phase_branchement_technicien (phase_branchement_technicien_id),
ADD INDEX IF NOT EXISTS idx_phase_terrassement_technicien (phase_terrassement_technicien_id),
ADD INDEX IF NOT EXISTS idx_date_branchement_prevue (date_branchement_prevue),
ADD INDEX IF NOT EXISTS idx_date_terrassement_prevue (date_terrassement_prevue);

-- Contraintes de clés étrangères
ALTER TABLE interventions
ADD CONSTRAINT IF NOT EXISTS fk_interventions_type_prestation
    FOREIGN KEY (type_prestation_id) REFERENCES types_prestations(id) ON UPDATE CASCADE ON DELETE SET NULL,
ADD CONSTRAINT IF NOT EXISTS fk_interventions_technicien_branchement
    FOREIGN KEY (phase_branchement_technicien_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
ADD CONSTRAINT IF NOT EXISTS fk_interventions_technicien_terrassement
    FOREIGN KEY (phase_terrassement_technicien_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL;

-- Table pour l'historique détaillé des sessions de travail (chronomètre)
CREATE TABLE IF NOT EXISTS intervention_sessions_travail (
    id INT PRIMARY KEY AUTO_INCREMENT,
    intervention_id INT NOT NULL COMMENT 'Référence vers l\'intervention',
    phase ENUM('branchement', 'terrassement') NOT NULL COMMENT 'Phase de l\'intervention',
    technicien_id INT NOT NULL COMMENT 'Technicien qui a travaillé',
    debut DATETIME NOT NULL COMMENT 'Heure de début de la session',
    fin DATETIME DEFAULT NULL COMMENT 'Heure de fin de la session (NULL si en cours)',
    duree_minutes INT DEFAULT NULL COMMENT 'Durée de la session en minutes',
    notes TEXT DEFAULT NULL COMMENT 'Notes pour cette session de travail',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_intervention_phase (intervention_id, phase),
    INDEX idx_technicien (technicien_id),
    INDEX idx_debut (debut),

    FOREIGN KEY (intervention_id) REFERENCES interventions(id) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (technicien_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sessions de travail détaillées avec chronomètre';

-- Vue pour simplifier les requêtes d'interventions avec details
CREATE OR REPLACE VIEW v_interventions_completes AS
SELECT
    i.*,
    tp.nom as type_prestation_nom,
    tp.code as type_prestation_code,
    tp.has_terrassement,

    -- Technicien branchement
    ub.nom as technicien_branchement_nom,
    ub.prenom as technicien_branchement_prenom,
    ub.taux_horaire as technicien_branchement_taux,

    -- Technicien terrassement
    ut.nom as technicien_terrassement_nom,
    ut.prenom as technicien_terrassement_prenom,
    ut.taux_horaire as technicien_terrassement_taux,

    -- Client info
    c.nom as client_nom,
    c.contact_principal as client_contact,

    -- Calculs automatiques
    CASE
        WHEN i.phase_branchement_statut = 'terminee' AND
             (tp.has_terrassement = FALSE OR i.phase_terrassement_statut IN ('terminee', 'non_applicable'))
        THEN 'Terminée'
        WHEN i.phase_branchement_statut = 'en_cours' OR i.phase_terrassement_statut = 'en_cours'
        THEN 'En cours'
        WHEN i.phase_branchement_statut = 'annulee' OR i.phase_terrassement_statut = 'annulee'
        THEN 'Annulée'
        ELSE 'En attente'
    END as statut_global

FROM interventions i
LEFT JOIN types_prestations tp ON i.type_prestation_id = tp.id
LEFT JOIN users ub ON i.phase_branchement_technicien_id = ub.id
LEFT JOIN users ut ON i.phase_terrassement_technicien_id = ut.id
LEFT JOIN clients c ON i.client_id = c.id;

-- Triggers pour calculs automatiques
DELIMITER //

-- Trigger pour calculer automatiquement les coûts et totaux
CREATE TRIGGER IF NOT EXISTS tr_interventions_calculate_costs
    BEFORE UPDATE ON interventions
    FOR EACH ROW
BEGIN
    -- Calculer le coût de la phase branchement
    IF NEW.phase_branchement_duree_heures IS NOT NULL AND NEW.phase_branchement_taux_horaire IS NOT NULL THEN
        SET NEW.phase_branchement_cout = NEW.phase_branchement_duree_heures * NEW.phase_branchement_taux_horaire;
    END IF;

    -- Calculer le coût de la phase terrassement
    IF NEW.phase_terrassement_duree_heures IS NOT NULL AND NEW.phase_terrassement_taux_horaire IS NOT NULL THEN
        SET NEW.phase_terrassement_cout = NEW.phase_terrassement_duree_heures * NEW.phase_terrassement_taux_horaire;
    END IF;

    -- Calculer les totaux
    SET NEW.duree_totale_heures = COALESCE(NEW.phase_branchement_duree_heures, 0) + COALESCE(NEW.phase_terrassement_duree_heures, 0);
    SET NEW.cout_total_reel = COALESCE(NEW.phase_branchement_cout, 0) + COALESCE(NEW.phase_terrassement_cout, 0);

    -- Calculer l'écart budgétaire
    IF NEW.cout_total_estime IS NOT NULL AND NEW.cout_total_reel IS NOT NULL THEN
        SET NEW.ecart_budget = NEW.cout_total_reel - NEW.cout_total_estime;
        SET NEW.ecart_pourcentage = ROUND((NEW.ecart_budget / NEW.cout_total_estime) * 100, 2);
    END IF;
END//

-- Trigger pour mettre à jour les durées depuis les sessions de travail
CREATE TRIGGER IF NOT EXISTS tr_sessions_update_intervention_duration
    AFTER INSERT ON intervention_sessions_travail
    FOR EACH ROW
BEGIN
    DECLARE total_minutes_branchement INT DEFAULT 0;
    DECLARE total_minutes_terrassement INT DEFAULT 0;

    -- Calculer total minutes pour phase branchement
    SELECT COALESCE(SUM(duree_minutes), 0) INTO total_minutes_branchement
    FROM intervention_sessions_travail
    WHERE intervention_id = NEW.intervention_id AND phase = 'branchement' AND duree_minutes IS NOT NULL;

    -- Calculer total minutes pour phase terrassement
    SELECT COALESCE(SUM(duree_minutes), 0) INTO total_minutes_terrassement
    FROM intervention_sessions_travail
    WHERE intervention_id = NEW.intervention_id AND phase = 'terrassement' AND duree_minutes IS NOT NULL;

    -- Mettre à jour l'intervention
    UPDATE interventions SET
        phase_branchement_duree_heures = total_minutes_branchement / 60.0,
        phase_terrassement_duree_heures = total_minutes_terrassement / 60.0
    WHERE id = NEW.intervention_id;
END//

DELIMITER ;

-- Mise à jour des interventions existantes pour compatibilité
UPDATE interventions SET
    type_prestation_id = 1,  -- Branchement seul par défaut
    phase_branchement_statut = CASE
        WHEN statut = 'Terminée' THEN 'terminee'
        WHEN statut = 'En cours' THEN 'en_cours'
        WHEN statut = 'Annulée' THEN 'annulee'
        ELSE 'en_attente'
    END,
    phase_terrassement_statut = 'non_applicable'
WHERE type_prestation_id IS NULL;