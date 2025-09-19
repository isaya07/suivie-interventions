<?php
/**
 * Système d'audit trail pour tracer toutes les modifications
 *
 * Fonctionnalités :
 * - Enregistrement automatique des modifications
 * - Comparaison avant/après des données
 * - Traçabilité complète des utilisateurs
 * - Support des champs sensibles Enedis
 * - Détection des actions critiques
 */

class AuditTrail {

    private $db;
    private static $instance = null;

    public function __construct($database) {
        $this->db = $database;
    }

    public static function getInstance($database) {
        if (self::$instance === null) {
            self::$instance = new AuditTrail($database);
        }
        return self::$instance;
    }

    /**
     * Enregistre une modification d'intervention électrique
     */
    public function logInterventionChange($interventionId, $userId, $action, $oldData = null, $newData = null, $comment = '') {
        try {
            // Déterminer le type d'action et sa criticité
            $actionInfo = $this->analyzeAction($action, $oldData, $newData);

            // Préparer les données pour l'historique
            $changes = $this->detectChanges($oldData, $newData);

            // Insérer dans l'historique
            $query = "INSERT INTO intervention_historique
                     (intervention_id, user_id, action, commentaire, ancien_statut, nouveau_statut, changes_data, action_type, is_critical, ip_address)
                     VALUES (:intervention_id, :user_id, :action, :commentaire, :ancien_statut, :nouveau_statut, :changes_data, :action_type, :is_critical, :ip_address)";

            $stmt = $this->db->prepare($query);

            $params = [
                ':intervention_id' => $interventionId,
                ':user_id' => $userId,
                ':action' => $action,
                ':commentaire' => $comment,
                ':ancien_statut' => $oldData['statut'] ?? null,
                ':nouveau_statut' => $newData['statut'] ?? null,
                ':changes_data' => json_encode($changes, JSON_UNESCAPED_UNICODE),
                ':action_type' => $actionInfo['type'],
                ':is_critical' => $actionInfo['is_critical'] ? 1 : 0,
                ':ip_address' => $this->getUserIP()
            ];

            $result = $stmt->execute($params);

            // Log les actions critiques séparément
            if ($actionInfo['is_critical']) {
                $this->logCriticalAction($interventionId, $userId, $action, $changes);
            }

            return $result;

        } catch (Exception $e) {
            error_log("Erreur audit trail: " . $e->getMessage());
            // Ne pas faire échouer l'opération principale
            return false;
        }
    }

    /**
     * Analyse le type d'action et détermine sa criticité
     */
    private function analyzeAction($action, $oldData, $newData) {
        $actionInfo = [
            'type' => 'modification',
            'is_critical' => false
        ];

        // Actions critiques
        $criticalActions = [
            'creation' => true,
            'suppression' => true,
            'changement_technicien' => true,
            'validation_devis' => true,
            'mise_en_service' => true,
            'annulation' => true
        ];

        // Détecter le type d'action
        if (strpos($action, 'creation') !== false) {
            $actionInfo['type'] = 'creation';
            $actionInfo['is_critical'] = true;
        } elseif (strpos($action, 'suppression') !== false) {
            $actionInfo['type'] = 'suppression';
            $actionInfo['is_critical'] = true;
        } elseif (strpos($action, 'technicien') !== false) {
            $actionInfo['type'] = 'changement_technicien';
            $actionInfo['is_critical'] = true;
        } elseif ($this->isFinancialChange($oldData, $newData)) {
            $actionInfo['type'] = 'modification_financiere';
            $actionInfo['is_critical'] = true;
        } elseif ($this->isSpecificationChange($oldData, $newData)) {
            $actionInfo['type'] = 'modification_technique';
            $actionInfo['is_critical'] = true;
        }

        return $actionInfo;
    }

