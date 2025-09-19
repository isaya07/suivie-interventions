<?php
/**
 * API d'optimisation de planning d'interventions
 *
 * Endpoints:
 * - POST /planning_optimizer.php?action=optimiser : Optimise le planning pour une période
 * - GET /planning_optimizer.php?action=plannings : Liste des plannings générés
 * - GET /planning_optimizer.php?action=planning&id=X : Détails d'un planning
 * - POST /planning_optimizer.php?action=appliquer : Applique un planning optimisé
 * - GET /planning_optimizer.php?action=parametres : Récupère les paramètres d'optimisation
 * - PUT /planning_optimizer.php?action=parametres : Met à jour les paramètres
 */

// Configuration CORS et en-têtes
include_once 'cors.php';
require_once '../config/env.php';
header("Content-Type: application/json; charset=UTF-8");

// Import des dépendances
include_once '../config/database.php';
include_once '../config/auth.php';
include_once '../models/PlanningOptimizer.php';
include_once '../utils/Validator.php';
include_once '../utils/ErrorHandler.php';

// Initialisation des services
$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    ErrorHandler::handleServerError("Erreur de connexion à la base de données");
}

$auth = new Auth($db);
$optimizer = new PlanningOptimizer($db);

// Vérification de l'authentification
if (!$auth->isAuthenticated()) {
    ErrorHandler::handleAuthError();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method . '_' . $action) {
        case 'POST_optimiser':
            optimiserPlanning();
            break;

        case 'GET_plannings':
            listerPlannings();
            break;

        case 'GET_planning':
            obtenirPlanning();
            break;

        case 'POST_appliquer':
            appliquerPlanning();
            break;

        case 'GET_parametres':
            obtenirParametres();
            break;

        case 'PUT_parametres':
            mettreAJourParametres();
            break;

        case 'GET_zones':
            obtenirZones();
            break;

        case 'POST_calculer_trajet':
            calculerTempsTrajet();
            break;

        case 'GET_statistiques':
            obtenirStatistiques();
            break;

        default:
            ErrorHandler::handleError(400, "Action non supportée: $action");
    }

} catch (Exception $e) {
    ErrorHandler::handleServerError("Erreur interne: " . $e->getMessage());
}

/**
 * Optimise le planning pour une période donnée
 */
function optimiserPlanning() {
    global $optimizer, $auth;

    // Vérifier les permissions
    if (!$auth->hasPermission('manager')) {
        ErrorHandler::handleAuthError("Permission insuffisante");
    }

    $input = json_decode(file_get_contents("php://input"), true);

    // Validation des données
    $errors = [];

    if (!Validator::validateRequired($input['date_debut'] ?? '')) {
        $errors[] = 'Date de début obligatoire';
    }

    if (!Validator::validateRequired($input['date_fin'] ?? '')) {
        $errors[] = 'Date de fin obligatoire';
    }

    // Validation du format des dates
    if (isset($input['date_debut']) && !strtotime($input['date_debut'])) {
        $errors[] = 'Format de date de début invalide';
    }

    if (isset($input['date_fin']) && !strtotime($input['date_fin'])) {
        $errors[] = 'Format de date de fin invalide';
    }

    // Vérifier que la date de fin est après la date de début
    if (isset($input['date_debut']) && isset($input['date_fin'])) {
        if (strtotime($input['date_fin']) <= strtotime($input['date_debut'])) {
            $errors[] = 'La date de fin doit être postérieure à la date de début';
        }
    }

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    // Options d'optimisation
    $options = [
        'population_size' => $input['population_size'] ?? 50,
        'generations' => $input['generations'] ?? 100,
        'mutation_rate' => $input['mutation_rate'] ?? 0.1,
        'algorithme' => $input['algorithme'] ?? 'genetic_algorithm',
        'parametres_id' => $input['parametres_id'] ?? null
    ];

    // Appliquer des paramètres personnalisés si spécifiés
    if ($options['parametres_id']) {
        $optimizer->appliquerParametres($options['parametres_id']);
    }

    $resultat = $optimizer->optimiserPlanning(
        $input['date_debut'],
        $input['date_fin'],
        $options
    );

    if ($resultat['success']) {
        echo json_encode([
            'success' => true,
            'data' => $resultat,
            'message' => 'Planning optimisé avec succès'
        ]);
    } else {
        ErrorHandler::handleError(500, $resultat['error']);
    }
}

/**
 * Liste tous les plannings générés
 */
