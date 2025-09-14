<?php
// Créer des interventions de test
require_once 'config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->getConnection();

    if ($db === null) {
        throw new Exception("Connexion à la base de données échouée");
    }

    // Récupérer un utilisateur admin pour le createur_id
    $stmt = $db->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    $createur_id = $admin['id'] ?? 1;

    // Récupérer des techniciens
    $stmt = $db->prepare("SELECT id, prenom, nom FROM users WHERE role IN ('technicien', 'manager') LIMIT 3");
    $stmt->execute();
    $techniciens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Interventions de test
    $interventions_test = [
        [
            'titre' => 'Réparation climatisation bureau',
            'description' => 'La climatisation du bureau principal ne fonctionne plus. Vérifier le système et effectuer les réparations nécessaires.',
            'client_nom' => 'Entreprise ABC',
            'statut' => 'En cours',
            'priorite' => 'Haute',
            'type_intervention' => 'Réparation',
            'temps_estime' => 4,
            'cout_prevu' => 250.00,
            'date_intervention' => date('Y-m-d H:i:s', strtotime('+1 day')),
            'date_limite' => date('Y-m-d H:i:s', strtotime('+3 days'))
        ],
        [
            'titre' => 'Installation nouveau poste informatique',
            'description' => 'Installation et configuration d\'un nouveau poste de travail avec logiciels métier.',
            'client_nom' => 'Cabinet Médical XYZ',
            'statut' => 'En attente',
            'priorite' => 'Normale',
            'type_intervention' => 'Installation',
            'temps_estime' => 2,
            'cout_prevu' => 150.00,
            'date_intervention' => date('Y-m-d H:i:s', strtotime('+2 days')),
            'date_limite' => date('Y-m-d H:i:s', strtotime('+5 days'))
        ],
        [
            'titre' => 'Maintenance préventive serveur',
            'description' => 'Maintenance trimestrielle du serveur principal : nettoyage, mise à jour, vérification des sauvegardes.',
            'client_nom' => 'Restaurant Le Gourmet',
            'statut' => 'Terminée',
            'priorite' => 'Basse',
            'type_intervention' => 'Maintenance',
            'temps_estime' => 3,
            'temps_reel' => 2.5,
            'cout_prevu' => 200.00,
            'cout_reel' => 180.00,
            'date_intervention' => date('Y-m-d H:i:s', strtotime('-2 days')),
            'date_fin' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'satisfaction_client' => 5,
            'notes_technicien' => 'Intervention réalisée sans problème. Client satisfait.'
        ],
        [
            'titre' => 'Dépannage réseau urgent',
            'description' => 'Panne réseau générale dans les locaux. Perte de connectivité internet et intranet.',
            'client_nom' => 'Agence Immobilière Central',
            'statut' => 'En cours',
            'priorite' => 'Urgente',
            'type_intervention' => 'Réparation',
            'temps_estime' => 1,
            'cout_prevu' => 100.00,
            'date_intervention' => date('Y-m-d H:i:s'),
            'date_limite' => date('Y-m-d H:i:s', strtotime('+1 day'))
        ],
        [
            'titre' => 'Formation utilisateurs logiciel comptable',
            'description' => 'Formation du personnel sur le nouveau logiciel de comptabilité. Session de 4h avec documentation.',
            'client_nom' => 'Comptabilité Dupont & Associés',
            'statut' => 'En attente',
            'priorite' => 'Normale',
            'type_intervention' => 'Autre',
            'temps_estime' => 4,
            'cout_prevu' => 300.00,
            'date_intervention' => date('Y-m-d H:i:s', strtotime('+1 week')),
            'date_limite' => date('Y-m-d H:i:s', strtotime('+2 weeks'))
        ],
        [
            'titre' => 'Diagnostic problème imprimante',
            'description' => 'L\'imprimante laser du service administratif produit des impressions de mauvaise qualité.',
            'client_nom' => 'École Primaire Saint-Jean',
            'statut' => 'En pause',
            'priorite' => 'Basse',
            'type_intervention' => 'Diagnostic',
            'temps_estime' => 1,
            'cout_prevu' => 80.00,
            'date_intervention' => date('Y-m-d H:i:s', strtotime('+3 days')),
            'date_limite' => date('Y-m-d H:i:s', strtotime('+1 week'))
        ]
    ];

    $created_interventions = [];

    foreach ($interventions_test as $index => $intervention_data) {
        // Assigner un technicien aléatoire si disponible
        $technicien = null;
        if (!empty($techniciens)) {
            $technicien = $techniciens[$index % count($techniciens)];
        }

        $query = "INSERT INTO interventions (
            titre, description, client_nom,
            technicien_id, technicien_nom, createur_id,
            statut, priorite, type_intervention,
            temps_estime, temps_reel, cout_prevu, cout_reel,
            date_creation, date_intervention, date_fin, date_limite,
            satisfaction_client, notes_technicien
        ) VALUES (
            :titre, :description, :client_nom,
            :technicien_id, :technicien_nom, :createur_id,
            :statut, :priorite, :type_intervention,
            :temps_estime, :temps_reel, :cout_prevu, :cout_reel,
            NOW(), :date_intervention, :date_fin, :date_limite,
            :satisfaction_client, :notes_technicien
        )";

        $stmt = $db->prepare($query);

        $params = [
            ':titre' => $intervention_data['titre'],
            ':description' => $intervention_data['description'],
            ':client_nom' => $intervention_data['client_nom'],
            ':technicien_id' => $technicien['id'] ?? null,
            ':technicien_nom' => $technicien ? $technicien['prenom'] . ' ' . $technicien['nom'] : null,
            ':createur_id' => $createur_id,
            ':statut' => $intervention_data['statut'],
            ':priorite' => $intervention_data['priorite'],
            ':type_intervention' => $intervention_data['type_intervention'],
            ':temps_estime' => $intervention_data['temps_estime'],
            ':temps_reel' => $intervention_data['temps_reel'] ?? null,
            ':cout_prevu' => $intervention_data['cout_prevu'],
            ':cout_reel' => $intervention_data['cout_reel'] ?? null,
            ':date_intervention' => $intervention_data['date_intervention'],
            ':date_fin' => $intervention_data['date_fin'] ?? null,
            ':date_limite' => $intervention_data['date_limite'],
            ':satisfaction_client' => $intervention_data['satisfaction_client'] ?? null,
            ':notes_technicien' => $intervention_data['notes_technicien'] ?? null
        ];

        if ($stmt->execute($params)) {
            $created_interventions[] = [
                'id' => $db->lastInsertId(),
                'titre' => $intervention_data['titre'],
                'statut' => $intervention_data['statut'],
                'technicien' => $technicien ? $technicien['prenom'] . ' ' . $technicien['nom'] : 'Non assigné'
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'message' => count($created_interventions) . ' interventions de test créées avec succès',
        'interventions' => $created_interventions
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
?>