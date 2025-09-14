<?php
// Test de connexion à la base de données
header('Content-Type: application/json');

try {
    require_once 'config/database.php';

    $database = new Database();
    $db = $database->getConnection();

    if ($db === null) {
        throw new Exception("Connexion à la base de données échouée");
    }

    // Test une requête simple
    $stmt = $db->query("SELECT 1 as test");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result && $result['test'] == 1) {
        echo json_encode([
            'success' => true,
            'message' => 'Connexion à la base de données réussie'
        ]);
    } else {
        throw new Exception("Test de requête échoué");
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>