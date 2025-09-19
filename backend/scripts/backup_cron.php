<?php
/**
 * Script de backup automatique pour les tâches cron
 *
 * Ce script peut être exécuté par cron pour effectuer des backups automatiques
 * Usage: php backup_cron.php [--force] [--type=full|incremental]
 */

// Changement vers le répertoire du script
chdir(__DIR__);

// Import des dépendances
require_once '../config/env.php';
require_once '../config/database.php';
require_once '../utils/BackupSystem.php';

// Gestion des arguments de ligne de commande
$options = getopt('', ['force', 'type::', 'help']);

if (isset($options['help'])) {
    showHelp();
    exit(0);
}

// Configuration
$forceBackup = isset($options['force']);
$backupType = $options['type'] ?? 'full';

// Log du démarrage
$logFile = dirname(__DIR__) . '/logs/cron_backup.log';
logMessage($logFile, "Démarrage du script de backup automatique - Type: {$backupType}");

try {
    // Initialisation de la base de données
    $database = new Database();
    $db = $database->getConnection();

    if ($db === null) {
        throw new Exception("Impossible de se connecter à la base de données");
    }

    // Initialisation du système de backup
    $backupSystem = new BackupSystem($db);

    // Vérifier si un backup est nécessaire (sauf si forcé)
    if (!$forceBackup) {
        $result = $backupSystem->scheduleBackups();
    } else {
        $result = $backupSystem->performFullBackup();
    }

    if ($result['success']) {
        logMessage($logFile, "Backup terminé avec succès: " . json_encode($result));

        // Optionnel: Envoyer une notification de succès
        if (isset($result['backup_id'])) {
            sendSuccessNotification($result);
        }
    } else {
        logMessage($logFile, "Échec du backup: " . ($result['error'] ?? 'Erreur inconnue'), 'ERROR');
        sendFailureNotification($result['error'] ?? 'Erreur inconnue');
    }

} catch (Exception $e) {
    $errorMessage = "Exception lors du backup automatique: " . $e->getMessage();
    logMessage($logFile, $errorMessage, 'ERROR');
    sendFailureNotification($errorMessage);
    exit(1);
}

exit(0);

/**
 * Fonctions utilitaires
 */

function showHelp() {
    echo "Script de backup automatique\n";
    echo "Usage: php backup_cron.php [options]\n\n";
    echo "Options:\n";
    echo "  --force        Force l'exécution du backup même si non planifié\n";
    echo "  --type=TYPE    Type de backup (full|incremental) - défaut: full\n";
    echo "  --help         Affiche cette aide\n\n";
    echo "Exemples:\n";
    echo "  php backup_cron.php\n";
    echo "  php backup_cron.php --force\n";
    echo "  php backup_cron.php --type=full --force\n";
}

function logMessage($logFile, $message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $pid = getmypid();
    $logEntry = "[{$timestamp}] [{$level}] [PID:{$pid}] {$message}\n";

    // Créer le répertoire de logs si nécessaire
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

    // Aussi afficher sur stdout pour cron
    echo $logEntry;
}

function sendSuccessNotification($result) {
    // Configuration de notification (peut être dans un fichier de config)
    $notificationConfig = [
        'enabled' => false, // Désactivé par défaut
        'email' => 'admin@example.com',
        'webhook_url' => '', // URL de webhook Slack/Teams/etc.
    ];

    if (!$notificationConfig['enabled']) {
        return;
    }

    $subject = "Backup automatique réussi - " . date('Y-m-d H:i:s');
    $message = "Le backup automatique s'est terminé avec succès.\n\n";
    $message .= "ID de backup: " . ($result['backup_id'] ?? 'N/A') . "\n";
    $message .= "Durée: " . ($result['duration'] ?? 'N/A') . " secondes\n";
    $message .= "Timestamp: " . date('Y-m-d H:i:s') . "\n";

    // Envoi par email (si configuré)
    if ($notificationConfig['email'] && function_exists('mail')) {
        mail($notificationConfig['email'], $subject, $message);
    }

    // Envoi par webhook (si configuré)
    if ($notificationConfig['webhook_url']) {
        $payload = json_encode([
            'text' => $subject,
            'attachments' => [
                [
                    'color' => 'good',
                    'fields' => [
                        ['title' => 'ID Backup', 'value' => $result['backup_id'] ?? 'N/A', 'short' => true],
                        ['title' => 'Durée', 'value' => ($result['duration'] ?? 'N/A') . 's', 'short' => true],
                    ]
                ]
            ]
        ]);

        $ch = curl_init($notificationConfig['webhook_url']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
    }
}

function sendFailureNotification($error) {
    // Configuration similaire à sendSuccessNotification
    $notificationConfig = [
        'enabled' => true, // Activé par défaut pour les erreurs
        'email' => 'admin@example.com',
        'webhook_url' => '',
    ];

    if (!$notificationConfig['enabled']) {
        return;
    }

    $subject = "ÉCHEC du backup automatique - " . date('Y-m-d H:i:s');
    $message = "Le backup automatique a échoué.\n\n";
    $message .= "Erreur: {$error}\n";
    $message .= "Serveur: " . php_uname('n') . "\n";
    $message .= "Timestamp: " . date('Y-m-d H:i:s') . "\n";
    $message .= "\nVeuillez vérifier les logs pour plus de détails.";

    // Envoi par email
    if ($notificationConfig['email'] && function_exists('mail')) {
        mail($notificationConfig['email'], $subject, $message);
    }

    // Envoi par webhook
    if ($notificationConfig['webhook_url']) {
        $payload = json_encode([
            'text' => $subject,
            'attachments' => [
                [
                    'color' => 'danger',
                    'fields' => [
                        ['title' => 'Erreur', 'value' => $error, 'short' => false],
                        ['title' => 'Serveur', 'value' => php_uname('n'), 'short' => true],
                    ]
                ]
            ]
        ]);

        $ch = curl_init($notificationConfig['webhook_url']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
    }
}

/**
 * Fonction de nettoyage automatique des logs
 */
function cleanupLogs() {
    $logFile = dirname(__DIR__) . '/logs/cron_backup.log';

    if (!file_exists($logFile)) {
        return;
    }

    $maxLogSize = 10 * 1024 * 1024; // 10 MB

    if (filesize($logFile) > $maxLogSize) {
        // Garder seulement les 1000 dernières lignes
        $lines = file($logFile);
        $lastLines = array_slice($lines, -1000);

        file_put_contents($logFile, implode('', $lastLines));

        logMessage($logFile, "Logs nettoyés - taille réduite");
    }
}

// Nettoyage automatique des logs à chaque exécution
cleanupLogs();
?>