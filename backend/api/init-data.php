<?php
/**
 * Script d'initialisation des données de base
 * À exécuter une seule fois pour créer les données de test
 */

// Configuration CORS et en-têtes
include_once 'cors.php';
header("Content-Type: application/json; charset=UTF-8");

// Import des dépendances
include_once '../config/database.php';

try {
    // Connexion à la base de données
    $database = new Database();
    $db = $database->getConnection();

    if ($db === null) {
        echo json_encode([
            'success' => false,
            'message' => 'Impossible de se connecter à la base de données'
        ]);
        exit;
    }

    $results = [];

    // 1. Créer la table types_prestations si elle n'existe pas
    $create_table_query = "
    CREATE TABLE IF NOT EXISTS types_prestations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(255) NOT NULL,
        code VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        has_terrassement BOOLEAN DEFAULT FALSE,
        duree_branchement_heures DECIMAL(4,2) DEFAULT 4.00,
        duree_terrassement_heures DECIMAL(4,2) DEFAULT NULL,
        date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $db->exec($create_table_query);
    $results[] = "Table types_prestations créée ou vérifiée";

    // 2. Vérifier si des données existent déjà
    $count_stmt = $db->query("SELECT COUNT(*) as count FROM types_prestations");
    $count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];

    if ($count == 0) {
        // 3. Insérer les données de base
        $insert_data = "
        INSERT INTO types_prestations (nom, code, duree_branchement_heures, duree_terrassement_heures, has_terrassement, description) VALUES
        ('Branchement électrique seul', 'BRANCHEMENT_SEUL', 4.00, NULL, FALSE, 'Installation et raccordement électrique uniquement'),
        ('Branchement avec terrassement', 'BRANCHEMENT_TERRASSEMENT', 4.00, 4.00, TRUE, 'Installation électrique complète avec travaux de terrassement'),
        ('Branchement simple maison', 'BRANCHEMENT_MAISON', 3.00, NULL, FALSE, 'Branchement standard pour habitation individuelle'),
        ('Branchement triphasé', 'BRANCHEMENT_TRIPHASE', 6.00, NULL, FALSE, 'Installation électrique triphasée pour gros consommateurs'),
        ('Branchement avec tranchée', 'BRANCHEMENT_TRANCHEE', 5.00, 6.00, TRUE, 'Installation complète avec creusement de tranchée'),
        ('Réparation branchement', 'REPARATION_BRANCHEMENT', 2.00, NULL, FALSE, 'Réparation et remise en état d''un branchement existant')
        ";

        $db->exec($insert_data);
        $results[] = "6 types de prestations insérés";
    } else {
        $results[] = "Des données existent déjà ($count enregistrements)";
    }

    // 4. Mettre à jour la table users pour les taux horaires si elle existe
    try {
        // Vérifier si la colonne taux_horaire existe
        $columns_stmt = $db->query("SHOW COLUMNS FROM users LIKE 'taux_horaire'");
        $has_taux_horaire = $columns_stmt->rowCount() > 0;

        if (!$has_taux_horaire) {
            $alter_users = "
            ALTER TABLE users
            ADD COLUMN taux_horaire DECIMAL(6,2) DEFAULT 40.00 COMMENT 'Taux horaire du technicien en euros',
            ADD COLUMN specialite_principale ENUM('cableur', 'terrassier', 'autre') DEFAULT 'autre' COMMENT 'Spécialité principale du technicien'
            ";
            $db->exec($alter_users);
            $results[] = "Colonnes taux_horaire et specialite_principale ajoutées à la table users";
        }

        // Mettre à jour les taux si ils sont NULL
        $update_taux = "
        UPDATE users SET
            taux_horaire = CASE
                WHEN role = 'technicien' THEN 45.00
                ELSE 40.00
            END,
            specialite_principale = 'cableur'
        WHERE taux_horaire IS NULL OR taux_horaire = 0
        ";
        $updated = $db->exec($update_taux);
        $results[] = "Taux horaires mis à jour pour $updated utilisateurs";

    } catch (PDOException $e) {
        $results[] = "Info: Table users non modifiée - " . $e->getMessage();
    }

    // 5. Récupérer un échantillon des données créées
    $sample_stmt = $db->query("SELECT * FROM types_prestations ORDER BY id LIMIT 3");
    $sample_data = $sample_stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'Données initialisées avec succès',
        'actions_performed' => $results,
        'sample_data' => $sample_data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'initialisation des données',
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>