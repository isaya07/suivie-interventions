-- ================================================================
-- SCRIPT DE NETTOYAGE ET MISE À JOUR COMPLÈTE DE LA BASE
-- Suppression des éléments obsolètes et application des nouvelles fonctionnalités
-- ================================================================

-- Application des mises à jour précédentes si pas encore fait
SOURCE types_branchements_update.sql;

-- ================================================================
-- NETTOYAGE DES ÉLÉMENTS OBSOLÈTES
-- ================================================================

-- Suppression des colonnes obsolètes devenues inutiles
ALTER TABLE interventions
DROP COLUMN IF EXISTS technicien_nom,
DROP COLUMN IF EXISTS client_nom;

-- Suppression des anciens types de prestations génériques
DELETE FROM types_prestations
WHERE code IN ('BRANCHEMENT_SEUL', 'BRANCHEMENT_TERRASSEMENT')
AND id NOT IN (SELECT DISTINCT type_prestation_id FROM interventions WHERE type_prestation_id IS NOT NULL);

-- ================================================================
-- MISE À JOUR DES UTILISATEURS AVEC SPÉCIALITÉS
-- ================================================================

-- Ajout d'utilisateurs techniciens spécialisés si pas encore existants
INSERT IGNORE INTO users (username, email, password_hash, nom, prenom, role, telephone, taux_horaire, specialite_principale) VALUES
('jdupont_terrassier', 'j.dupont@company.com', '$2y$10$example_hash_terrassier', 'Dupont', 'Jean', 'technicien', '0123456789', 38.00, 'terrassier'),
('mmartin_cableur', 'm.martin@company.com', '$2y$10$example_hash_cableur', 'Martin', 'Michel', 'technicien', '0123456790', 45.00, 'cableur'),
('pbernard_terrassier', 'p.bernard@company.com', '$2y$10$example_hash_terrassier2', 'Bernard', 'Pierre', 'technicien', '0123456791', 38.00, 'terrassier'),
('ldurand_cableur', 'l.durand@company.com', '$2y$10$example_hash_cableur2', 'Durand', 'Louis', 'technicien', '0123456792', 45.00, 'cableur'),
('admin_system', 'admin@company.com', '$2y$10$example_hash_admin', 'Admin', 'Système', 'admin', '0123456793', NULL, 'autre'),
('manager_ops', 'manager@company.com', '$2y$10$example_hash_manager', 'Manager', 'Opérations', 'manager', '0123456794', NULL, 'autre');

-- ================================================================
-- MISE À JOUR DES CLIENTS DE TEST
-- ================================================================

-- Ajout de clients de test si pas encore existants
INSERT IGNORE INTO clients (nom, email, telephone, adresse, ville, code_postal, contact_principal, notes) VALUES
('Maison Dupuis', 'dupuis@email.com', '0145678901', '123 Rue de la Paix', 'Paris', '75001', 'M. Dupuis', 'Construction neuve'),
('Villa Martin', 'martin.villa@email.com', '0156789012', '456 Avenue des Champs', 'Lyon', '69001', 'Mme Martin', 'Rénovation électrique'),
('Résidence Bernard', 'contact@residence-bernard.fr', '0167890123', '789 Boulevard Haussmann', 'Marseille', '13001', 'M. Bernard', 'Extension maison'),
('Pavillon Durand', 'durand.pavillon@email.com', '0178901234', '321 Chemin des Vignes', 'Toulouse', '31000', 'Mme Durand', 'Maison individuelle'),
('Lotissement Moreau', 'moreau.lot@email.com', '0189012345', '654 Allée des Tilleuls', 'Nantes', '44000', 'M. Moreau', 'Nouveau lotissement');

-- ================================================================
-- DONNÉES DE TEST POUR LES BRANCHEMENTS
-- ================================================================

-- Variables pour les IDs (adaptation nécessaire selon les IDs réels)
SET @client1 = (SELECT id FROM clients WHERE nom = 'Maison Dupuis' LIMIT 1);
SET @client2 = (SELECT id FROM clients WHERE nom = 'Villa Martin' LIMIT 1);
SET @client3 = (SELECT id FROM clients WHERE nom = 'Résidence Bernard' LIMIT 1);
SET @client4 = (SELECT id FROM clients WHERE nom = 'Pavillon Durand' LIMIT 1);
SET @client5 = (SELECT id FROM clients WHERE nom = 'Lotissement Moreau' LIMIT 1);

SET @terrassier1 = (SELECT id FROM users WHERE username = 'jdupont_terrassier' LIMIT 1);
SET @terrassier2 = (SELECT id FROM users WHERE username = 'pbernard_terrassier' LIMIT 1);
SET @cableur1 = (SELECT id FROM users WHERE username = 'mmartin_cableur' LIMIT 1);
SET @cableur2 = (SELECT id FROM users WHERE username = 'ldurand_cableur' LIMIT 1);
SET @admin = (SELECT id FROM users WHERE username = 'admin_system' LIMIT 1);