function listerPlannings() {
    global $db;

    $page = $_GET['page'] ?? 1;
    $limit = $_GET['limit'] ?? 20;
    $offset = ($page - 1) * $limit;

    $query = "
        SELECT p.*, u.nom as createur_nom,
               COUNT(pc.id) as nombre_interventions,
               AVG(pc.efficacite_score) as efficacite_moyenne
        FROM planning_optimise p
        LEFT JOIN users u ON p.created_by = u.id
        LEFT JOIN planning_creneaux pc ON p.id = pc.planning_id
        GROUP BY p.id
        ORDER BY p.created_at DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $plannings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Compter le total
    $countQuery = "SELECT COUNT(*) as total FROM planning_optimise";
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute();
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    echo json_encode([
        'success' => true,
        'data' => $plannings,
        'pagination' => [
            'page' => (int)$page,
            'limit' => (int)$limit,
            'total' => (int)$total,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

/**
 * Obtient les détails d'un planning spécifique
 */
function obtenirPlanning() {
    global $db;

    $id = $_GET['id'] ?? null;
    if (!$id) {
        ErrorHandler::handleError(400, "ID du planning requis");
    }

    // Récupérer le planning principal
    $queryPlanning = "
        SELECT p.*, u.nom as createur_nom, u.prenom as createur_prenom
        FROM planning_optimise p
        LEFT JOIN users u ON p.created_by = u.id
        WHERE p.id = :id
    ";

    $stmt = $db->prepare($queryPlanning);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    $planning = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$planning) {
        ErrorHandler::handleNotFoundError("Planning non trouvé");
    }

    // Récupérer les créneaux
    $queryCreneaux = "
        SELECT pc.*, i.numero as intervention_numero, i.titre as intervention_titre,
               i.description as intervention_description, i.priorite,
               c.nom as client_nom, c.adresse as client_adresse,
               u.nom as technicien_nom, u.prenom as technicien_prenom,
               l.latitude, l.longitude, l.zone_intervention
        FROM planning_creneaux pc
        INNER JOIN interventions i ON pc.intervention_id = i.id
        INNER JOIN users u ON pc.technicien_id = u.id
        LEFT JOIN clients c ON i.client_id = c.id
        LEFT JOIN localisation l ON i.localisation_id = l.id
        WHERE pc.planning_id = :id
        ORDER BY pc.technicien_id, pc.ordre_sequence
    ";

    $stmt = $db->prepare($queryCreneaux);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    $creneaux = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Regrouper par technicien
    $planningParTechnicien = [];
    foreach ($creneaux as $creneau) {
        $techId = $creneau['technicien_id'];
        if (!isset($planningParTechnicien[$techId])) {
            $planningParTechnicien[$techId] = [
                'technicien' => [
                    'id' => $techId,
                    'nom' => $creneau['technicien_nom'],
                    'prenom' => $creneau['technicien_prenom']
                ],
                'creneaux' => []
            ];
        }
        $planningParTechnicien[$techId]['creneaux'][] = $creneau;
    }

    $planning['planning_par_technicien'] = array_values($planningParTechnicien);
    $planning['creneaux'] = $creneaux;

    echo json_encode([
        'success' => true,
        'data' => $planning
    ]);
}

/**
 * Applique un planning optimisé (met à jour les interventions)
 */
function appliquerPlanning() {
    global $db, $auth;

    // Vérifier les permissions
    if (!$auth->hasPermission('manager')) {
        ErrorHandler::handleAuthError("Permission insuffisante");
    }

    $input = json_decode(file_get_contents("php://input"), true);
    $planningId = $input['planning_id'] ?? null;

    if (!$planningId) {
        ErrorHandler::handleError(400, "ID du planning requis");
    }

    try {
        $db->beginTransaction();

        // Récupérer les créneaux du planning
        $query = "
            SELECT pc.*, i.id as intervention_id
            FROM planning_creneaux pc
            INNER JOIN interventions i ON pc.intervention_id = i.id
            WHERE pc.planning_id = :planning_id
        ";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':planning_id', $planningId);
        $stmt->execute();

        $creneaux = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Mettre à jour chaque intervention
        $updateQuery = "
            UPDATE interventions SET
                technicien_id = :technicien_id,
                date_intervention = :date_debut,
                statut = 'En attente'
            WHERE id = :intervention_id
        ";

        $updateStmt = $db->prepare($updateQuery);

        foreach ($creneaux as $creneau) {
            $updateStmt->bindParam(':technicien_id', $creneau['technicien_id']);
            $updateStmt->bindParam(':date_debut', $creneau['date_debut']);
            $updateStmt->bindParam(':intervention_id', $creneau['intervention_id']);
            $updateStmt->execute();
        }

        // Marquer le planning comme appliqué
        $updatePlanningQuery = "UPDATE planning_optimise SET statut = 'applique' WHERE id = :id";
        $updatePlanningStmt = $db->prepare($updatePlanningQuery);
        $updatePlanningStmt->bindParam(':id', $planningId);
        $updatePlanningStmt->execute();

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Planning appliqué avec succès',
            'interventions_mises_a_jour' => count($creneaux)
        ]);

    } catch (Exception $e) {
        $db->rollback();
        ErrorHandler::handleServerError("Erreur lors de l'application du planning: " . $e->getMessage());
    }
}

/**
 * Obtient les paramètres d'optimisation
 */
function obtenirParametres() {
    global $db;

    $query = "SELECT * FROM parametres_optimisation ORDER BY is_default DESC, nom";
    $stmt = $db->prepare($query);
    $stmt->execute();

    $parametres = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $parametres
    ]);
}

