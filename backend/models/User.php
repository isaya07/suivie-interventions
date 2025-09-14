<?php
// backend/models/User.php
class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $username;
    public $email;
    public $password_hash;
    public $nom;
    public $prenom;
    public $role;
    public $telephone;
    public $specialite;
    public $avatar;
    public $is_active;
    public $last_login;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET username=:username, email=:email, password_hash=:password_hash, 
                      nom=:nom, prenom=:prenom, role=:role, telephone=:telephone, 
                      specialite=:specialite";

        $stmt = $this->conn->prepare($query);

        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->nom = htmlspecialchars(strip_tags($this->nom));
        $this->prenom = htmlspecialchars(strip_tags($this->prenom));
        $this->role = htmlspecialchars(strip_tags($this->role));
        $this->telephone = htmlspecialchars(strip_tags($this->telephone));
        $this->specialite = htmlspecialchars(strip_tags($this->specialite));

        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password_hash", $this->password_hash);
        $stmt->bindParam(":nom", $this->nom);
        $stmt->bindParam(":prenom", $this->prenom);
        $stmt->bindParam(":role", $this->role);
        $stmt->bindParam(":telephone", $this->telephone);
        $stmt->bindParam(":specialite", $this->specialite);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function login($username, $password) {
        $query = "SELECT id, username, email, password_hash, nom, prenom, role, telephone, specialite, avatar, is_active
                  FROM " . $this->table_name . " 
                  WHERE (username = :username OR email = :username) AND is_active = 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if(password_verify($password, $row['password_hash'])) {
                $this->id = $row['id'];
                $this->username = $row['username'];
                $this->email = $row['email'];
                $this->nom = $row['nom'];
                $this->prenom = $row['prenom'];
                $this->role = $row['role'];
                $this->telephone = $row['telephone'];
                $this->specialite = $row['specialite'];
                $this->avatar = $row['avatar'];
                
                // Mettre à jour la date de dernière connexion
                $this->updateLastLogin();
                
                return true;
            }
        }
        return false;
    }

    private function updateLastLogin() {
        $query = "UPDATE " . $this->table_name . " SET last_login = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
    }

    public function read() {
        $query = "SELECT id, username, email, nom, prenom, role, telephone, specialite, type_technicien, avatar, is_active, last_login, created_at
                  FROM " . $this->table_name . "
                  ORDER BY nom, prenom";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readTechniciens() {
        $query = "SELECT id, username, email, nom, prenom, specialite, type_technicien, telephone
                  FROM " . $this->table_name . "
                  WHERE role IN ('technicien', 'manager') AND is_active = 1
                  ORDER BY nom, prenom";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne() {
        $query = "SELECT id, username, email, nom, prenom, role, telephone, specialite, avatar, is_active, last_login, created_at
                  FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row) {
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->nom = $row['nom'];
            $this->prenom = $row['prenom'];
            $this->role = $row['role'];
            $this->telephone = $row['telephone'];
            $this->specialite = $row['specialite'];
            $this->avatar = $row['avatar'];
            $this->is_active = $row['is_active'];
            $this->last_login = $row['last_login'];
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET email=:email, nom=:nom, prenom=:prenom, role=:role, 
                      telephone=:telephone, specialite=:specialite, is_active=:is_active
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->nom = htmlspecialchars(strip_tags($this->nom));
        $this->prenom = htmlspecialchars(strip_tags($this->prenom));
        $this->role = htmlspecialchars(strip_tags($this->role));
        $this->telephone = htmlspecialchars(strip_tags($this->telephone));
        $this->specialite = htmlspecialchars(strip_tags($this->specialite));

        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":nom", $this->nom);
        $stmt->bindParam(":prenom", $this->prenom);
        $stmt->bindParam(":role", $this->role);
        $stmt->bindParam(":telephone", $this->telephone);
        $stmt->bindParam(":specialite", $this->specialite);
        $stmt->bindParam(":is_active", $this->is_active);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    public function changePassword($new_password) {
        $query = "UPDATE " . $this->table_name . " SET password_hash=:password_hash WHERE id=:id";
        $stmt = $this->conn->prepare($query);
        
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt->bindParam(":password_hash", $password_hash);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }
}
?>