-- Mise à jour de la table intervention_historique pour un audit trail complet
-- Ajout des colonnes pour un système d'audit avancé

ALTER TABLE intervention_historique
ADD COLUMN IF NOT EXISTS changes_data JSON COMMENT 'Détail des changements au format JSON',
ADD COLUMN IF NOT EXISTS action_type VARCHAR(50) DEFAULT 'modification' COMMENT 'Type d\'action: creation, modification, suppression, etc.',
ADD COLUMN IF NOT EXISTS is_critical BOOLEAN DEFAULT FALSE COMMENT 'Indique si l\'action est critique',
ADD COLUMN IF NOT EXISTS ip_address VARCHAR(45) COMMENT 'Adresse IP de l\'utilisateur',
ADD COLUMN IF NOT EXISTS user_agent TEXT COMMENT 'User agent du navigateur',
ADD INDEX IF NOT EXISTS idx_historique_action_type (action_type),
ADD INDEX IF NOT EXISTS idx_historique_critical (is_critical),
ADD INDEX IF NOT EXISTS idx_historique_date (created_at);

-- Table pour les actions critiques (backup de sécurité)
CREATE TABLE IF NOT EXISTS audit_critical_actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    intervention_id INT NOT NULL,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    changes_data JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (intervention_id) REFERENCES interventions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Trigger pour backup automatique des actions critiques
DELIMITER //
CREATE TRIGGER IF NOT EXISTS backup_critical_actions
    AFTER INSERT ON intervention_historique
    FOR EACH ROW
BEGIN
    IF NEW.is_critical = 1 THEN
        INSERT INTO audit_critical_actions
        (intervention_id, user_id, action, changes_data, ip_address, user_agent, created_at)
        VALUES
        (NEW.intervention_id, NEW.user_id, NEW.action, NEW.changes_data, NEW.ip_address, NEW.user_agent, NEW.created_at);
    END IF;
END//
DELIMITER ;

-- Vue pour les statistiques d'audit
CREATE OR REPLACE VIEW audit_statistics AS
SELECT
    DATE(created_at) as date_action,
    action_type,
    COUNT(*) as total_actions,
    COUNT(CASE WHEN is_critical = 1 THEN 1 END) as critical_actions,
    COUNT(DISTINCT user_id) as unique_users,
    COUNT(DISTINCT intervention_id) as interventions_affected
FROM intervention_historique
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(created_at), action_type
ORDER BY date_action DESC, total_actions DESC;

-- Vue pour l'historique détaillé avec noms d'utilisateurs
CREATE OR REPLACE VIEW audit_detailed_history AS
SELECT
    h.id,
    h.intervention_id,
    i.numero as intervention_numero,
    i.titre as intervention_titre,
    h.user_id,
    CONCAT(u.prenom, ' ', u.nom) as user_name,
    u.username,
    h.action,
    h.action_type,
    h.is_critical,
    h.commentaire,
    h.ancien_statut,
    h.nouveau_statut,
    h.changes_data,
    h.ip_address,
    h.created_at
FROM intervention_historique h
LEFT JOIN users u ON h.user_id = u.id
LEFT JOIN interventions i ON h.intervention_id = i.id
ORDER BY h.created_at DESC;

-- Index composites pour les requêtes d'audit fréquentes
CREATE INDEX IF NOT EXISTS idx_historique_user_date ON intervention_historique(user_id, created_at);
CREATE INDEX IF NOT EXISTS idx_historique_intervention_date ON intervention_historique(intervention_id, created_at);
CREATE INDEX IF NOT EXISTS idx_historique_critical_date ON intervention_historique(is_critical, created_at);

-- Procédure de nettoyage de l'historique (GDPR compliance)
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS CleanOldAuditHistory(IN months_to_keep INT)
BEGIN
    -- Nettoyer les actions non-critiques anciennes
    DELETE FROM intervention_historique
    WHERE created_at < DATE_SUB(NOW(), INTERVAL months_to_keep MONTH)
    AND is_critical = 0;

    -- Archiver les actions critiques très anciennes (plus de 5 ans)
    -- En production, cela devrait être exporté vers un système d'archivage
    SELECT COUNT(*) as archived_records
    FROM intervention_historique
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 60 MONTH)
    AND is_critical = 1;
END//
DELIMITER ;

-- Procédure pour générer un hash d'intégrité des données critiques
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS GenerateIntegrityHash(IN intervention_id INT)
BEGIN
    DECLARE integrity_hash VARCHAR(64);

    SELECT SHA2(CONCAT(
        COALESCE(titre, ''),
        COALESCE(client_nom, ''),
        COALESCE(type_reglementaire, ''),
        COALESCE(mode_pose, ''),
        COALESCE(cout_total_reel, 0),
        COALESCE(date_mise_en_service, '')
    ), 256) INTO integrity_hash
    FROM interventions
    WHERE id = intervention_id;

    -- Enregistrer le hash pour vérification future
    INSERT INTO intervention_historique
    (intervention_id, user_id, action, commentaire, action_type, is_critical)
    VALUES
    (intervention_id, NULL, 'integrity_check', integrity_hash, 'verification', 1);
END//
DELIMITER ;

-- Création d'un utilisateur système pour les actions automatiques
INSERT IGNORE INTO users (username, email, password_hash, role, prenom, nom, is_active)
VALUES ('system_audit', 'system@audit.internal', '', 'system', 'Système', 'Audit', 1);