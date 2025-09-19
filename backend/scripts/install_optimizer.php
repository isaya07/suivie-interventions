<?php
/**
 * Script d'installation de l'optimiseur de planning
 *
 * Applique le schéma de base de données et initialise les données de base
 */

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    if ($db === null) {
        die("Erreur de connexion à la base de données\n");
    }

    echo "Installation de l'optimiseur de planning...\n\n";

    // Lire et exécuter le fichier SQL
    $sqlFile = '../../database/planning_optimizer_schema.sql';

    if (!file_exists($sqlFile)) {
        die("Fichier SQL non trouvé: $sqlFile\n");
    }

    $sql = file_get_contents($sqlFile);

    // Diviser en requêtes individuelles
    $queries = explode(';', $sql);

    $successCount = 0;
    $errorCount = 0;

    foreach ($queries as $query) {
        $query = trim($query);

        if (empty($query)) continue;

        try {
            $db->exec($query);
            $successCount++;

            // Afficher un résumé de la requête
            $firstWords = implode(' ', array_slice(explode(' ', $query), 0, 3));
            echo "✓ $firstWords...\n";

        } catch (PDOException $e) {
            $errorCount++;
            echo "✗ Erreur: " . $e->getMessage() . "\n";
            echo "   Requête: " . substr($query, 0, 100) . "...\n";
        }
    }

    echo "\n=== Résumé de l'installation ===\n";
    echo "Requêtes réussies: $successCount\n";
    echo "Erreurs: $errorCount\n";

    if ($errorCount === 0) {
        echo "\n✓ Installation terminée avec succès!\n";

        // Ajouter quelques données de test
        ajouterDonneesTest($db);

    } else {
        echo "\n⚠ Installation terminée avec des erreurs.\n";
    }

} catch (Exception $e) {
    echo "Erreur fatale: " . $e->getMessage() . "\n";
}

/**
 * Ajoute des données de test pour l'optimiseur
 */
function ajouterDonneesTest($db) {
    echo "\nAjout de données de test...\n";

    try {
        // Ajouter des zones de techniciens
        $queryZones = "
            INSERT IGNORE INTO technicien_zones (technicien_id, zone_intervention, rayon_action, cout_deplacement_km)
            SELECT u.id, 'Paris-IDF', 50, 0.50
            FROM users u
            WHERE u.role IN ('technicien', 'manager')
            AND u.is_active = 1
            LIMIT 3
        ";
        $db->exec($queryZones);
        echo "✓ Zones de techniciens ajoutées\n";

        // Ajouter des disponibilités par défaut
        $queryDispo = "
            INSERT IGNORE INTO disponibilites_technicien (technicien_id, date_debut, date_fin, type_creneau)
            SELECT u.id,
                   CONCAT(CURDATE(), ' 08:00:00'),
                   CONCAT(CURDATE(), ' 18:00:00'),
                   'travail'
            FROM users u
            WHERE u.role IN ('technicien', 'manager')
            AND u.is_active = 1
            LIMIT 3
        ";
        $db->exec($queryDispo);
        echo "✓ Disponibilités par défaut ajoutées\n";

        // Ajouter des localisations de test pour les clients existants
        $queryLoc = "
            INSERT IGNORE INTO localisation (client_id, adresse_complete, latitude, longitude, ville, zone_intervention)
            SELECT c.id,
                   CONCAT(c.adresse, ', ', c.ville),
                   48.8566 + (RAND() - 0.5) * 0.2,
                   2.3522 + (RAND() - 0.5) * 0.2,
                   c.ville,
                   'Paris-IDF'
            FROM clients c
            WHERE c.adresse IS NOT NULL
            LIMIT 5
        ";
        $db->exec($queryLoc);
        echo "✓ Localisations de test ajoutées\n";

        // Mettre à jour les interventions existantes avec les localisations
        $queryUpdateInterventions = "
            UPDATE interventions i
            INNER JOIN clients c ON i.client_id = c.id
            INNER JOIN localisation l ON c.id = l.client_id
            SET i.localisation_id = l.id,
                i.duree_estimee_minutes = 120 + (RAND() * 120),
                i.fenetre_debut = CONCAT(DATE(i.date_intervention), ' 08:00:00'),
                i.fenetre_fin = CONCAT(DATE(i.date_intervention), ' 18:00:00')
            WHERE i.localisation_id IS NULL
            AND i.client_id IS NOT NULL
        ";
        $db->exec($queryUpdateInterventions);
        echo "✓ Interventions mises à jour avec localisations\n";

        echo "\n✓ Données de test ajoutées avec succès!\n";

    } catch (Exception $e) {
        echo "Erreur lors de l'ajout des données de test: " . $e->getMessage() . "\n";
    }
}

/**
 * Vérification de l'installation
 */
function verifierInstallation($db) {
    echo "\n=== Vérification de l'installation ===\n";

    $tables = [
        'localisation',
        'technicien_zones',
        'disponibilites_technicien',
        'temps_trajet',
        'planning_optimise',
        'planning_creneaux',
        'parametres_optimisation'
    ];

    foreach ($tables as $table) {
        try {
            $query = "SELECT COUNT(*) as count FROM $table";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            echo "✓ Table $table: {$result['count']} enregistrement(s)\n";

        } catch (Exception $e) {
            echo "✗ Erreur table $table: " . $e->getMessage() . "\n";
        }
    }
}

// Exécuter la vérification à la fin
if (isset($db)) {
    verifierInstallation($db);
}

echo "\n=== Installation terminée ===\n";
echo "Vous pouvez maintenant utiliser l'optimiseur de planning à l'adresse:\n";
echo "http://localhost:3002/planning/optimizer\n\n";
?>