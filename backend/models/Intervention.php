<?php
// models/Intervention.php
class Intervention {
    private $conn;
    private $table_name = "interventions";

    public $id;
    public $titre;
    public $description;
    public $client;
    public $technicien;
    public $statut;
    public $priorite;
    public $date_creation;
    public $date_intervention;
    public $date_fin;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET titre=:titre, description=:description, client=:client, 
                      technicien=:technicien, statut=:statut, priorite=:priorite, 
                      date_creation=:date_creation, date_intervention=:date_intervention";

        $stmt = $this->conn->prepare($query);

        $this->titre = htmlspecialchars(strip_tags($this->titre));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->client = htmlspecialchars(strip_tags($this->client));
        $this->technicien = htmlspecialchars(strip_tags($this->technicien));
        $this->statut = htmlspecialchars(strip_tags($this->statut));
        $this->priorite = htmlspecialchars(strip_tags($this->priorite));

        $stmt->bindParam(":titre", $this->titre);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":client", $this->client);
        $stmt->bindParam(":technicien", $this->technicien);
        $stmt->bindParam(":statut", $this->statut);
        $stmt->bindParam(":priorite", $this->priorite);
        $stmt->bindParam(":date_creation", $this->date_creation);
        $stmt->bindParam(":date_intervention", $this->date_intervention);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function read() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY date_creation DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row) {
            $this->titre = $row['titre'];
            $this->description = $row['description'];
            $this->client = $row['client'];
            $this->technicien = $row['technicien'];
            $this->statut = $row['statut'];
            $this->priorite = $row['priorite'];
            $this->date_creation = $row['date_creation'];
            $this->date_intervention = $row['date_intervention'];
            $this->date_fin = $row['date_fin'];
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET titre=:titre, description=:description, client=:client, 
                      technicien=:technicien, statut=:statut, priorite=:priorite, 
                      date_intervention=:date_intervention, date_fin=:date_fin
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $this->titre = htmlspecialchars(strip_tags($this->titre));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->client = htmlspecialchars(strip_tags($this->client));
        $this->technicien = htmlspecialchars(strip_tags($this->technicien));
        $this->statut = htmlspecialchars(strip_tags($this->statut));
        $this->priorite = htmlspecialchars(strip_tags($this->priorite));
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(":titre", $this->titre);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":client", $this->client);
        $stmt->bindParam(":technicien", $this->technicien);
        $stmt->bindParam(":statut", $this->statut);
        $stmt->bindParam(":priorite", $this->priorite);
        $stmt->bindParam(":date_intervention", $this->date_intervention);
        $stmt->bindParam(":date_fin", $this->date_fin);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>