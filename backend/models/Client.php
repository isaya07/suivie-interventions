<?php
// backend/models/Client.php
class Client {
    private $conn;
    private $table_name = "clients";

    public $id;
    public $nom;
    public $email;
    public $telephone;
    public $adresse;
    public $ville;
    public $code_postal;
    public $contact_principal;
    public $notes;
    public $latitude;
    public $longitude;
    public $user_id;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET nom=:nom, email=:email, telephone=:telephone,
                      adresse=:adresse, ville=:ville, code_postal=:code_postal,
                      contact_principal=:contact_principal, notes=:notes,
                      latitude=:latitude, longitude=:longitude, user_id=:user_id";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->nom = htmlspecialchars(strip_tags($this->nom));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->telephone = htmlspecialchars(strip_tags($this->telephone));
        $this->adresse = htmlspecialchars(strip_tags($this->adresse));
        $this->ville = htmlspecialchars(strip_tags($this->ville));
        $this->code_postal = htmlspecialchars(strip_tags($this->code_postal));
        $this->contact_principal = htmlspecialchars(strip_tags($this->contact_principal));
        $this->notes = htmlspecialchars(strip_tags($this->notes));

        // Bind parameters
        $stmt->bindParam(":nom", $this->nom);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":telephone", $this->telephone);
        $stmt->bindParam(":adresse", $this->adresse);
        $stmt->bindParam(":ville", $this->ville);
        $stmt->bindParam(":code_postal", $this->code_postal);
        $stmt->bindParam(":contact_principal", $this->contact_principal);
        $stmt->bindParam(":notes", $this->notes);
        $stmt->bindParam(":latitude", $this->latitude);
        $stmt->bindParam(":longitude", $this->longitude);
        $stmt->bindParam(":user_id", $this->user_id);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function read() {
        // Vérifier si les colonnes GPS existent
        $stmt = $this->conn->prepare("SHOW COLUMNS FROM " . $this->table_name . " LIKE 'latitude'");
        $stmt->execute();
        $hasGPS = $stmt->rowCount() > 0;

        if ($hasGPS) {
            $query = "SELECT id, nom, email, telephone, adresse, ville, code_postal,
                             contact_principal, notes, latitude, longitude,
                             user_id, created_at, updated_at
                      FROM " . $this->table_name . "
                      ORDER BY nom ASC";
        } else {
            $query = "SELECT id, nom, email, telephone, adresse, ville, code_postal,
                             contact_principal, notes, NULL as latitude, NULL as longitude,
                             user_id, created_at, updated_at
                      FROM " . $this->table_name . "
                      ORDER BY nom ASC";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne() {
        // Vérifier si les colonnes GPS existent
        $stmt = $this->conn->prepare("SHOW COLUMNS FROM " . $this->table_name . " LIKE 'latitude'");
        $stmt->execute();
        $hasGPS = $stmt->rowCount() > 0;

        if ($hasGPS) {
            $query = "SELECT id, nom, email, telephone, adresse, ville, code_postal,
                             contact_principal, notes, latitude, longitude,
                             user_id, created_at, updated_at
                      FROM " . $this->table_name . "
                      WHERE id = ? LIMIT 0,1";
        } else {
            $query = "SELECT id, nom, email, telephone, adresse, ville, code_postal,
                             contact_principal, notes, NULL as latitude, NULL as longitude,
                             user_id, created_at, updated_at
                      FROM " . $this->table_name . "
                      WHERE id = ? LIMIT 0,1";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row) {
            $this->nom = $row['nom'];
            $this->email = $row['email'];
            $this->telephone = $row['telephone'];
            $this->adresse = $row['adresse'];
            $this->ville = $row['ville'];
            $this->code_postal = $row['code_postal'];
            $this->contact_principal = $row['contact_principal'];
            $this->notes = $row['notes'];
            $this->latitude = $row['latitude'];
            $this->longitude = $row['longitude'];
            $this->user_id = $row['user_id'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET nom=:nom, email=:email, telephone=:telephone,
                      adresse=:adresse, ville=:ville, code_postal=:code_postal,
                      contact_principal=:contact_principal, notes=:notes,
                      latitude=:latitude, longitude=:longitude
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->nom = htmlspecialchars(strip_tags($this->nom));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->telephone = htmlspecialchars(strip_tags($this->telephone));
        $this->adresse = htmlspecialchars(strip_tags($this->adresse));
        $this->ville = htmlspecialchars(strip_tags($this->ville));
        $this->code_postal = htmlspecialchars(strip_tags($this->code_postal));
        $this->contact_principal = htmlspecialchars(strip_tags($this->contact_principal));
        $this->notes = htmlspecialchars(strip_tags($this->notes));

        // Bind parameters
        $stmt->bindParam(":nom", $this->nom);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":telephone", $this->telephone);
        $stmt->bindParam(":adresse", $this->adresse);
        $stmt->bindParam(":ville", $this->ville);
        $stmt->bindParam(":code_postal", $this->code_postal);
        $stmt->bindParam(":contact_principal", $this->contact_principal);
        $stmt->bindParam(":notes", $this->notes);
        $stmt->bindParam(":latitude", $this->latitude);
        $stmt->bindParam(":longitude", $this->longitude);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        return $stmt->execute();
    }

    // Recherche par proximité GPS (rayon en km)
    public function findByLocation($latitude, $longitude, $radius = 10) {
        $query = "SELECT id, nom, email, telephone, adresse, ville, code_postal,
                         contact_principal, notes, latitude, longitude,
                         user_id, created_at, updated_at,
                         (6371 * acos(cos(radians(:lat)) * cos(radians(latitude)) *
                          cos(radians(longitude) - radians(:lng)) +
                          sin(radians(:lat)) * sin(radians(latitude)))) AS distance
                  FROM " . $this->table_name . "
                  WHERE latitude IS NOT NULL AND longitude IS NOT NULL
                  HAVING distance < :radius
                  ORDER BY distance ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":lat", $latitude);
        $stmt->bindParam(":lng", $longitude);
        $stmt->bindParam(":radius", $radius);
        $stmt->execute();
        return $stmt;
    }
}
?>