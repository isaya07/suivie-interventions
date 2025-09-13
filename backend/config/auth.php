<?php
// config/auth.php
class Auth {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function startSession() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
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
        $this->startSession();
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    public function getCurrentUser() {
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

    public function hasPermission($required_role) {
        if(!$this->isAuthenticated()) {
            return false;
        }

        $user_role = $_SESSION['role'];
        $roles_hierarchy = ['client' => 1, 'technicien' => 2, 'manager' => 3, 'admin' => 4];
        
        return $roles_hierarchy[$user_role] >= $roles_hierarchy[$required_role];
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