SET @type_souterrain_t2 = (SELECT id FROM types_prestations WHERE code = 'SOUTERRAIN_TYPE_2' LIMIT 1);
SET @type_aerien_t1 = (SELECT id FROM types_prestations WHERE code = 'AERIEN_TYPE_1' LIMIT 1);
SET @type_aerosouterrain_t2 = (SELECT id FROM types_prestations WHERE code = 'AEROSOUTERRAIN_TYPE_2' LIMIT 1);
SET @type_souterrain_boite_t1 = (SELECT id FROM types_prestations WHERE code = 'SOUTERRAIN_BOITE_TYPE_1' LIMIT 1);
SET @type_di_seule_t2 = (SELECT id FROM types_prestations WHERE code = 'DI_SEULE_TYPE_2' LIMIT 1);

-- Interventions de test avec tous les types de branchements
INSERT INTO interventions (
    numero, titre, description, client_id, createur_id,
    type_prestation_id, type_reglementaire, mode_pose,
    longueur_liaison_reseau, longueur_derivation_individuelle, distance_raccordement,
    phase_branchement_technicien_id, phase_terrassement_technicien_id,
    phase_branchement_statut, phase_terrassement_statut,
    date_reception_dossier, date_etude_technique, date_validation_devis,
    priorite, statut,
    date_creation
) VALUES

-- Branchement Souterrain Type 2 - En cours
('BR-2024-001', 'Branchement souterrain - Maison Dupuis', 'Branchement souterrain Type 2 avec terrassement complet',
 @client1, @admin, @type_souterrain_t2, 'type_2', 'souterrain',
 45, 35, 80, @cableur1, @terrassier1, 'en_attente', 'en_cours',
 '2024-01-15', '2024-01-18', '2024-01-22', 'normale', 'En cours',
 '2024-01-15 09:00:00'),

-- Branchement Aérien Type 1 - Terminé
('BR-2024-002', 'Branchement aérien - Villa Martin', 'Branchement aérien Type 1 simple et rapide',
 @client2, @admin, @type_aerien_t1, 'type_1', 'aerien',
 20, 15, 35, @cableur2, NULL, 'terminee', 'non_applicable',
 '2024-01-10', '2024-01-12', '2024-01-15', 'haute', 'Terminée',
 '2024-01-10 14:30:00'),

-- Branchement Aérosouterrain Type 2 - En attente
('BR-2024-003', 'Branchement aérosouterrain - Résidence Bernard', 'Transition aérien vers souterrain avec terrassement partiel',
 @client3, @admin, @type_aerosouterrain_t2, 'type_2', 'aerosouterrain',
 30, 40, 70, @cableur1, @terrassier2, 'en_attente', 'en_attente',
 '2024-01-20', '2024-01-23', NULL, 'normale', 'En attente',
 '2024-01-20 11:15:00'),

-- Branchement Souterrain sur Boîte Type 1 - En cours
('BR-2024-004', 'Branchement sur boîte - Pavillon Durand', 'Raccordement depuis boîte de dérivation existante',
 @client4, @admin, @type_souterrain_boite_t1, 'type_1', 'souterrain_boite',
 25, 28, 53, @cableur2, @terrassier1, 'en_cours', 'terminee',
 '2024-01-25', '2024-01-27', '2024-01-30', 'basse', 'En cours',
 '2024-01-25 16:45:00'),

-- DI Seule Type 2 - Terminé
('BR-2024-005', 'DI Seule - Lotissement Moreau', 'Dérivation individuelle seule en préparation futur raccordement',
 @client5, @admin, @type_di_seule_t2, 'type_2', 'di_seule',
 0, 45, 45, @cableur1, @terrassier2, 'terminee', 'terminee',
 '2024-01-05', '2024-01-08', '2024-01-10', 'urgente', 'Terminée',
 '2024-01-05 08:00:00');

-- ================================================================
-- SESSIONS DE TRAVAIL DE TEST
-- ================================================================

-- Sessions pour l'intervention BR-2024-002 (terminée)
INSERT INTO intervention_sessions_travail (intervention_id, phase, technicien_id, debut, fin, duree_minutes, notes) VALUES
((SELECT id FROM interventions WHERE numero = 'BR-2024-002'), 'branchement', @cableur2, '2024-01-16 08:00:00', '2024-01-16 11:30:00', 210, 'Installation branchement aérien sans difficultés'),
((SELECT id FROM interventions WHERE numero = 'BR-2024-002'), 'branchement', @cableur2, '2024-01-16 13:00:00', '2024-01-16 15:00:00', 120, 'Tests et mise en service');

-- Sessions pour l'intervention BR-2024-005 (terminée)
INSERT INTO intervention_sessions_travail (intervention_id, phase, technicien_id, debut, fin, duree_minutes, notes) VALUES
((SELECT id FROM interventions WHERE numero = 'BR-2024-005'), 'terrassement', @terrassier2, '2024-01-12 08:00:00', '2024-01-12 12:00:00', 240, 'Terrassement pour DI, terrain difficile'),
((SELECT id FROM interventions WHERE numero = 'BR-2024-005'), 'branchement', @cableur1, '2024-01-13 09:00:00', '2024-01-13 11:30:00', 150, 'Pose DI et préparation raccordement futur');

