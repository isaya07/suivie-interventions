-- Base de données étendue avec utilisateurs et gestion des fichiers
CREATE DATABASE IF NOT EXISTS suivi_interventions;
USE suivi_interventions;

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    role ENUM('admin', 'technicien', 'manager', 'client') DEFAULT 'technicien',
    telephone VARCHAR(20),
    specialite VARCHAR(255),
    avatar VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des clients (étendue)
CREATE TABLE IF NOT EXISTS clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    telephone VARCHAR(20),
    adresse TEXT,
    ville VARCHAR(100),
    code_postal VARCHAR(10),
    contact_principal VARCHAR(255),
    notes TEXT,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Table des interventions (mise à jour)
CREATE TABLE IF NOT EXISTS interventions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(20) UNIQUE,
    titre VARCHAR(255) NOT NULL,
    description TEXT,
    client_id INT,
    client_nom VARCHAR(255), -- Pour compatibilité ascendante
    technicien_id INT,
    technicien_nom VARCHAR(255), -- Pour compatibilité ascendante
    createur_id INT,
    statut ENUM('En attente', 'En cours', 'En pause', 'Terminée', 'Annulée') DEFAULT 'En attente',
    priorite ENUM('Basse', 'Normale', 'Haute', 'Urgente') DEFAULT 'Normale',
    type_intervention ENUM('Maintenance', 'Réparation', 'Installation', 'Diagnostic', 'Autre') DEFAULT 'Réparation',
    temps_estime INT, -- en minutes
    temps_reel INT, -- en minutes
    cout_prevu DECIMAL(10,2),
    cout_reel DECIMAL(10,2),
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_intervention DATETIME,
    date_fin DATETIME,
    date_limite DATETIME,
    notes_technicien TEXT,
    satisfaction_client INT, -- Note de 1 à 5
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
    FOREIGN KEY (technicien_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (createur_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Table des fichiers
CREATE TABLE IF NOT EXISTS fichiers_intervention (
    id INT AUTO_INCREMENT PRIMARY KEY,
    intervention_id INT NOT NULL,
    nom_fichier VARCHAR(255) NOT NULL,
    nom_original VARCHAR(255) NOT NULL,
    chemin_fichier VARCHAR(500) NOT NULL,
    type_mime VARCHAR(100),
    taille INT,
    type_fichier ENUM('photo_avant', 'photo_apres', 'document', 'facture', 'devis', 'autre') DEFAULT 'autre',
    description TEXT,
    uploaded_by INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (intervention_id) REFERENCES interventions(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Table des commentaires/historique
CREATE TABLE IF NOT EXISTS intervention_historique (
    id INT AUTO_INCREMENT PRIMARY KEY,
    intervention_id INT NOT NULL,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    commentaire TEXT,
    ancien_statut VARCHAR(50),
    nouveau_statut VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (intervention_id) REFERENCES interventions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Table des sessions
CREATE TABLE IF NOT EXISTS user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Triggers pour générer automatiquement le numéro d'intervention
DELIMITER //
CREATE TRIGGER before_intervention_insert 
    BEFORE INSERT ON interventions
    FOR EACH ROW 
BEGIN
    IF NEW.numero IS NULL THEN
        SET NEW.numero = CONCAT('INT-', YEAR(NOW()), LPAD(MONTH(NOW()), 2, '0'), '-', 
                               LPAD((SELECT COALESCE(MAX(CAST(SUBSTRING(numero, -4) AS UNSIGNED)), 0) + 1 
                                     FROM interventions 
                                     WHERE numero LIKE CONCAT('INT-', YEAR(NOW()), LPAD(MONTH(NOW()), 2, '0'), '-%')), 4, '0'));
    END IF;
END//
DELIMITER ;

-- Index pour optimiser les performances
CREATE INDEX idx_interventions_statut ON interventions(statut);
CREATE INDEX idx_interventions_priorite ON interventions(priorite);
CREATE INDEX idx_interventions_technicien ON interventions(technicien_id);
CREATE INDEX idx_interventions_client ON interventions(client_id);
CREATE INDEX idx_interventions_date_intervention ON interventions(date_intervention);
CREATE INDEX idx_interventions_numero ON interventions(numero);
CREATE INDEX idx_fichiers_intervention ON fichiers_intervention(intervention_id);
CREATE INDEX idx_historique_intervention ON intervention_historique(intervention_id);
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_sessions_user_id ON user_sessions(user_id);
CREATE INDEX idx_sessions_expires ON user_sessions(expires_at);

-- Insertion des utilisateurs de test
INSERT INTO users (username, email, password_hash, nom, prenom, role, telephone, specialite) VALUES
('admin', 'admin@exemple.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrateur', 'Système', 'admin', '01.23.45.67.89', 'Administration'),
('jean.dupont', 'jean.dupont@exemple.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dupont', 'Jean', 'technicien', '01.23.45.67.90', 'Climatisation, Chauffage'),
('marie.durand', 'marie.durand@exemple.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Durand', 'Marie', 'technicien', '01.23.45.67.91', 'Réseau, Informatique'),
('pierre.bernard', 'pierre.bernard@exemple.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Bernard', 'Pierre', 'manager', '01.23.45.67.92', 'Management, Coordination'),
('client.test', 'client@exemple.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test', 'Client', 'client', '01.23.45.67.93', NULL);

-- Insertion des clients de test
INSERT INTO clients (nom, email, telephone, adresse, ville, code_postal, contact_principal) VALUES
('Entreprise ABC', 'contact@entreprise-abc.com', '01.11.22.33.44', '123 Rue de la Paix', 'Paris', '75001', 'M. Martin'),
('SARL Martin Industrie', 'info@martin-industrie.com', '01.55.66.77.88', '456 Avenue des Champs', 'Lyon', '69000', 'Mme Dubois'),
('SAS Techno Solutions', 'support@techno-solutions.com', '01.99.88.77.66', '789 Boulevard Tech', 'Marseille', '13000', 'M. Leroy'),
('PME Innovation', 'admin@pme-innovation.fr', '01.44.55.66.77', '321 Rue Innovation', 'Toulouse', '31000', 'Mme Garcia');

-- Mise à jour des interventions existantes avec les nouvelles relations
UPDATE interventions SET 
    client_id = 1, 
    technicien_id = (SELECT id FROM users WHERE username = 'jean.dupont'),
    createur_id = (SELECT id FROM users WHERE username = 'admin'),
    type_intervention = 'Réparation',
    temps_estime = 120
WHERE id = 1;

UPDATE interventions SET 
    client_id = 2, 
    technicien_id = (SELECT id FROM users WHERE username = 'marie.durand'),
    createur_id = (SELECT id FROM users WHERE username = 'admin'),
    type_intervention = 'Installation',
    temps_estime = 240
WHERE id = 2;

UPDATE interventions SET 
    client_id = 3, 
    technicien_id = (SELECT id FROM users WHERE username = 'pierre.bernard'),
    createur_id = (SELECT id FROM users WHERE username = 'admin'),
    type_intervention = 'Maintenance',
    temps_estime = 60,
    temps_reel = 75
WHERE id = 3;

-- Ajout d'historique pour les interventions existantes
INSERT INTO intervention_historique (intervention_id, user_id, action, commentaire, nouveau_statut) VALUES
(1, 1, 'creation', 'Intervention créée', 'En attente'),
(2, 1, 'creation', 'Intervention créée', 'En cours'),
(3, 1, 'creation', 'Intervention créée', 'Terminée'),
(3, 3, 'completion', 'Maintenance préventive effectuée avec succès', 'Terminée');

-- Vues utiles pour les rapports
CREATE VIEW v_interventions_completes AS
SELECT 
    i.*,
    c.nom as client_nom_complet,
    c.email as client_email,
    c.telephone as client_telephone,
    CONCAT(u.prenom, ' ', u.nom) as technicien_nom_complet,
    u.email as technicien_email,
    u.specialite as technicien_specialite,
    CONCAT(cr.prenom, ' ', cr.nom) as createur_nom,
    (SELECT COUNT(*) FROM fichiers_intervention WHERE intervention_id = i.id) as nb_fichiers
FROM interventions i
LEFT JOIN clients c ON i.client_id = c.id
LEFT JOIN users u ON i.technicien_id = u.id
LEFT JOIN users cr ON i.createur_id = cr.id;

-- Vue pour les statistiques
CREATE VIEW v_statistiques_interventions AS
SELECT 
    COUNT(*) as total_interventions,
    SUM(CASE WHEN statut = 'En attente' THEN 1 ELSE 0 END) as en_attente,
    SUM(CASE WHEN statut = 'En cours' THEN 1 ELSE 0 END) as en_cours,
    SUM(CASE WHEN statut = 'Terminée' THEN 1 ELSE 0 END) as terminees,
    SUM(CASE WHEN statut = 'Annulée' THEN 1 ELSE 0 END) as annulees,
    AVG(CASE WHEN temps_reel IS NOT NULL THEN temps_reel END) as temps_moyen,
    SUM(CASE WHEN date_limite < NOW() AND statut NOT IN ('Terminée', 'Annulée') THEN 1 ELSE 0 END) as en_retard
FROM interventions;

-- Permissions par défaut : mot de passe = "password" hashé
-- En production, utilisez toujours des mots de passe forts et uniques !