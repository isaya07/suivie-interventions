<?php
/**
 * Test direct d'insertion SQL pour débugger
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

    // Test d'insertion très simple avec seulement les champs obligatoires + nouveaux
    try {
        $insert_query = "INSERT INTO interventions (
            titre,
            description,
            client_nom,
            createur_id,
            priorite,
            type_prestation_id,
            type_reglementaire,
            mode_pose,
            longueur_liaison_reseau,
            longueur_derivation_individuelle,
            distance_raccordement,
            date_reception_dossier,
            date_creation,
            date_modification
        ) VALUES (
            'Test Simple SQL',
            'Test direct SQL pour débugger',
            'Client Test Direct',
            1,
            'normale',
            10,
            'type_2',
            'souterrain',
            25,
            35,
            60,
            '2025-09-16 10:00:00',
            NOW(),
            NOW()
        )";

        $result = $db->exec($insert_query);

        if ($result !== false) {
            $intervention_id = $db->lastInsertId();

            // Vérification - récupérer l'intervention créée
            $select_query = "SELECT * FROM interventions WHERE id = :id";
            $stmt = $db->prepare($select_query);
            $stmt->bindValue(":id", $intervention_id);
            $stmt->execute();
            $intervention = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'message' => 'Intervention créée avec succès via SQL direct',
                'intervention_id' => $intervention_id,
                'rows_affected' => $result,
                'intervention_data' => $intervention
            ]);
        } else {
            $error_info = $db->errorInfo();
            echo json_encode([
                'success' => false,
                'message' => 'Erreur SQL direct',
                'sql_error' => $error_info,
                'query' => $insert_query
            ]);
        }

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur exception SQL',
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