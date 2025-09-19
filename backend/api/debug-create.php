<?php
/**
 * Script de debug pour la création d'interventions électriques
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
        echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
        exit;
    }

    $auth = new Auth($db);

    // Vérification de l'authentification
    if (!$auth->isAuthenticated()) {
        echo json_encode(['success' => false, 'message' => 'Authentification requise']);
        exit;
    }

    // Test simple d'insertion avec nouveaux champs
    try {
        $current_user = $auth->getCurrentUser();

        // Récupération du type de prestation
        $prestation_query = "SELECT * FROM types_prestations WHERE id = :id";
        $prestation_stmt = $db->prepare($prestation_query);
        $prestation_stmt->bindParam(":id", $_POST['type_prestation_id'] ?? 10);
        $prestation_stmt->execute();
        $prestation = $prestation_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$prestation) {
            echo json_encode(['success' => false, 'message' => 'Type de prestation non trouvé']);
            exit;
        }

        // Test d'insertion simple
        $insert_query = "INSERT INTO interventions SET
            titre = :titre,
            description = :description,
            client_nom = :client_nom,
            createur_id = :createur_id,
            priorite = :priorite,
            type_prestation_id = :type_prestation_id,

            -- Nouveaux champs Enedis
            type_reglementaire = :type_reglementaire,
            mode_pose = :mode_pose,
            longueur_liaison_reseau = :longueur_liaison_reseau,
            longueur_derivation_individuelle = :longueur_derivation_individuelle,
            distance_raccordement = :distance_raccordement,

            -- Dates de suivi du processus
            date_reception_dossier = :date_reception_dossier,
            date_etude_technique = :date_etude_technique,

            date_creation = NOW(),
            date_modification = NOW()";

        $stmt = $db->prepare($insert_query);

        // Données de test
        $titre = 'Test Debug Intervention';
        $description = 'Test debug avec nouveaux champs';
        $client_nom = 'Client Debug';
        $priorite = 'normale';
        $type_prestation_id = 10;
        $type_reglementaire = 'type_2';
        $mode_pose = 'souterrain';
        $longueur_liaison_reseau = 25;
        $longueur_derivation_individuelle = 35;
        $distance_raccordement = 60;
        $date_reception_dossier = '2025-09-16 10:00:00';
        $date_etude_technique = '2025-09-16 14:00:00';

        // Binding des paramètres
        $stmt->bindParam(":titre", $titre);
        $stmt->bindParam(":description", $description);
        $stmt->bindParam(":client_nom", $client_nom);
        $stmt->bindParam(":createur_id", $current_user['id']);
        $stmt->bindParam(":priorite", $priorite);
        $stmt->bindParam(":type_prestation_id", $type_prestation_id);
        $stmt->bindParam(":type_reglementaire", $type_reglementaire);
        $stmt->bindParam(":mode_pose", $mode_pose);
        $stmt->bindParam(":longueur_liaison_reseau", $longueur_liaison_reseau);
        $stmt->bindParam(":longueur_derivation_individuelle", $longueur_derivation_individuelle);
        $stmt->bindParam(":distance_raccordement", $distance_raccordement);
        $stmt->bindParam(":date_reception_dossier", $date_reception_dossier);
        $stmt->bindParam(":date_etude_technique", $date_etude_technique);

        if($stmt->execute()) {
            $intervention_id = $db->lastInsertId();

            echo json_encode([
                'success' => true,
                'message' => 'Intervention créée avec succès',
                'data' => [
                    'id' => $intervention_id,
                    'prestation_info' => $prestation,
                    'inserted_data' => [
                        'titre' => $titre,
                        'type_reglementaire' => $type_reglementaire,
                        'mode_pose' => $mode_pose,
                        'longueur_liaison_reseau' => $longueur_liaison_reseau,
                        'longueur_derivation_individuelle' => $longueur_derivation_individuelle,
                        'distance_raccordement' => $distance_raccordement
                    ]
                ]
            ]);
        } else {
            $error_info = $stmt->errorInfo();
            echo json_encode([
                'success' => false,
                'message' => 'Erreur SQL',
                'sql_error' => $error_info,
                'query' => $insert_query
            ]);
        }

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur exception',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur générale',
        'error' => $e->getMessage()
    ]);
}
?>