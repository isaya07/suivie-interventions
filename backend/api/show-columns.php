<?php
/**
 * Script pour afficher les colonnes des tables
 */

// Configuration CORS et en-têtes
include_once 'cors.php';
header("Content-Type: application/json; charset=UTF-8");

// Import des dépendances
include_once '../config/database.php';

try {
    // Initialisation des services
    $database = new Database();
    $db = $database->getConnection();

    if ($db === null) {
        echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
        exit;
    }

    // Afficher les colonnes de la table interventions
    $columns = $db->query("SHOW COLUMNS FROM interventions")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'table' => 'interventions',
        'columns' => $columns,
        'column_names' => array_column($columns, 'Field')
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur',
        'error' => $e->getMessage()
    ]);
}
?>