-- ================================================================
-- MISE À JOUR DES TYPES DE BRANCHEMENTS AVEC TYPOLOGIE COMPLÈTE
-- Ajout des types réglementaires Enedis et modes de pose
-- ================================================================

-- Extension de la table types_prestations avec nouveaux champs
ALTER TABLE types_prestations
ADD COLUMN IF NOT EXISTS type_reglementaire ENUM('type_1', 'type_2', 'non_applicable') DEFAULT 'non_applicable' COMMENT 'Type réglementaire Enedis',
ADD COLUMN IF NOT EXISTS mode_pose ENUM('aerien', 'souterrain', 'aerosouterrain', 'souterrain_boite', 'di_seule') DEFAULT 'souterrain' COMMENT 'Mode de pose du branchement',
ADD COLUMN IF NOT EXISTS distance_metres INT DEFAULT NULL COMMENT 'Distance de raccordement en mètres',
ADD COLUMN IF NOT EXISTS materiel_specifique TEXT DEFAULT NULL COMMENT 'Matériel spécifique requis';

-- Mise à jour des prestations existantes
UPDATE types_prestations SET
    mode_pose = 'souterrain',
    type_reglementaire = 'type_2'
WHERE code = 'BRANCHEMENT_TERRASSEMENT';

UPDATE types_prestations SET
    mode_pose = 'aerien',
    type_reglementaire = 'type_1'
WHERE code = 'BRANCHEMENT_SEUL';

-- Ajout des nouveaux types de branchements selon la typologie
INSERT INTO types_prestations (nom, code, duree_branchement_heures, duree_terrassement_heures, has_terrassement, type_reglementaire, mode_pose, description) VALUES

-- Branchements aériens
('Branchement Aérien Type 1', 'AERIEN_TYPE_1', 3.00, NULL, FALSE, 'type_1', 'aerien', 'Branchement aérien < 30m, compteur intérieur'),
('Branchement Aérien Type 2', 'AERIEN_TYPE_2', 3.50, NULL, FALSE, 'type_2', 'aerien', 'Branchement aérien > 30m, coffret limite propriété'),

-- Branchements souterrains
('Branchement Souterrain Type 1', 'SOUTERRAIN_TYPE_1', 4.00, 4.00, TRUE, 'type_1', 'souterrain', 'Branchement souterrain < 30m, compteur intérieur'),
('Branchement Souterrain Type 2', 'SOUTERRAIN_TYPE_2', 4.00, 4.50, TRUE, 'type_2', 'souterrain', 'Branchement souterrain > 30m, coffret limite propriété'),

-- Branchements aérosouterrains
('Branchement Aérosouterrain Type 1', 'AEROSOUTERRAIN_TYPE_1', 3.50, 2.50, TRUE, 'type_1', 'aerosouterrain', 'Aérosouterrain < 30m, terrassement partiel'),
('Branchement Aérosouterrain Type 2', 'AEROSOUTERRAIN_TYPE_2', 3.50, 3.00, TRUE, 'type_2', 'aerosouterrain', 'Aérosouterrain > 30m, terrassement partiel + coffret'),

-- Branchements souterrains sur boîte
('Souterrain sur Boîte Type 1', 'SOUTERRAIN_BOITE_TYPE_1', 4.50, 3.00, TRUE, 'type_1', 'souterrain_boite', 'Raccordement depuis boîte existante < 30m'),
('Souterrain sur Boîte Type 2', 'SOUTERRAIN_BOITE_TYPE_2', 4.50, 3.50, TRUE, 'type_2', 'souterrain_boite', 'Raccordement depuis boîte existante > 30m'),

-- DI Seule (Dérivation Individuelle)
('DI Seule Type 1', 'DI_SEULE_TYPE_1', 2.00, 2.00, TRUE, 'type_1', 'di_seule', 'Dérivation individuelle seule < 30m'),
('DI Seule Type 2', 'DI_SEULE_TYPE_2', 2.00, 3.00, TRUE, 'type_2', 'di_seule', 'Dérivation individuelle seule > 30m');

-- Extension de la table interventions pour les nouveaux champs
ALTER TABLE interventions
ADD COLUMN IF NOT EXISTS type_reglementaire ENUM('type_1', 'type_2') DEFAULT NULL COMMENT 'Type réglementaire Enedis choisi',
ADD COLUMN IF NOT EXISTS mode_pose ENUM('aerien', 'souterrain', 'aerosouterrain', 'souterrain_boite', 'di_seule') DEFAULT NULL COMMENT 'Mode de pose choisi',
ADD COLUMN IF NOT EXISTS distance_raccordement INT DEFAULT NULL COMMENT 'Distance de raccordement estimée en mètres',
ADD COLUMN IF NOT EXISTS materiel_specifique_requis TEXT DEFAULT NULL COMMENT 'Matériel spécifique requis pour cette intervention',

