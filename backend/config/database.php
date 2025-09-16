<?php
/**
 * Classe de gestion de connexion à la base de données MySQL
 *
 * Utilise PDO pour une connexion sécurisée avec gestion des erreurs
 * Configuration via variables d'environnement pour la flexibilité
 * Pattern Singleton implicite via méthode getConnection()
 */

require_once __DIR__ . '/env.php';

class Database {
    // Propriétés de connexion (privées pour encapsulation)
    private $host;
    private $db_name;
    private $username;
    private $password;

    // Instance de connexion PDO
    public $conn;

    /**
     * Constructeur - Initialise les paramètres de connexion
     * Charge les variables depuis le fichier de configuration env.php
     * Utilise des valeurs par défaut pour un environnement de développement local
     */
    public function __construct() {
        $env = include __DIR__ . '/env.php';
        $this->host = $env['DB_HOST'] ?? 'localhost';
        $this->db_name = $env['DB_NAME'] ?? 'suivi_interventions';
        $this->username = $env['DB_USER'] ?? 'root';
        $this->password = $env['DB_PASS'] ?? '';
    }

    /**
     * Établit et retourne une connexion PDO à la base de données
     *
     * Configuration automatique :
     * - Encodage UTF-8 pour supporter les caractères accentués
     * - Mode d'erreur EXCEPTION pour une gestion robuste des erreurs
     * - Logging automatique des erreurs de connexion
     *
     * @return PDO|null Instance PDO si connexion réussie, null sinon
     */
    public function getConnection() {
        $this->conn = null;

        try {
            // Création de la connexion PDO avec DSN MySQL
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                                $this->username, $this->password);

            // Configuration de l'encodage UTF-8
            $this->conn->exec("set names utf8");

            // Activation du mode exception pour une meilleure gestion d'erreurs
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch(PDOException $exception) {
            // Logging de l'erreur réelle pour le debugging (sans exposer les détails)
            error_log("Database connection error: " . $exception->getMessage());

            // Retour null pour indiquer l'échec de connexion
            $this->conn = null;
        }

        return $this->conn;
    }
}
?>