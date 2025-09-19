<?php
/**
 * Script d'application des migrations de base de données
 * À utiliser uniquement en développement/test
 */

// Configuration CORS et en-têtes
include_once 'cors.php';
header("Content-Type: application/json; charset=UTF-8");

// Import des dépendances
include_once '../config/database.php';
include_once '../config/auth.php';

try {
    // Initialisation des services
    $database = new Database();
    $db = $database->getConnection();

    if ($db === null) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
        exit;
    }

    $auth = new Auth($db);

    // Vérification de l'authentification et des permissions admin
    if (!$auth->isAuthenticated()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentification requise']);
        exit;
    }

    $current_user = $auth->getCurrentUser();
    if (!$current_user || $current_user['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permissions admin requises']);
        exit;
    }

    $method = $_SERVER['REQUEST_METHOD'];
    $migration = $_GET['migration'] ?? '';

    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Méthode POST requise']);
        exit;
    }

    $results = [];
    $errors = [];

    // Application de la migration types_branchements_update.sql
    if ($migration === 'types_branchements' || $migration === 'all') {
        try {
            error_log("Début application migration types_branchements_update.sql");

            // Étendre types_prestations
            $db->exec("ALTER TABLE types_prestations
                ADD COLUMN IF NOT EXISTS type_reglementaire ENUM('type_1', 'type_2', 'non_applicable') DEFAULT 'non_applicable',
                ADD COLUMN IF NOT EXISTS mode_pose ENUM('aerien', 'souterrain', 'aerosouterrain', 'souterrain_boite', 'di_seule') DEFAULT 'souterrain'");

            // Étendre interventions
            $db->exec("ALTER TABLE interventions
                ADD COLUMN IF NOT EXISTS type_prestation_id INT DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS type_reglementaire ENUM('type_1', 'type_2') DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS mode_pose ENUM('aerien', 'souterrain', 'aerosouterrain', 'souterrain_boite', 'di_seule') DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS distance_raccordement INT DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS longueur_liaison_reseau INT DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS longueur_derivation_individuelle INT DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS date_reception_dossier DATETIME DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS date_etude_technique DATETIME DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS date_validation_devis DATETIME DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS date_realisation_terrassement DATETIME DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS date_realisation_cablage DATETIME DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS date_mise_en_service DATETIME DEFAULT NULL");

            // Ajouter les contraintes et index
            $db->exec("ALTER TABLE interventions
                ADD INDEX IF NOT EXISTS idx_type_prestation_id (type_prestation_id)");

            // Optionnel : ajouter la contrainte de clé étrangère (à faire avec prudence en production)
            // $db->exec("ALTER TABLE interventions ADD FOREIGN KEY (type_prestation_id) REFERENCES types_prestations(id) ON DELETE SET NULL");

            // Mise à jour des types existants
            $db->exec("UPDATE types_prestations SET
                type_reglementaire = 'type_1',
                mode_pose = 'souterrain'
                WHERE type_reglementaire = 'non_applicable'");

            // Ajouter les nouveaux types spécialisés Enedis
            $types_sql = "INSERT IGNORE INTO types_prestations (nom, code, duree_branchement_heures, duree_terrassement_heures, has_terrassement, type_reglementaire, mode_pose, description) VALUES
                ('Branchement Aérien Type 1', 'AERIEN_TYPE_1', 3.00, NULL, FALSE, 'type_1', 'aerien', 'Branchement aérien < 30m, compteur intérieur'),
                ('Branchement Aérien Type 2', 'AERIEN_TYPE_2', 3.50, NULL, FALSE, 'type_2', 'aerien', 'Branchement aérien > 30m, coffret limite propriété'),
                ('Branchement Souterrain Type 1', 'SOUTERRAIN_TYPE_1', 4.00, 4.00, TRUE, 'type_1', 'souterrain', 'Branchement souterrain < 30m, compteur intérieur'),
                ('Branchement Souterrain Type 2', 'SOUTERRAIN_TYPE_2', 4.00, 4.50, TRUE, 'type_2', 'souterrain', 'Branchement souterrain > 30m, coffret limite propriété'),
                ('Branchement Aérosouterrain Type 1', 'AEROSOUTERRAIN_TYPE_1', 3.50, 2.50, TRUE, 'type_1', 'aerosouterrain', 'Aérosouterrain < 30m, terrassement partiel'),
                ('Branchement Aérosouterrain Type 2', 'AEROSOUTERRAIN_TYPE_2', 3.50, 3.00, TRUE, 'type_2', 'aerosouterrain', 'Aérosouterrain > 30m, terrassement partiel + coffret'),
                ('Souterrain sur Boîte Type 1', 'SOUTERRAIN_BOITE_TYPE_1', 4.50, 3.00, TRUE, 'type_1', 'souterrain_boite', 'Raccordement depuis boîte existante < 30m'),
                ('Souterrain sur Boîte Type 2', 'SOUTERRAIN_BOITE_TYPE_2', 4.50, 3.50, TRUE, 'type_2', 'souterrain_boite', 'Raccordement depuis boîte existante > 30m'),
                ('DI Seule Type 1', 'DI_SEULE_TYPE_1', 2.00, 2.00, TRUE, 'type_1', 'di_seule', 'Dérivation individuelle seule < 30m'),
                ('DI Seule Type 2', 'DI_SEULE_TYPE_2', 2.00, 3.00, TRUE, 'type_2', 'di_seule', 'Dérivation individuelle seule > 30m')";

            $db->exec($types_sql);

            $results[] = "Migration types_branchements appliquée avec succès";

        } catch (Exception $e) {
            $errors[] = "Erreur migration types_branchements: " . $e->getMessage();
            error_log("Erreur migration: " . $e->getMessage());
        }
    }

    // Vérification finale
    try {
        $check_types = $db->query("SELECT COUNT(*) as count FROM types_prestations WHERE type_reglementaire IS NOT NULL")->fetch()['count'];
        $check_columns = $db->query("SHOW COLUMNS FROM interventions LIKE 'type_reglementaire'")->fetch();

        $results[] = "Vérification: {$check_types} types avec type_reglementaire";
        $results[] = "Colonne type_reglementaire dans interventions: " . ($check_columns ? 'OK' : 'MANQUANTE');

    } catch (Exception $e) {
        $errors[] = "Erreur vérification: " . $e->getMessage();
    }

    // Réponse
    http_response_code(200);
    echo json_encode([
        'success' => count($errors) === 0,
        'message' => count($errors) === 0 ? 'Migrations appliquées avec succès' : 'Erreurs lors de l\'application',
        'results' => $results,
        'errors' => $errors,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    error_log("Erreur générale apply-migrations.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur',
        'error' => $e->getMessage()
    ]);
}
?>