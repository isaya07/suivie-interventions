<?php
// Test de l'API des interventions
header('Content-Type: application/json');
include_once 'api/cors.php';

require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'models/Intervention.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    if ($db === null) {
        throw new Exception("Connexion à la base de données échouée");
    }

    $intervention = new Intervention($db);

    // Test direct de la méthode read()
    $stmt = $intervention->read();
    $num = $stmt->rowCount();

    echo json_encode([
        'success' => true,
        'total_interventions' => $num,
        'debug_info' => 'Test direct de la base de données',
        'sample_data' => []
    ]);

    // Récupérer quelques exemples
    if ($num > 0) {
        $samples = [];
        $count = 0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC) && $count < 3) {
            $samples[] = $row;
            $count++;
        }

        echo json_encode([
            'success' => true,
            'total_interventions' => $num,
            'sample_data' => $samples,
            'columns' => array_keys($samples[0] ?? [])
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>