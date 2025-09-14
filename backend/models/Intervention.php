<?php
// models/Intervention.php
class Intervention {
    private $conn;
    private $table_name = "interventions";

    // Updated properties to match new database schema
    public $id;
    public $numero;
    public $titre;
    public $description;
    public $client_id;
    public $client_nom; // For backward compatibility
    public $technicien_id;
    public $technicien_nom; // For backward compatibility
    public $createur_id;
    public $statut;
    public $priorite;
    public $type_intervention;
    public $temps_estime;
    public $temps_reel;
    public $cout_prevu;
    public $cout_reel;
    public $date_creation;
    public $date_intervention;
    public $date_fin;
    public $date_limite;
    public $notes_technicien;
    public $satisfaction_client;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET titre=:titre, description=:description,
                      client_id=:client_id, client_nom=:client_nom,
                      technicien_id=:technicien_id, technicien_nom=:technicien_nom,
                      createur_id=:createur_id, statut=:statut, priorite=:priorite,
                      type_intervention=:type_intervention, temps_estime=:temps_estime,
                      cout_prevu=:cout_prevu, date_creation=:date_creation,
                      date_intervention=:date_intervention, date_limite=:date_limite";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->titre = htmlspecialchars(strip_tags($this->titre));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->client_nom = htmlspecialchars(strip_tags($this->client_nom ?? ''));
        $this->technicien_nom = htmlspecialchars(strip_tags($this->technicien_nom ?? ''));
        $this->statut = htmlspecialchars(strip_tags($this->statut));
        $this->priorite = htmlspecialchars(strip_tags($this->priorite));
        $this->type_intervention = htmlspecialchars(strip_tags($this->type_intervention ?? 'Réparation'));

        $stmt->bindParam(":titre", $this->titre);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":client_id", $this->client_id);
        $stmt->bindParam(":client_nom", $this->client_nom);
        $stmt->bindParam(":technicien_id", $this->technicien_id);
        $stmt->bindParam(":technicien_nom", $this->technicien_nom);
        $stmt->bindParam(":createur_id", $this->createur_id);
        $stmt->bindParam(":statut", $this->statut);
        $stmt->bindParam(":priorite", $this->priorite);
        $stmt->bindParam(":type_intervention", $this->type_intervention);
        $stmt->bindParam(":temps_estime", $this->temps_estime);
        $stmt->bindParam(":cout_prevu", $this->cout_prevu);
        $stmt->bindParam(":date_creation", $this->date_creation);
        $stmt->bindParam(":date_intervention", $this->date_intervention);
        $stmt->bindParam(":date_limite", $this->date_limite);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
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
            $this->numero = $row['numero'];
            $this->titre = $row['titre'];
            $this->description = $row['description'];
            $this->client_id = $row['client_id'];
            $this->client_nom = $row['client_nom'];
            $this->technicien_id = $row['technicien_id'];
            $this->technicien_nom = $row['technicien_nom'];
            $this->createur_id = $row['createur_id'];
            $this->statut = $row['statut'];
            $this->priorite = $row['priorite'];
            $this->type_intervention = $row['type_intervention'];
            $this->temps_estime = $row['temps_estime'];
            $this->temps_reel = $row['temps_reel'];
            $this->cout_prevu = $row['cout_prevu'];
            $this->cout_reel = $row['cout_reel'];
            $this->date_creation = $row['date_creation'];
            $this->date_intervention = $row['date_intervention'];
            $this->date_fin = $row['date_fin'];
            $this->date_limite = $row['date_limite'];
            $this->notes_technicien = $row['notes_technicien'];
            $this->satisfaction_client = $row['satisfaction_client'];
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET titre=:titre, description=:description,
                      client_id=:client_id, client_nom=:client_nom,
                      technicien_id=:technicien_id, technicien_nom=:technicien_nom,
                      statut=:statut, priorite=:priorite,
                      type_intervention=:type_intervention,
                      temps_estime=:temps_estime, temps_reel=:temps_reel,
                      cout_prevu=:cout_prevu, cout_reel=:cout_reel,
                      date_intervention=:date_intervention, date_fin=:date_fin,
                      date_limite=:date_limite, notes_technicien=:notes_technicien,
                      satisfaction_client=:satisfaction_client
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $this->titre = htmlspecialchars(strip_tags($this->titre));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->client_nom = htmlspecialchars(strip_tags($this->client_nom ?? ''));
        $this->technicien_nom = htmlspecialchars(strip_tags($this->technicien_nom ?? ''));
        $this->statut = htmlspecialchars(strip_tags($this->statut));
        $this->priorite = htmlspecialchars(strip_tags($this->priorite));
        $this->type_intervention = htmlspecialchars(strip_tags($this->type_intervention ?? ''));
        $this->notes_technicien = htmlspecialchars(strip_tags($this->notes_technicien ?? ''));
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(":titre", $this->titre);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":client_id", $this->client_id);
        $stmt->bindParam(":client_nom", $this->client_nom);
        $stmt->bindParam(":technicien_id", $this->technicien_id);
        $stmt->bindParam(":technicien_nom", $this->technicien_nom);
        $stmt->bindParam(":statut", $this->statut);
        $stmt->bindParam(":priorite", $this->priorite);
        $stmt->bindParam(":type_intervention", $this->type_intervention);
        $stmt->bindParam(":temps_estime", $this->temps_estime);
        $stmt->bindParam(":temps_reel", $this->temps_reel);
        $stmt->bindParam(":cout_prevu", $this->cout_prevu);
        $stmt->bindParam(":cout_reel", $this->cout_reel);
        $stmt->bindParam(":date_intervention", $this->date_intervention);
        $stmt->bindParam(":date_fin", $this->date_fin);
        $stmt->bindParam(":date_limite", $this->date_limite);
        $stmt->bindParam(":notes_technicien", $this->notes_technicien);
        $stmt->bindParam(":satisfaction_client", $this->satisfaction_client);
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