/**
 * Met à jour les paramètres d'optimisation
 */
function mettreAJourParametres() {
    global $db, $auth;

    // Vérifier les permissions
    if (!$auth->hasPermission('admin')) {
        ErrorHandler::handleAuthError("Permission insuffisante");
    }

    $input = json_decode(file_get_contents("php://input"), true);

    // Validation
    $validator = new Validator();
    $validator->required('id', $input['id'] ?? '');
    $validator->numeric('poids_distance', $input['poids_distance'] ?? '');
    $validator->numeric('poids_temps', $input['poids_temps'] ?? '');
    $validator->numeric('poids_priorite', $input['poids_priorite'] ?? '');
    $validator->numeric('poids_cout', $input['poids_cout'] ?? '');

    if (!$validator->isValid()) {
        ErrorHandler::handleValidationError($validator->getErrors());
    }

    // Vérifier que la somme des poids = 1
    $somme = $input['poids_distance'] + $input['poids_temps'] + $input['poids_priorite'] + $input['poids_cout'];
    if (abs($somme - 1.0) > 0.01) {
        ErrorHandler::handleError(400, "La somme des poids doit être égale à 1.0");
    }

    $query = "
        UPDATE parametres_optimisation SET
            poids_distance = :poids_distance,
            poids_temps = :poids_temps,
            poids_priorite = :poids_priorite,
            poids_cout = :poids_cout,
            duree_max_journee = :duree_max_journee,
            temps_pause_min = :temps_pause_min,
            distance_max_trajet = :distance_max_trajet,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = :id
    ";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $input['id']);
    $stmt->bindParam(':poids_distance', $input['poids_distance']);
    $stmt->bindParam(':poids_temps', $input['poids_temps']);
    $stmt->bindParam(':poids_priorite', $input['poids_priorite']);
    $stmt->bindParam(':poids_cout', $input['poids_cout']);
    $stmt->bindParam(':duree_max_journee', $input['duree_max_journee'] ?? 480);
    $stmt->bindParam(':temps_pause_min', $input['temps_pause_min'] ?? 30);
    $stmt->bindParam(':distance_max_trajet', $input['distance_max_trajet'] ?? 100);
    $stmt->execute();

    echo json_encode([
        'success' => true,
        'message' => 'Paramètres mis à jour avec succès'
    ]);
}

/**
 * Obtient les zones d'intervention disponibles
 */
function obtenirZones() {
    global $db;

    $query = "
        SELECT DISTINCT zone_intervention, COUNT(*) as nombre_techniciens
        FROM technicien_zones
        WHERE is_active = 1
        GROUP BY zone_intervention
        ORDER BY zone_intervention
    ";

    $stmt = $db->prepare($query);
    $stmt->execute();

    $zones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $zones
    ]);
}

/**
 * Calcule le temps de trajet entre deux localisations
 */
function calculerTempsTrajet() {
    global $optimizer;

    $input = json_decode(file_get_contents("php://input"), true);

    $validator = new Validator();
    $validator->required('lat1', $input['lat1'] ?? '');
    $validator->required('lon1', $input['lon1'] ?? '');
    $validator->required('lat2', $input['lat2'] ?? '');
    $validator->required('lon2', $input['lon2'] ?? '');

    if (!$validator->isValid()) {
        ErrorHandler::handleValidationError($validator->getErrors());
    }

    // Utiliser une méthode publique ou créer une classe utilitaire
    $tempsTrajet = calculerTempsTrajetSimple(
        $input['lat1'], $input['lon1'],
        $input['lat2'], $input['lon2']
    );

    echo json_encode([
        'success' => true,
        'data' => $tempsTrajet
    ]);
}

/**
 * Fonction utilitaire pour calculer le temps de trajet
 */
function calculerTempsTrajetSimple($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371; // Rayon de la Terre en km

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * asin(sqrt($a));
    $distance = $earthRadius * $c;

    // Estimation du temps (vitesse moyenne)
    $vitesseMoyenne = $distance > 20 ? 80 : 40;
    $tempsTrajetMinutes = ($distance / $vitesseMoyenne) * 60;

    return [
        'distance_km' => round($distance, 2),
        'temps_minutes' => round($tempsTrajetMinutes),
        'cout_estime' => round($distance * 0.50, 2)
    ];
}

/**
 * Obtient les statistiques d'optimisation
 */
function obtenirStatistiques() {
    global $db;

    $query = "
        SELECT
            COUNT(*) as total_plannings,
            AVG(score_optimisation) as score_moyen,
            AVG(temps_calcul_ms) as temps_calcul_moyen,
            COUNT(CASE WHEN statut = 'applique' THEN 1 END) as plannings_appliques
        FROM planning_optimise
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ";

    $stmt = $db->prepare($query);
    $stmt->execute();

    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
}
?>