-- Longueurs spécifiques des liaisons
ADD COLUMN IF NOT EXISTS longueur_liaison_reseau INT DEFAULT NULL COMMENT 'Longueur Liaison Réseau (LR) en mètres',
ADD COLUMN IF NOT EXISTS longueur_derivation_individuelle INT DEFAULT NULL COMMENT 'Longueur Dérivation Individuelle (DI) en mètres',

-- Dates de suivi du processus
ADD COLUMN IF NOT EXISTS date_reception_dossier DATE DEFAULT NULL COMMENT 'Date de réception du dossier client',
ADD COLUMN IF NOT EXISTS date_etude_technique DATE DEFAULT NULL COMMENT 'Date d\'étude technique',
ADD COLUMN IF NOT EXISTS date_validation_devis DATE DEFAULT NULL COMMENT 'Date de validation du devis par le client',
ADD COLUMN IF NOT EXISTS date_realisation_terrassement DATE DEFAULT NULL COMMENT 'Date de réalisation du terrassement',
ADD COLUMN IF NOT EXISTS date_realisation_cablage DATE DEFAULT NULL COMMENT 'Date de réalisation du câblage',
ADD COLUMN IF NOT EXISTS date_mise_en_service DATE DEFAULT NULL COMMENT 'Date de mise en service finale';

-- Index pour les nouveaux champs
ALTER TABLE interventions
ADD INDEX IF NOT EXISTS idx_type_reglementaire (type_reglementaire),
ADD INDEX IF NOT EXISTS idx_mode_pose (mode_pose),
ADD INDEX IF NOT EXISTS idx_date_reception_dossier (date_reception_dossier),
ADD INDEX IF NOT EXISTS idx_date_mise_en_service (date_mise_en_service);

-- Mise à jour de la vue pour inclure les nouveaux champs
DROP VIEW IF EXISTS v_interventions_completes;
CREATE VIEW v_interventions_completes AS
SELECT
    i.*,
    tp.nom as type_prestation_nom,
    tp.code as type_prestation_code,
    tp.has_terrassement,
    tp.type_reglementaire as type_reglementaire_prestation,
    tp.mode_pose as mode_pose_prestation,

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
    END as statut_global,

    -- Libellé complet du type
    CONCAT(
        tp.nom,
        CASE
            WHEN i.distance_raccordement IS NOT NULL THEN CONCAT(' (', i.distance_raccordement, 'm)')
            ELSE ''
        END
    ) as type_complet,

    -- Calculs des délais et indicateurs
    CASE
        WHEN i.date_reception_dossier IS NOT NULL AND i.date_etude_technique IS NOT NULL
        THEN DATEDIFF(i.date_etude_technique, i.date_reception_dossier)
        ELSE NULL
    END as delai_reception_etude_jours,

    CASE
        WHEN i.date_etude_technique IS NOT NULL AND i.date_realisation_terrassement IS NOT NULL
        THEN DATEDIFF(i.date_realisation_terrassement, i.date_etude_technique)
        ELSE NULL
    END as delai_etude_terrassement_jours,

    CASE
        WHEN i.date_realisation_terrassement IS NOT NULL AND i.date_realisation_cablage IS NOT NULL
        THEN DATEDIFF(i.date_realisation_cablage, i.date_realisation_terrassement)
        WHEN i.date_etude_technique IS NOT NULL AND i.date_realisation_cablage IS NOT NULL AND i.date_realisation_terrassement IS NULL
        THEN DATEDIFF(i.date_realisation_cablage, i.date_etude_technique)
        ELSE NULL
    END as delai_terrassement_cablage_jours,

    CASE
        WHEN i.date_reception_dossier IS NOT NULL AND i.date_mise_en_service IS NOT NULL
        THEN DATEDIFF(i.date_mise_en_service, i.date_reception_dossier)
        ELSE NULL
    END as delai_total_jours

FROM interventions i
LEFT JOIN types_prestations tp ON i.type_prestation_id = tp.id
LEFT JOIN users ub ON i.phase_branchement_technicien_id = ub.id
LEFT JOIN users ut ON i.phase_terrassement_technicien_id = ut.id
LEFT JOIN clients c ON i.client_id = c.id;

-- Requête pour vérifier les types créés
SELECT
    id,
    nom,
    code,
    type_reglementaire,
    mode_pose,
    duree_branchement_heures,
    duree_terrassement_heures,
    has_terrassement,
    description
FROM types_prestations
ORDER BY type_reglementaire, mode_pose, nom;