-- Sessions en cours pour BR-2024-001
INSERT INTO intervention_sessions_travail (intervention_id, phase, technicien_id, debut, fin, duree_minutes, notes) VALUES
((SELECT id FROM interventions WHERE numero = 'BR-2024-001'), 'terrassement', @terrassier1, '2024-01-25 08:00:00', '2024-01-25 16:30:00', 510, 'Terrassement principal, sol argileux'),
((SELECT id FROM interventions WHERE numero = 'BR-2024-001'), 'terrassement', @terrassier1, '2024-01-26 08:00:00', '2024-01-26 12:00:00', 240, 'Finition terrassement et pose fourreaux');

-- Sessions en cours pour BR-2024-004
INSERT INTO intervention_sessions_travail (intervention_id, phase, technicien_id, debut, fin, duree_minutes, notes) VALUES
((SELECT id FROM interventions WHERE numero = 'BR-2024-004'), 'terrassement', @terrassier1, '2024-02-01 08:00:00', '2024-02-01 12:30:00', 270, 'Terrassement jusqu\'à la boîte existante'),
((SELECT id FROM interventions WHERE numero = 'BR-2024-004'), 'branchement', @cableur2, '2024-02-02 09:00:00', NULL, NULL, 'Branchement en cours - démarré ce matin');

-- ================================================================
-- MISE À JOUR DES CALCULS ET DATES
-- ================================================================

-- Mise à jour des dates de réalisation pour les interventions terminées
UPDATE interventions SET
    date_realisation_terrassement = '2024-01-13',
    date_realisation_cablage = '2024-01-13',
    date_mise_en_service = '2024-01-13'
WHERE numero = 'BR-2024-005';

UPDATE interventions SET
    date_realisation_cablage = '2024-01-16',
    date_mise_en_service = '2024-01-16'
WHERE numero = 'BR-2024-002';

UPDATE interventions SET
    date_realisation_terrassement = '2024-02-01'
WHERE numero = 'BR-2024-004';

UPDATE interventions SET
    date_realisation_terrassement = '2024-01-26'
WHERE numero = 'BR-2024-001';

-- Forcer le recalcul des durées et coûts via les triggers
UPDATE interventions SET
    phase_branchement_duree_heures = 5.5,
    phase_branchement_taux_horaire = 45.00,
    cout_total_estime = 247.50
WHERE numero = 'BR-2024-002';

UPDATE interventions SET
    phase_terrassement_duree_heures = 8.0,
    phase_terrassement_taux_horaire = 38.00,
    phase_branchement_duree_heures = 2.5,
    phase_branchement_taux_horaire = 45.00,
    cout_total_estime = 416.50
WHERE numero = 'BR-2024-005';

-- ================================================================
-- VÉRIFICATIONS ET NETTOYAGE FINAL
-- ================================================================

-- Suppression des données de test obsolètes ou incohérentes
DELETE FROM intervention_sessions_travail
WHERE intervention_id NOT IN (SELECT id FROM interventions);

-- Mise à jour des numéros d'intervention manquants
UPDATE interventions
SET numero = CONCAT('BR-', YEAR(date_creation), '-', LPAD(id, 3, '0'))
WHERE numero IS NULL;

-- Vérification des contraintes de cohérence
UPDATE interventions
SET phase_terrassement_statut = 'non_applicable'
WHERE type_prestation_id IN (
    SELECT id FROM types_prestations WHERE has_terrassement = FALSE
) AND phase_terrassement_statut != 'non_applicable';

-- ================================================================
-- RAPPORT DE MISE À JOUR
-- ================================================================

SELECT
    'RAPPORT DE MISE À JOUR' as section,
    COUNT(*) as total_interventions,
    SUM(CASE WHEN type_prestation_id IS NOT NULL THEN 1 ELSE 0 END) as avec_nouveau_type,
    SUM(CASE WHEN date_reception_dossier IS NOT NULL THEN 1 ELSE 0 END) as avec_suivi_dates
FROM interventions

UNION ALL

SELECT
    'TYPES DE PRESTATIONS' as section,
    COUNT(*) as total,
    SUM(CASE WHEN has_terrassement = TRUE THEN 1 ELSE 0 END) as avec_terrassement,
    SUM(CASE WHEN type_reglementaire = 'type_2' THEN 1 ELSE 0 END) as type_2
FROM types_prestations

UNION ALL

SELECT
    'SESSIONS DE TRAVAIL' as section,
    COUNT(*) as total,
    COUNT(DISTINCT intervention_id) as interventions_avec_sessions,
    SUM(CASE WHEN fin IS NULL THEN 1 ELSE 0 END) as sessions_en_cours
FROM intervention_sessions_travail;