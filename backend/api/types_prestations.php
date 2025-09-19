<?php
/**
 * API simple de gestion des types de prestations
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

    // Vérification de l'authentification
    if (!$auth->isAuthenticated()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentification requise']);
        exit;
    }

    $method = $_SERVER['REQUEST_METHOD'];
    $category = $_GET['category'] ?? '';

    // Pour l'instant, seul GET est supporté
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
        exit;
    }

    // Requête simple pour récupérer les types de prestations
    $query = "SELECT * FROM types_prestations WHERE 1=1";
    $params = [];

    // Filtrage par catégorie électrique si demandé
    if (!empty($category) && strtolower($category) === 'electrique') {
        $query .= " AND (code LIKE '%BRANCHEMENT%' OR code LIKE '%ELECTRIQUE%')";
    }

    $query .= " ORDER BY nom ASC";

    $stmt = $db->prepare($query);
    $stmt->execute();

    $types_prestations = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $prestation = [
            'id' => (int)$row['id'],
            'nom' => $row['nom'],
            'code' => $row['code'],
            'description' => $row['description'] ?? '',
            'has_terrassement' => (bool)$row['has_terrassement'],
            'duree_branchement_heures' => (float)$row['duree_branchement_heures'],
            'duree_terrassement_heures' => (float)$row['duree_terrassement_heures'],
            'type_reglementaire' => $row['type_reglementaire'] ?? 'type_1',
            'mode_pose' => $row['mode_pose'] ?? 'souterrain',
            'date_creation' => $row['date_creation'] ?? null
        ];

        // Calcul des coûts estimés
        $taux_branchement = 45.00; // €/heure
        $taux_terrassement = 38.00; // €/heure

        $prestation['cout_branchement_estime'] = $prestation['duree_branchement_heures'] * $taux_branchement;

        if ($prestation['has_terrassement']) {
            $prestation['cout_terrassement_estime'] = $prestation['duree_terrassement_heures'] * $taux_terrassement;
            $prestation['cout_total_estime'] = $prestation['cout_branchement_estime'] + $prestation['cout_terrassement_estime'];
        } else {
            $prestation['cout_terrassement_estime'] = 0;
            $prestation['cout_total_estime'] = $prestation['cout_branchement_estime'];
        }

        $types_prestations[] = $prestation;
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $types_prestations,
        'total' => count($types_prestations),
        'filter' => !empty($category) ? $category : 'all'
    ]);

} catch (Exception $e) {
    error_log("Erreur types_prestations.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur',
        'error' => $e->getMessage()
    ]);
}
?>