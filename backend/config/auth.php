<?php
// config/auth.php
require_once __DIR__ . '/../models/User.php';

class Auth {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function startSession() {
        if (session_status() == PHP_SESSION_NONE) {
            // Security configurations
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_samesite', 'Strict');

            // Clean expired sessions before starting new one
            $this->cleanExpiredSessions();

            session_start();

            // Regenerate session ID periodically for security
            if (!isset($_SESSION['last_regeneration'])) {
                $_SESSION['last_regeneration'] = time();
            } else if (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
                session_regenerate_id(true);
                $_SESSION['last_regeneration'] = time();
            }
        }
    }

    public function login($username, $password) {
        $user = new User($this->conn);

        if($user->login($username, $password)) {
            $this->startSession();
            $_SESSION['user_id'] = $user->id;
            $_SESSION['username'] = $user->username;
            $_SESSION['nom_complet'] = $user->prenom . ' ' . $user->nom;
            $_SESSION['role'] = $user->role;
            $_SESSION['email'] = $user->email;
            $_SESSION['avatar'] = $user->avatar;
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';

            // Generate a session token for frontend
            $token = bin2hex(random_bytes(32));
            $_SESSION['token'] = $token;

            // Enregistrer la session en base
            $this->saveSession($user->id, $token);

            return [
                'success' => true,
                'token' => $token, // Add token for frontend compatibility
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'nom_complet' => $user->prenom . ' ' . $user->nom,
                    'role' => $user->role,
                    'email' => $user->email,
                    'avatar' => $user->avatar
                ]
            ];
        }

        return ['success' => false, 'message' => 'Identifiants invalides'];
    }

    public function logout() {
        $this->startSession();
        
        if(isset($_SESSION['user_id'])) {
            $this->deleteSession($_SESSION['user_id']);
        }
        
        session_destroy();
        return ['success' => true, 'message' => 'Déconnexion réussie'];
    }

    public function isAuthenticated() {
        // Vérifier d'abord si il y a un token dans les headers
        $headers = getallheaders();
        $authorization = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (preg_match('/Bearer\s+(.*)$/i', $authorization, $matches)) {
            $token = $matches[1];
            return $this->validateToken($token);
        }

        // Sinon vérifier les sessions PHP
        $this->startSession();

        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            return false;
        }

        // Additional security checks
        $current_ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $current_user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $current_ip) {
            // IP changed, destroy session for security
            session_destroy();
            return false;
        }

        if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $current_user_agent) {
            // User agent changed, destroy session for security
            session_destroy();
            return false;
        }

        // Check if session exists in database and is not expired
        if (isset($_SESSION['token'])) {
            $query = "SELECT expires_at FROM user_sessions WHERE id = :token AND user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":token", $_SESSION['token']);
            $stmt->bindParam(":user_id", $_SESSION['user_id']);
            $stmt->execute();

            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$session || strtotime($session['expires_at']) < time()) {
                session_destroy();
                return false;
            }
        }

        return true;
    }

    public function getCurrentUser() {
        // Vérifier d'abord les tokens Bearer
        $headers = getallheaders();
        $authorization = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (preg_match('/Bearer\s+(.*)$/i', $authorization, $matches)) {
            $token = $matches[1];
            $user = $this->getUserFromToken($token);
            if ($user) {
                return $user;
            }
        }

        // Sinon vérifier les sessions PHP
        $this->startSession();
        if($this->isAuthenticated()) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'nom_complet' => $_SESSION['nom_complet'],
                'role' => $_SESSION['role'],
                'email' => $_SESSION['email'],
                'avatar' => $_SESSION['avatar']
            ];
        }
        return null;
    }

    private function validateToken($token) {
        $query = "SELECT user_id, expires_at FROM user_sessions WHERE id = :token";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $token);
        $stmt->execute();

        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($session && strtotime($session['expires_at']) > time()) {
            return true;
        }
        return false;
    }

    private function getUserFromToken($token) {
        $query = "SELECT s.user_id, u.username, u.nom, u.prenom, u.role, u.email, u.avatar
                  FROM user_sessions s
                  JOIN users u ON s.user_id = u.id
                  WHERE s.id = :token AND s.expires_at > NOW()";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $token);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            return [
                'id' => $result['user_id'],
                'username' => $result['username'],
                'nom_complet' => $result['prenom'] . ' ' . $result['nom'],
                'role' => $result['role'],
                'email' => $result['email'],
                'avatar' => $result['avatar']
            ];
        }
        return null;
    }

    public function hasPermission($required_role) {
        if(!$this->isAuthenticated()) {
            return false;
        }

        // Récupérer l'utilisateur actuel (qui gère à la fois les tokens et les sessions)
        $current_user = $this->getCurrentUser();
        if (!$current_user || !isset($current_user['role'])) {
            return false;
        }

        $user_role = $current_user['role'];
        $roles_hierarchy = ['client' => 1, 'technicien' => 2, 'manager' => 3, 'admin' => 4];

        return isset($roles_hierarchy[$user_role]) &&
               isset($roles_hierarchy[$required_role]) &&
               $roles_hierarchy[$user_role] >= $roles_hierarchy[$required_role];
    }

    private function saveSession($user_id, $token = null) {
        $session_id = $token ?? session_id();
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $expires_at = date('Y-m-d H:i:s', time() + 86400); // 24 heures

        $query = "INSERT INTO user_sessions (id, user_id, ip_address, user_agent, expires_at)
                  VALUES (:session_id, :user_id, :ip_address, :user_agent, :expires_at)
                  ON DUPLICATE KEY UPDATE
                  ip_address = :ip_address, user_agent = :user_agent, expires_at = :expires_at";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":session_id", $session_id);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":ip_address", $ip_address);
        $stmt->bindParam(":user_agent", $user_agent);
        $stmt->bindParam(":expires_at", $expires_at);
        $stmt->execute();
    }

    private function deleteSession($user_id) {
        $query = "DELETE FROM user_sessions WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
    }

    public function cleanExpiredSessions() {
        $query = "DELETE FROM user_sessions WHERE expires_at < NOW()";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
    }
}
?>