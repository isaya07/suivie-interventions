-- Schéma d'optimisation de planning d'interventions
-- Prend en compte la localisation, temps de trajet, durées et délais

USE suivi_interventions;

-- Table pour stocker les coordonnées géographiques
CREATE TABLE IF NOT EXISTS localisation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT,
    adresse_complete TEXT NOT NULL,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    ville VARCHAR(100),
    code_postal VARCHAR(10),
    region VARCHAR(100),
    pays VARCHAR(50) DEFAULT 'France',
    zone_intervention VARCHAR(50), -- Zone géographique pour regroupement
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    INDEX idx_coordinates (latitude, longitude),
    INDEX idx_zone (zone_intervention)
);

-- Table des zones de couverture des techniciens
CREATE TABLE IF NOT EXISTS technicien_zones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    technicien_id INT NOT NULL,
    zone_intervention VARCHAR(50) NOT NULL,
    rayon_action INT DEFAULT 50, -- en kilomètres
    cout_deplacement_km DECIMAL(5,2) DEFAULT 0.50, -- coût par km
    temps_deplacement_base INT DEFAULT 30, -- temps de base en minutes
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (technicien_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_technicien_zone (technicien_id, zone_intervention)
);

-- Table des créneaux de disponibilité des techniciens
CREATE TABLE IF NOT EXISTS disponibilites_technicien (
    id INT AUTO_INCREMENT PRIMARY KEY,
    technicien_id INT NOT NULL,
    date_debut DATETIME NOT NULL,
    date_fin DATETIME NOT NULL,
    type_creneau ENUM('travail', 'pause', 'deplacement', 'formation', 'conges') DEFAULT 'travail',
    est_disponible BOOLEAN DEFAULT TRUE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (technicien_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_technicien_date (technicien_id, date_debut, date_fin)
);

-- Table des temps de trajet pré-calculés (cache)
CREATE TABLE IF NOT EXISTS temps_trajet (
    id INT AUTO_INCREMENT PRIMARY KEY,
    localisation_depart_id INT,
    localisation_arrivee_id INT,
    distance_km DECIMAL(8,2),
    temps_trajet_minutes INT,
    cout_trajet DECIMAL(8,2),
    date_calcul TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_valid BOOLEAN DEFAULT TRUE, -- pour invalider les anciens calculs
    FOREIGN KEY (localisation_depart_id) REFERENCES localisation(id) ON DELETE CASCADE,
    FOREIGN KEY (localisation_arrivee_id) REFERENCES localisation(id) ON DELETE CASCADE,
    UNIQUE KEY unique_trajet (localisation_depart_id, localisation_arrivee_id),
    INDEX idx_depart (localisation_depart_id),
    INDEX idx_arrivee (localisation_arrivee_id)
);

-- Table des planning optimisés générés
CREATE TABLE IF NOT EXISTS planning_optimise (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom_planning VARCHAR(255) NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    technicien_id INT,
    algorithme_utilise VARCHAR(50) DEFAULT 'genetic_algorithm',
    score_optimisation DECIMAL(8,4), -- score de qualité du planning
    temps_calcul_ms INT, -- temps de calcul en millisecondes
    parametres_optimisation JSON, -- paramètres utilisés pour l'optimisation
    statut ENUM('brouillon', 'valide', 'applique', 'archive') DEFAULT 'brouillon',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (technicien_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_dates (date_debut, date_fin),
    INDEX idx_statut (statut)
);

-- Table des créneaux planifiés dans un planning optimisé
CREATE TABLE IF NOT EXISTS planning_creneaux (
    id INT AUTO_INCREMENT PRIMARY KEY,
    planning_id INT NOT NULL,
    intervention_id INT NOT NULL,
    technicien_id INT NOT NULL,
    date_debut DATETIME NOT NULL,
    date_fin DATETIME NOT NULL,
    temps_trajet_precedent INT DEFAULT 0, -- temps de trajet depuis l'intervention précédente
    cout_trajet_precedent DECIMAL(8,2) DEFAULT 0,
    ordre_sequence INT, -- ordre dans la journée du technicien
    priorite_calculee INT, -- priorité recalculée par l'algorithme
    efficacite_score DECIMAL(5,2), -- score d'efficacité de ce créneau
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (planning_id) REFERENCES planning_optimise(id) ON DELETE CASCADE,
    FOREIGN KEY (intervention_id) REFERENCES interventions(id) ON DELETE CASCADE,
    FOREIGN KEY (technicien_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_planning (planning_id),
    INDEX idx_technicien_date (technicien_id, date_debut),
    INDEX idx_ordre (planning_id, technicien_id, ordre_sequence)
);

-- Table des paramètres d'optimisation
CREATE TABLE IF NOT EXISTS parametres_optimisation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    poids_distance DECIMAL(3,2) DEFAULT 0.30, -- importance de la distance dans l'optimisation
    poids_temps DECIMAL(3,2) DEFAULT 0.25, -- importance du temps dans l'optimisation
    poids_priorite DECIMAL(3,2) DEFAULT 0.25, -- importance de la priorité
    poids_cout DECIMAL(3,2) DEFAULT 0.20, -- importance du coût
    duree_max_journee INT DEFAULT 480, -- durée max de travail par jour en minutes (8h)
    temps_pause_min INT DEFAULT 30, -- temps de pause minimum en minutes
    distance_max_trajet INT DEFAULT 100, -- distance max acceptable pour un trajet en km
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Ajouter des colonnes à la table interventions pour l'optimisation
ALTER TABLE interventions
ADD COLUMN IF NOT EXISTS localisation_id INT,
ADD COLUMN IF NOT EXISTS duree_estimee_minutes INT DEFAULT 120,
ADD COLUMN IF NOT EXISTS fenetre_debut DATETIME, -- fenêtre de temps acceptable pour commencer
ADD COLUMN IF NOT EXISTS fenetre_fin DATETIME, -- fenêtre de temps acceptable pour finir
ADD COLUMN IF NOT EXISTS flexibilite_planning ENUM('fixe', 'flexible', 'tres_flexible') DEFAULT 'flexible',
ADD COLUMN IF NOT EXISTS cout_retard_heure DECIMAL(8,2) DEFAULT 0, -- coût du retard par heure
ADD COLUMN IF NOT EXISTS niveau_urgence INT DEFAULT 1, -- niveau d'urgence de 1 à 10
ADD FOREIGN KEY (localisation_id) REFERENCES localisation(id) ON DELETE SET NULL;

-- Index pour optimiser les requêtes de planning
CREATE INDEX IF NOT EXISTS idx_intervention_planning ON interventions(statut, date_intervention, priorite);
CREATE INDEX IF NOT EXISTS idx_intervention_localisation ON interventions(localisation_id, statut);

-- Insertion des paramètres par défaut
INSERT INTO parametres_optimisation (nom, description, is_default) VALUES
('Optimisation équilibrée', 'Paramètres équilibrés pour la plupart des cas d''usage', TRUE),
('Priorité temps', 'Optimisation privilégiant la rapidité d''exécution', FALSE),
('Priorité coût', 'Optimisation privilégiant la réduction des coûts', FALSE),
('Priorité urgence', 'Optimisation privilégiant les interventions urgentes', FALSE)
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Mise à jour des paramètres spécialisés
UPDATE parametres_optimisation SET
    poids_distance = 0.20, poids_temps = 0.40, poids_priorite = 0.25, poids_cout = 0.15
WHERE nom = 'Priorité temps';

UPDATE parametres_optimisation SET
    poids_distance = 0.40, poids_temps = 0.20, poids_priorite = 0.15, poids_cout = 0.25
WHERE nom = 'Priorité coût';

UPDATE parametres_optimisation SET
    poids_distance = 0.15, poids_temps = 0.20, poids_priorite = 0.50, poids_cout = 0.15
WHERE nom = 'Priorité urgence';