    /**
     * Détecte les changements entre ancien et nouveau état
     */
    private function detectChanges($oldData, $newData) {
        if (!$oldData || !$newData) {
            return [];
        }

        $changes = [];
        $sensitiveFields = [
            // Champs de base
            'titre', 'description', 'priorite', 'statut',
            // Champs clients
            'client_nom', 'client_contact', 'client_telephone',
            // Spécifications Enedis
            'type_reglementaire', 'mode_pose',
            'longueur_liaison_reseau', 'longueur_derivation_individuelle', 'distance_raccordement',
            // Données financières
            'cout_total_estime', 'cout_total_reel', 'taux_horaire',
            // Techniciens
            'phase_terrassement_technicien_id', 'phase_branchement_technicien_id',
            // Dates critiques
            'date_validation_devis', 'date_mise_en_service'
        ];

        foreach ($sensitiveFields as $field) {
            $oldValue = $oldData[$field] ?? null;
            $newValue = $newData[$field] ?? null;

            if ($oldValue !== $newValue) {
                $changes[$field] = [
                    'ancien' => $oldValue,
                    'nouveau' => $newValue,
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            }
        }

        return $changes;
    }

    /**
     * Vérifie si c'est un changement financier
     */
    private function isFinancialChange($oldData, $newData) {
        if (!$oldData || !$newData) return false;

        $financialFields = ['cout_total_estime', 'cout_total_reel', 'taux_horaire'];

        foreach ($financialFields as $field) {
            if (($oldData[$field] ?? null) !== ($newData[$field] ?? null)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie si c'est un changement de spécification technique
     */
    private function isSpecificationChange($oldData, $newData) {
        if (!$oldData || !$newData) return false;

        $specFields = [
            'type_reglementaire', 'mode_pose',
            'longueur_liaison_reseau', 'longueur_derivation_individuelle'
        ];

        foreach ($specFields as $field) {
            if (($oldData[$field] ?? null) !== ($newData[$field] ?? null)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Log les actions critiques dans un fichier séparé
     */
    private function logCriticalAction($interventionId, $userId, $action, $changes) {
        $logFile = '../logs/critical_actions.log';

        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'intervention_id' => $interventionId,
            'user_id' => $userId,
            'action' => $action,
            'changes' => $changes,
            'ip' => $this->getUserIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ];

        $logLine = json_encode($logData, JSON_UNESCAPED_UNICODE) . "\n";

        // Créer le répertoire si nécessaire
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }

    /**
     * Récupère l'historique d'une intervention
     */
    public function getInterventionHistory($interventionId, $limit = 50) {
        $query = "SELECT h.*, u.username, u.prenom, u.nom
                 FROM intervention_historique h
                 LEFT JOIN users u ON h.user_id = u.id
                 WHERE h.intervention_id = :intervention_id
                 ORDER BY h.created_at DESC
                 LIMIT :limit";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':intervention_id', $interventionId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $history = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['changes_data'] = json_decode($row['changes_data'], true);
            $history[] = $row;
        }

        return $history;
    }

    /**
     * Récupère les actions critiques récentes
     */
    public function getCriticalActions($days = 7, $limit = 100) {
        $query = "SELECT h.*, u.username, u.prenom, u.nom, i.numero, i.titre
                 FROM intervention_historique h
                 LEFT JOIN users u ON h.user_id = u.id
                 LEFT JOIN interventions i ON h.intervention_id = i.id
                 WHERE h.is_critical = 1
                 AND h.created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                 ORDER BY h.created_at DESC
                 LIMIT :limit";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':days', $days, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Génère un rapport d'audit
     */
    public function generateAuditReport($startDate, $endDate, $interventionId = null) {
        $whereClause = "WHERE h.created_at >= :start_date AND h.created_at <= :end_date";
        $params = [
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ];

        if ($interventionId) {
            $whereClause .= " AND h.intervention_id = :intervention_id";
            $params[':intervention_id'] = $interventionId;
        }

        $query = "SELECT
                    h.*,
                    u.username,
                    u.prenom,
                    u.nom,
                    i.numero,
                    i.titre
                 FROM intervention_historique h
                 LEFT JOIN users u ON h.user_id = u.id
                 LEFT JOIN interventions i ON h.intervention_id = i.id
                 {$whereClause}
                 ORDER BY h.created_at DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Méthodes utilitaires
     */
    private function getUserIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }
    }

    /**
     * Nettoie l'historique ancien (GDPR compliance)
     */
    public function cleanOldHistory($monthsToKeep = 24) {
        $query = "DELETE FROM intervention_historique
                 WHERE created_at < DATE_SUB(NOW(), INTERVAL :months MONTH)
                 AND is_critical = 0";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':months', $monthsToKeep, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Statistiques d'audit
     */
    public function getAuditStats($days = 30) {
        $query = "SELECT
                    action_type,
                    COUNT(*) as count,
                    COUNT(CASE WHEN is_critical = 1 THEN 1 END) as critical_count
                 FROM intervention_historique
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                 GROUP BY action_type
                 ORDER BY count DESC";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':days', $days, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>