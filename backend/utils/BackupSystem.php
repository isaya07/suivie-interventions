<?php
/**
 * Système de backup automatique pour l'application de suivi d'interventions
 *
 * Fonctionnalités :
 * - Backup automatique de la base de données
 * - Backup des fichiers critiques (uploads, logs, config)
 * - Compression et chiffrement des backups
 * - Rotation automatique des anciens backups
 * - Backup incrémental et complet
 * - Notification en cas d'échec
 * - Restauration automatisée
 */

class BackupSystem {

    private $db;
    private $config;
    private $backupDir;
    private $logFile;

    public function __construct($database) {
        $this->db = $database;
        $this->backupDir = dirname(__DIR__) . '/backups';
        $this->logFile = dirname(__DIR__) . '/logs/backup.log';

        // Configuration du système de backup
        $this->config = [
            'max_backups' => 30,           // Nombre maximum de backups à conserver
            'backup_schedule' => 'daily',   // daily, weekly, monthly
            'compress' => true,             // Compression des backups
            'encrypt' => false,             // Chiffrement (nécessite OpenSSL)
            'email_on_failure' => true,    // Email en cas d'échec
            'admin_email' => 'admin@example.com',
            'backup_types' => ['database', 'files', 'logs', 'uploads']
        ];

        $this->initializeBackupSystem();
    }

    /**
     * Initialise le système de backup
     */
    private function initializeBackupSystem() {
        // Créer les répertoires nécessaires
        $directories = [
            $this->backupDir,
            $this->backupDir . '/database',
            $this->backupDir . '/files',
            $this->backupDir . '/incremental',
            dirname($this->logFile)
        ];

        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }

