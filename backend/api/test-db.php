<?php
/**
 * API de test pour vérifier la base de données
 */

// Configuration CORS et en-têtes
include_once 'cors.php';
header("Content-Type: application/json; charset=UTF-8");

// Import des dépendances
include_once '../config/database.php';

try {
    // Test de connexion à la base de données
    $database = new Database();
    $db = $database->getConnection();

    if ($db === null) {
        echo json_encode([
            'success' => false,
            'message' => 'Impossible de se connecter à la base de données',
            'test' => 'connection_failed'
        ]);
        exit;
    }

    // Test si la table types_prestations existe
    $tableExists = false;
    try {
        $stmt = $db->query("DESCRIBE types_prestations");
        $tableExists = true;
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $columns = [];
        $tableExists = false;
    }

    // Test pour compter les enregistrements
    $count = 0;
    $data = [];
    if ($tableExists) {
        try {
            $stmt = $db->query("SELECT COUNT(*) as count FROM types_prestations");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $count = $result['count'];

            // Récupérer quelques exemples
            $stmt = $db->query("SELECT * FROM types_prestations LIMIT 5");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $count = -1;
            $data = ['error' => $e->getMessage()];
        }
    }

    // Test d'autres tables importantes
    $tables_to_check = ['users', 'interventions', 'clients'];
    $tables_status = [];

    foreach ($tables_to_check as $table) {
        try {
            $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $tables_status[$table] = [
                'exists' => true,
                'count' => $result['count']
            ];
        } catch (PDOException $e) {
            $tables_status[$table] = [
                'exists' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'database_connection' => 'OK',
        'types_prestations' => [
            'table_exists' => $tableExists,
            'columns' => $columns,
            'record_count' => $count,
            'sample_data' => $data
        ],
        'other_tables' => $tables_status,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors du test de la base de données',
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>