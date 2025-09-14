<?php
// Script de test pour l'API clients
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclure les fichiers nécessaires
include_once 'config/database.php';
include_once 'config/auth.php';
include_once 'models/Client.php';

echo "<h2>Test de l'API Clients</h2>";

// Test de connexion à la base de données
echo "<h3>1. Test de connexion à la base de données</h3>";
try {
    $database = new Database();
    $db = $database->getConnection();
    echo "✅ Connexion à la base de données réussie<br>";
} catch (Exception $e) {
    echo "❌ Erreur de connexion : " . $e->getMessage() . "<br>";
    exit;
}

// Test de la table clients
echo "<h3>2. Test de la table clients</h3>";
try {
    $stmt = $db->query("DESCRIBE clients");
    echo "✅ Table clients accessible<br>";
    echo "<strong>Colonnes disponibles :</strong><br>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")<br>";
    }
} catch (Exception $e) {
    echo "❌ Erreur table clients : " . $e->getMessage() . "<br>";
}

// Test du modèle Client
echo "<h3>3. Test du modèle Client</h3>";
try {
    $client = new Client($db);
    echo "✅ Modèle Client instancié<br>";

    $stmt = $client->read();
    $clients = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $clients[] = $row;
    }

    echo "✅ Lecture des clients réussie - " . count($clients) . " clients trouvés<br>";

    if (count($clients) > 0) {
        echo "<strong>Premier client :</strong><br>";
        foreach ($clients[0] as $key => $value) {
            echo "- $key: $value<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Erreur modèle Client : " . $e->getMessage() . "<br>";
}

// Test JSON de l'API
echo "<h3>4. Test API JSON</h3>";
try {
    $client = new Client($db);
    $stmt = $client->read();
    $clients = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $clients[] = array(
            "id" => $row['id'],
            "nom" => $row['nom'],
            "email" => $row['email'],
            "telephone" => $row['telephone'],
            "adresse" => $row['adresse'],
            "ville" => $row['ville'],
            "code_postal" => $row['code_postal'],
            "contact_principal" => $row['contact_principal'],
            "notes" => $row['notes'] ?? null,
            "latitude" => $row['latitude'] ?? null,
            "longitude" => $row['longitude'] ?? null,
            "user_id" => $row['user_id'] ?? null,
            "created_at" => $row['created_at'],
            "updated_at" => $row['updated_at']
        );
    }

    echo "✅ Format JSON généré avec succès<br>";
    echo "<strong>JSON de test :</strong><br>";
    echo "<pre>" . json_encode(array("records" => $clients), JSON_PRETTY_PRINT) . "</pre>";

} catch (Exception $e) {
    echo "❌ Erreur API JSON : " . $e->getMessage() . "<br>";
}
?>