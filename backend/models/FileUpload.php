<?php
// backend/models/FileUpload.php
class FileUpload {
    private $conn;
    private $table_name = "fichiers_intervention";

    public $id;
    public $intervention_id;
    public $nom_fichier;
    public $nom_original;
    public $chemin_fichier;
    public $type_mime;
    public $taille;
    public $type_fichier;
    public $description;
    public $uploaded_by;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function upload($intervention_id, $files, $user_id, $type_fichier = 'autre', $description = '') {
        $upload_dir = '../uploads/interventions/' . $intervention_id . '/';
        
        // Créer le dossier s'il n'existe pas
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $uploaded_files = [];

        foreach($files['name'] as $key => $name) {
            if($files['error'][$key] == 0) {
                $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xlsx', 'xls', 'txt'];
                
                if(!in_array($extension, $allowed_extensions)) {
                    continue;
                }

                $nom_fichier = uniqid() . '_' . time() . '.' . $extension;
                $chemin_complet = $upload_dir . $nom_fichier;

                if(move_uploaded_file($files['tmp_name'][$key], $chemin_complet)) {
                    $query = "INSERT INTO " . $this->table_name . " 
                              SET intervention_id=:intervention_id, nom_fichier=:nom_fichier, 
                                  nom_original=:nom_original, chemin_fichier=:chemin_fichier, 
                                  type_mime=:type_mime, taille=:taille, type_fichier=:type_fichier, 
                                  description=:description, uploaded_by=:uploaded_by";

                    $stmt = $this->conn->prepare($query);

                    $stmt->bindParam(":intervention_id", $intervention_id);
                    $stmt->bindParam(":nom_fichier", $nom_fichier);
                    $stmt->bindParam(":nom_original", $name);
                    $stmt->bindParam(":chemin_fichier", $chemin_complet);
                    $stmt->bindParam(":type_mime", $files['type'][$key]);
                    $stmt->bindParam(":taille", $files['size'][$key]);
                    $stmt->bindParam(":type_fichier", $type_fichier);
                    $stmt->bindParam(":description", $description);
                    $stmt->bindParam(":uploaded_by", $user_id);

                    if($stmt->execute()) {
                        $uploaded_files[] = [
                            'id' => $this->conn->lastInsertId(),
                            'nom_original' => $name,
                            'nom_fichier' => $nom_fichier,
                            'taille' => $files['size'][$key],
                            'type_fichier' => $type_fichier
                        ];
                    }
                }
            }
        }

        return $uploaded_files;
    }

    public function getByIntervention($intervention_id) {
        $query = "SELECT f.*, u.nom as uploader_nom, u.prenom as uploader_prenom 
                  FROM " . $this->table_name . " f
                  LEFT JOIN users u ON f.uploaded_by = u.id
                  WHERE f.intervention_id = :intervention_id 
                  ORDER BY f.uploaded_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":intervention_id", $intervention_id);
        $stmt->execute();
        
        return $stmt;
    }

    public function delete($file_id, $user_id, $user_role) {
        // Récupérer les infos du fichier
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $file_id);
        $stmt->execute();
        
        $file = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($file) {
            // Vérifier les permissions (admin ou propriétaire du fichier)
            if($user_role === 'admin' || $file['uploaded_by'] == $user_id) {
                // Supprimer le fichier physique
                if(file_exists($file['chemin_fichier'])) {
                    unlink($file['chemin_fichier']);
                }
                
                // Supprimer de la base de données
                $delete_query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
                $delete_stmt = $this->conn->prepare($delete_query);
                $delete_stmt->bindParam(":id", $file_id);
                
                return $delete_stmt->execute();
            }
        }
        
        return false;
    }
}
?>