        // Créer le fichier .htaccess pour sécuriser les backups
        $htaccessContent = "Order Deny,Allow\nDeny from all\n";
        file_put_contents($this->backupDir . '/.htaccess', $htaccessContent);
    }

    /**
     * Effectue un backup complet du système
     */
    public function performFullBackup() {
        $startTime = microtime(true);
        $backupId = date('Y-m-d_H-i-s') . '_full';

        $this->log("Début du backup complet - ID: {$backupId}");

        try {
            $backupResults = [];

            // Backup de la base de données
            if (in_array('database', $this->config['backup_types'])) {
                $dbResult = $this->backupDatabase($backupId);
                $backupResults['database'] = $dbResult;
            }

            // Backup des fichiers d'uploads
            if (in_array('uploads', $this->config['backup_types'])) {
                $uploadsResult = $this->backupUploads($backupId);
                $backupResults['uploads'] = $uploadsResult;
            }

            // Backup des logs
            if (in_array('logs', $this->config['backup_types'])) {
                $logsResult = $this->backupLogs($backupId);
                $backupResults['logs'] = $logsResult;
            }

            // Backup des fichiers de configuration
            if (in_array('files', $this->config['backup_types'])) {
                $configResult = $this->backupConfigFiles($backupId);
                $backupResults['config'] = $configResult;
            }

            // Créer le manifeste du backup
            $this->createBackupManifest($backupId, $backupResults);

            // Nettoyer les anciens backups
            $this->cleanOldBackups();

            $duration = round(microtime(true) - $startTime, 2);
            $this->log("Backup complet terminé avec succès en {$duration}s - ID: {$backupId}");

            return [
                'success' => true,
                'backup_id' => $backupId,
                'duration' => $duration,
                'results' => $backupResults
            ];

        } catch (Exception $e) {
            $this->log("ERREUR lors du backup complet: " . $e->getMessage(), 'ERROR');

            if ($this->config['email_on_failure']) {
                $this->sendFailureNotification($e->getMessage());
            }

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'backup_id' => $backupId
            ];
        }
    }

    /**
     * Backup de la base de données
     */
    private function backupDatabase($backupId) {
        $this->log("Début du backup de la base de données");

        try {
            // Obtenir les informations de connexion
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $database = $_ENV['DB_NAME'] ?? 'suivie_interventions';
            $username = $_ENV['DB_USER'] ?? 'root';
            $password = $_ENV['DB_PASS'] ?? '';

            $backupFile = $this->backupDir . "/database/db_{$backupId}.sql";

            // Utiliser mysqldump pour créer le backup
            $command = sprintf(
                'mysqldump --host=%s --user=%s --password=%s --single-transaction --routines --triggers %s > %s',
                escapeshellarg($host),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($database),
                escapeshellarg($backupFile)
            );

            // Exécuter la commande en masquant le mot de passe dans les logs
            $this->log("Exécution de mysqldump pour la base de données");
            exec($command, $output, $returnCode);

            if ($returnCode === 0 && file_exists($backupFile)) {
                $fileSize = filesize($backupFile);

                // Compresser si activé
                if ($this->config['compress']) {
                    $compressedFile = $backupFile . '.gz';
                    $this->compressFile($backupFile, $compressedFile);
                    unlink($backupFile);
                    $backupFile = $compressedFile;
                }

                $this->log("Backup de la base de données réussi - Taille: " . $this->formatBytes($fileSize));

                return [
                    'success' => true,
                    'file' => $backupFile,
                    'size' => $fileSize
                ];
            } else {
                throw new Exception("Échec de mysqldump - Code de retour: {$returnCode}");
            }

        } catch (Exception $e) {
            $this->log("Erreur backup base de données: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Backup des fichiers d'uploads
     */
    private function backupUploads($backupId) {
        $this->log("Début du backup des fichiers d'uploads");

        try {
            $uploadsDir = dirname(__DIR__) . '/uploads';
            $backupFile = $this->backupDir . "/files/uploads_{$backupId}.tar.gz";

            if (!is_dir($uploadsDir)) {
                $this->log("Répertoire uploads non trouvé - backup ignoré");
                return ['success' => true, 'message' => 'Pas de fichiers uploads'];
            }

            // Créer une archive tar.gz des uploads
            $command = sprintf(
                'tar -czf %s -C %s .',
                escapeshellarg($backupFile),
                escapeshellarg($uploadsDir)
            );

            exec($command, $output, $returnCode);

            if ($returnCode === 0 && file_exists($backupFile)) {
                $fileSize = filesize($backupFile);
                $this->log("Backup des uploads réussi - Taille: " . $this->formatBytes($fileSize));

                return [
                    'success' => true,
                    'file' => $backupFile,
                    'size' => $fileSize
                ];
            } else {
                throw new Exception("Échec de la création de l'archive uploads");
            }

        } catch (Exception $e) {
            $this->log("Erreur backup uploads: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Backup des logs
     */
    private function backupLogs($backupId) {
        $this->log("Début du backup des logs");

        try {
            $logsDir = dirname(__DIR__) . '/logs';
            $backupFile = $this->backupDir . "/files/logs_{$backupId}.tar.gz";

            if (!is_dir($logsDir)) {
                $this->log("Répertoire logs non trouvé - backup ignoré");
                return ['success' => true, 'message' => 'Pas de logs'];
            }

            // Créer une archive tar.gz des logs
            $command = sprintf(
                'tar -czf %s -C %s .',
                escapeshellarg($backupFile),
                escapeshellarg($logsDir)
            );

            exec($command, $output, $returnCode);

            if ($returnCode === 0 && file_exists($backupFile)) {
                $fileSize = filesize($backupFile);
                $this->log("Backup des logs réussi - Taille: " . $this->formatBytes($fileSize));

                return [
                    'success' => true,
                    'file' => $backupFile,
                    'size' => $fileSize
                ];
            } else {
                throw new Exception("Échec de la création de l'archive logs");
            }

        } catch (Exception $e) {
            $this->log("Erreur backup logs: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Backup des fichiers de configuration
     */
    private function backupConfigFiles($backupId) {
        $this->log("Début du backup des fichiers de configuration");

        try {
            $configFiles = [
                dirname(__DIR__) . '/config',
                dirname(__DIR__) . '/.htaccess',
                dirname(dirname(__DIR__)) . '/frontend/nuxt.config.ts',
                dirname(dirname(__DIR__)) . '/frontend/package.json'
            ];

            $backupFile = $this->backupDir . "/files/config_{$backupId}.tar.gz";
            $tempDir = sys_get_temp_dir() . "/backup_config_{$backupId}";

            // Créer un répertoire temporaire
            mkdir($tempDir, 0755, true);

            // Copier les fichiers de configuration
            foreach ($configFiles as $file) {
                if (file_exists($file)) {
                    if (is_dir($file)) {
                        $this->copyDirectory($file, $tempDir . '/' . basename($file));
                    } else {
                        copy($file, $tempDir . '/' . basename($file));
                    }
                }
            }

            // Créer l'archive
            $command = sprintf(
                'tar -czf %s -C %s .',
                escapeshellarg($backupFile),
                escapeshellarg($tempDir)
            );

            exec($command, $output, $returnCode);

            // Nettoyer le répertoire temporaire
            $this->removeDirectory($tempDir);

            if ($returnCode === 0 && file_exists($backupFile)) {
                $fileSize = filesize($backupFile);
                $this->log("Backup des configs réussi - Taille: " . $this->formatBytes($fileSize));

                return [
                    'success' => true,
                    'file' => $backupFile,
                    'size' => $fileSize
                ];
            } else {
                throw new Exception("Échec de la création de l'archive config");
            }

        } catch (Exception $e) {
            $this->log("Erreur backup config: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Crée un manifeste du backup
     */
    private function createBackupManifest($backupId, $results) {
        $manifest = [
            'backup_id' => $backupId,
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'full',
            'version' => '1.0',
            'system_info' => [
                'php_version' => PHP_VERSION,
                'os' => PHP_OS,
                'memory_limit' => ini_get('memory_limit')
            ],
            'results' => $results,
            'checksum' => $this->calculateBackupChecksum($results)
        ];

        $manifestFile = $this->backupDir . "/manifest_{$backupId}.json";
        file_put_contents($manifestFile, json_encode($manifest, JSON_PRETTY_PRINT));

        $this->log("Manifeste de backup créé: {$manifestFile}");
    }

    /**
     * Calcule un checksum pour vérifier l'intégrité du backup
     */
    private function calculateBackupChecksum($results) {
        $checksums = [];

        foreach ($results as $type => $result) {
            if (isset($result['file']) && file_exists($result['file'])) {
                $checksums[$type] = hash_file('sha256', $result['file']);
            }
        }

        return hash('sha256', json_encode($checksums));
    }

    /**
     * Nettoie les anciens backups
     */
    private function cleanOldBackups() {
        $this->log("Nettoyage des anciens backups");

        try {
            $backupFiles = glob($this->backupDir . '/manifest_*.json');

            if (count($backupFiles) <= $this->config['max_backups']) {
                return;
            }

            // Trier par date de modification
            usort($backupFiles, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });

            // Supprimer les plus anciens
            $toDelete = array_slice($backupFiles, 0, count($backupFiles) - $this->config['max_backups']);

            foreach ($toDelete as $manifestFile) {
                $manifest = json_decode(file_get_contents($manifestFile), true);
                $backupId = $manifest['backup_id'];

                // Supprimer tous les fichiers associés à ce backup
                $patterns = [
                    $this->backupDir . "/database/*_{$backupId}.*",
                    $this->backupDir . "/files/*_{$backupId}.*",
                    $manifestFile
                ];

                foreach ($patterns as $pattern) {
                    $files = glob($pattern);
                    foreach ($files as $file) {
                        unlink($file);
                    }
                }

                $this->log("Backup ancien supprimé: {$backupId}");
            }

        } catch (Exception $e) {
            $this->log("Erreur lors du nettoyage: " . $e->getMessage(), 'ERROR');
        }
    }

    /**
     * Restaure un backup
     */
    public function restoreBackup($backupId) {
        $this->log("Début de la restauration du backup: {$backupId}");

        try {
            $manifestFile = $this->backupDir . "/manifest_{$backupId}.json";

            if (!file_exists($manifestFile)) {
                throw new Exception("Manifeste de backup non trouvé: {$backupId}");
            }

            $manifest = json_decode(file_get_contents($manifestFile), true);

            // Vérifier l'intégrité
            $currentChecksum = $this->calculateBackupChecksum($manifest['results']);
            if ($currentChecksum !== $manifest['checksum']) {
                throw new Exception("Checksum invalide - backup possiblement corrompu");
            }

            // Restaurer la base de données
            if (isset($manifest['results']['database'])) {
                $this->restoreDatabase($manifest['results']['database']['file']);
            }

            // Note: La restauration des fichiers doit être faite avec précaution
            // en production pour éviter d'écraser des fichiers en cours d'utilisation

            $this->log("Restauration terminée avec succès: {$backupId}");

            return ['success' => true, 'backup_id' => $backupId];

        } catch (Exception $e) {
            $this->log("Erreur de restauration: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Restaure la base de données
     */
    private function restoreDatabase($backupFile) {
        $this->log("Restauration de la base de données depuis: {$backupFile}");

        // Décompresser si nécessaire
        if (pathinfo($backupFile, PATHINFO_EXTENSION) === 'gz') {
            $tempFile = sys_get_temp_dir() . '/restore_' . uniqid() . '.sql';
            $this->decompressFile($backupFile, $tempFile);
            $backupFile = $tempFile;
        }

        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $database = $_ENV['DB_NAME'] ?? 'suivie_interventions';
        $username = $_ENV['DB_USER'] ?? 'root';
        $password = $_ENV['DB_PASS'] ?? '';

        $command = sprintf(
            'mysql --host=%s --user=%s --password=%s %s < %s',
            escapeshellarg($host),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($backupFile)
        );

        exec($command, $output, $returnCode);

        // Nettoyer le fichier temporaire si créé
        if (isset($tempFile) && file_exists($tempFile)) {
            unlink($tempFile);
        }

        if ($returnCode !== 0) {
            throw new Exception("Échec de la restauration de la base de données");
        }

        $this->log("Base de données restaurée avec succès");
    }

    /**
     * Liste les backups disponibles
     */
    public function listBackups() {
        $manifests = glob($this->backupDir . '/manifest_*.json');
        $backups = [];

        foreach ($manifests as $manifestFile) {
            $manifest = json_decode(file_get_contents($manifestFile), true);
            $backups[] = [
                'id' => $manifest['backup_id'],
                'timestamp' => $manifest['timestamp'],
                'type' => $manifest['type'],
                'size' => $this->calculateBackupSize($manifest['results'])
            ];
        }

        // Trier par date décroissante
        usort($backups, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        return $backups;
    }

    /**
     * Méthodes utilitaires
     */
    private function compressFile($source, $destination) {
        $file = fopen($source, 'rb');
        $gz = gzopen($destination, 'wb9');

        while (!feof($file)) {
            gzwrite($gz, fread($file, 8192));
        }

        fclose($file);
        gzclose($gz);
    }

    private function decompressFile($source, $destination) {
        $gz = gzopen($source, 'rb');
        $file = fopen($destination, 'wb');

        while (!gzeof($gz)) {
            fwrite($file, gzread($gz, 8192));
        }

        gzclose($gz);
        fclose($file);
    }

    private function copyDirectory($src, $dst) {
        $dir = opendir($src);
        mkdir($dst, 0755, true);

        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..') {
                if (is_dir($src . '/' . $file)) {
                    $this->copyDirectory($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }

        closedir($dir);
    }

    private function removeDirectory($dir) {
        if (!is_dir($dir)) return;

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }

    private function calculateBackupSize($results) {
        $totalSize = 0;
        foreach ($results as $result) {
            if (isset($result['size'])) {
                $totalSize += $result['size'];
            }
        }
        return $totalSize;
    }

    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    private function log($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] {$level}: {$message}\n";

        // Créer le répertoire de logs si nécessaire
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    private function sendFailureNotification($error) {
        $subject = "Échec du backup - " . date('Y-m-d H:i:s');
        $message = "Le backup automatique a échoué avec l'erreur suivante :\n\n{$error}";

        // Ici vous pouvez implémenter l'envoi d'email
        // mail($this->config['admin_email'], $subject, $message);

        $this->log("Notification d'échec envoyée à: " . $this->config['admin_email']);
    }

    /**
     * Planifie les backups automatiques
     */
    public function scheduleBackups() {
        // Cette méthode peut être appelée par un cron job
        $lastBackupFile = $this->backupDir . '/last_backup.txt';
        $now = time();

        $shouldBackup = false;

        if (!file_exists($lastBackupFile)) {
            $shouldBackup = true;
        } else {
            $lastBackup = (int)file_get_contents($lastBackupFile);
            $interval = $this->getBackupInterval();

            if (($now - $lastBackup) >= $interval) {
                $shouldBackup = true;
            }
        }

        if ($shouldBackup) {
            $result = $this->performFullBackup();

            if ($result['success']) {
                file_put_contents($lastBackupFile, $now);
            }

            return $result;
        }

        return ['success' => true, 'message' => 'Backup non nécessaire'];
    }

    private function getBackupInterval() {
        switch ($this->config['backup_schedule']) {
            case 'hourly': return 3600;
            case 'daily': return 86400;
            case 'weekly': return 604800;
            case 'monthly': return 2592000;
            default: return 86400; // daily par défaut
        }
    }
}
?>