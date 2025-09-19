<?php
/**
 * API de gestion des backups automatiques
 *
 * Endpoints :
 * - POST /backup.php?action=full : Lancer un backup complet
 * - GET /backup.php?action=list : Lister les backups disponibles
 * - POST /backup.php?action=restore : Restaurer un backup
 * - GET /backup.php?action=status : Statut du système de backup
 * - POST /backup.php?action=schedule : Configurer la planification
 */

// Configuration CORS et en-têtes
include_once 'cors.php';
require_once '../config/env.php';
header("Content-Type: application/json; charset=UTF-8");

// Import des dépendances
include_once '../config/database.php';
include_once '../config/auth.php';
include_once '../utils/BackupSystem.php';
include_once '../utils/ErrorHandler.php';

// Initialisation des services
$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    ErrorHandler::handleServerError("Erreur de connexion à la base de données");
}

$auth = new Auth($db);
$backupSystem = new BackupSystem($db);

// Vérification de l'authentification et des permissions admin
if (!$auth->isAuthenticated()) {
    ErrorHandler::handleAuthError();
}

$current_user = $auth->getCurrentUser();
if ($current_user['role'] !== 'admin') {
    ErrorHandler::handleError(403, "Accès refusé - Permissions administrateur requises");
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Routage des endpoints
switch($method . '_' . $action) {
    /**
     * POST /backup.php?action=full
     * Lance un backup complet du système
     */
    case 'POST_full':
        try {
            $result = $backupSystem->performFullBackup();

            if ($result['success']) {
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Backup complet réalisé avec succès',
                    'data' => $result
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Échec du backup complet',
                    'error' => $result['error'] ?? 'Erreur inconnue'
                ]);
            }

        } catch (Exception $e) {
            ErrorHandler::handleServerError("Erreur lors du backup: " . $e->getMessage());
        }
        break;

    /**
     * GET /backup.php?action=list
     * Liste tous les backups disponibles
     */
    case 'GET_list':
        try {
            $backups = $backupSystem->listBackups();

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $backups,
                'total' => count($backups)
            ]);

        } catch (Exception $e) {
            ErrorHandler::handleServerError("Erreur lors de la récupération des backups: " . $e->getMessage());
        }
        break;

    /**
     * POST /backup.php?action=restore
     * Restaure un backup spécifique
     * Body: { "backup_id": "2024-01-15_14-30-00_full" }
     */
    case 'POST_restore':
        $data = json_decode(file_get_contents("php://input"));

        if (!$data || !isset($data->backup_id)) {
            ErrorHandler::handleError(400, "ID de backup requis");
        }

        try {
            $result = $backupSystem->restoreBackup($data->backup_id);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Backup restauré avec succès',
                'data' => $result
            ]);

        } catch (Exception $e) {
            ErrorHandler::handleServerError("Erreur lors de la restauration: " . $e->getMessage());
        }
        break;

    /**
     * GET /backup.php?action=status
     * Retourne le statut du système de backup
     */
    case 'GET_status':
        try {
            $backups = $backupSystem->listBackups();
            $lastBackup = !empty($backups) ? $backups[0] : null;

            $backupDir = dirname(__DIR__) . '/backups';
            $logFile = dirname(__DIR__) . '/logs/backup.log';

            $status = [
                'system_operational' => is_dir($backupDir) && is_writable($backupDir),
                'last_backup' => $lastBackup,
                'total_backups' => count($backups),
                'backup_directory' => $backupDir,
                'backup_directory_size' => $this->getDirectorySize($backupDir),
                'log_file_exists' => file_exists($logFile),
                'disk_space_available' => disk_free_space($backupDir),
                'recommendations' => []
            ];

            // Ajouter des recommandations
            if (!$lastBackup) {
                $status['recommendations'][] = "Aucun backup trouvé - effectuer un backup initial";
            } elseif (strtotime($lastBackup['timestamp']) < strtotime('-7 days')) {
                $status['recommendations'][] = "Dernier backup ancien - effectuer un nouveau backup";
            }

            if ($status['disk_space_available'] < 1073741824) { // < 1GB
                $status['recommendations'][] = "Espace disque faible - nettoyer les anciens backups";
            }

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $status
            ]);

        } catch (Exception $e) {
            ErrorHandler::handleServerError("Erreur lors de la récupération du statut: " . $e->getMessage());
        }
        break;

    /**
     * POST /backup.php?action=schedule
     * Active la planification automatique des backups
     * Body: { "schedule": "daily|weekly|monthly", "enabled": true }
     */
    case 'POST_schedule':
        $data = json_decode(file_get_contents("php://input"));

        if (!$data) {
            ErrorHandler::handleError(400, "Données de planification requises");
        }

        try {
            $scheduleFile = dirname(__DIR__) . '/config/backup_schedule.json';
            $config = [
                'enabled' => $data->enabled ?? true,
                'schedule' => $data->schedule ?? 'daily',
                'last_updated' => date('Y-m-d H:i:s'),
                'updated_by' => $current_user['id']
            ];

            file_put_contents($scheduleFile, json_encode($config, JSON_PRETTY_PRINT));

            // Créer/mettre à jour le cron job (exemple pour Linux)
            if ($config['enabled']) {
                $this->setupCronJob($config['schedule']);
            }

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Planification des backups configurée',
                'data' => $config
            ]);

        } catch (Exception $e) {
            ErrorHandler::handleServerError("Erreur lors de la configuration: " . $e->getMessage());
        }
        break;

    /**
     * GET /backup.php?action=logs
     * Récupère les logs de backup
     */
    case 'GET_logs':
        try {
            $logFile = dirname(__DIR__) . '/logs/backup.log';
            $lines = isset($_GET['lines']) ? (int)$_GET['lines'] : 100;

            if (!file_exists($logFile)) {
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'data' => [],
                    'message' => 'Aucun log de backup trouvé'
                ]);
                break;
            }

            // Lire les dernières lignes du fichier de log
            $logs = $this->tail($logFile, $lines);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $logs,
                'total_lines' => count($logs)
            ]);

        } catch (Exception $e) {
            ErrorHandler::handleServerError("Erreur lors de la lecture des logs: " . $e->getMessage());
        }
        break;

    /**
     * DELETE /backup.php?action=delete
     * Supprime un backup spécifique
     * Query: ?backup_id=2024-01-15_14-30-00_full
     */
    case 'DELETE_delete':
        $backupId = $_GET['backup_id'] ?? null;

        if (!$backupId) {
            ErrorHandler::handleError(400, "ID de backup requis");
        }

        try {
            $backupDir = dirname(__DIR__) . '/backups';
            $manifestFile = $backupDir . "/manifest_{$backupId}.json";

            if (!file_exists($manifestFile)) {
                ErrorHandler::handleNotFoundError("Backup non trouvé");
            }

            // Lire le manifeste pour connaître tous les fichiers
            $manifest = json_decode(file_get_contents($manifestFile), true);

            // Supprimer tous les fichiers associés
            $patterns = [
                $backupDir . "/database/*_{$backupId}.*",
                $backupDir . "/files/*_{$backupId}.*",
                $manifestFile
            ];

            $deletedFiles = [];
            foreach ($patterns as $pattern) {
                $files = glob($pattern);
                foreach ($files as $file) {
                    if (unlink($file)) {
                        $deletedFiles[] = basename($file);
                    }
                }
            }

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Backup supprimé avec succès',
                'data' => [
                    'backup_id' => $backupId,
                    'deleted_files' => $deletedFiles
                ]
            ]);

        } catch (Exception $e) {
            ErrorHandler::handleServerError("Erreur lors de la suppression: " . $e->getMessage());
        }
        break;

    /**
     * Gestion des endpoints non trouvés
     */
    default:
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Endpoint non trouvé',
            'available_actions' => [
                'POST /?action=full' => 'Lancer un backup complet',
                'GET /?action=list' => 'Lister les backups',
                'POST /?action=restore' => 'Restaurer un backup',
                'GET /?action=status' => 'Statut du système',
                'POST /?action=schedule' => 'Configurer la planification',
                'GET /?action=logs' => 'Logs de backup',
                'DELETE /?action=delete' => 'Supprimer un backup'
            ]
        ]);
        break;
}

/**
 * Fonctions utilitaires
 */
function getDirectorySize($directory) {
    if (!is_dir($directory)) return 0;

    $size = 0;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $size += $file->getSize();
        }
    }

    return $size;
}

function tail($filename, $lines = 100) {
    $file = file($filename);
    return array_slice($file, -$lines);
}

function setupCronJob($schedule) {
    // Cette fonction dépend du système d'exploitation
    // Exemple pour Linux/Unix :

    $cronEntry = match($schedule) {
        'hourly' => '0 * * * *',
        'daily' => '0 2 * * *',      // 2h du matin
        'weekly' => '0 2 * * 0',     // Dimanche 2h
        'monthly' => '0 2 1 * *',    // 1er du mois 2h
        default => '0 2 * * *'
    };

    $phpPath = '/usr/bin/php';
    $scriptPath = dirname(__DIR__) . '/scripts/backup_cron.php';
    $logPath = dirname(__DIR__) . '/logs/cron_backup.log';

    $cronCommand = "{$cronEntry} {$phpPath} {$scriptPath} >> {$logPath} 2>&1";

    // Ici vous devriez implémenter la logique pour ajouter au crontab
    // Cela peut nécessiter des permissions spéciales ou l'utilisation d'un service dédié
}
?>