<?php
/**
 * API de gestion des localisations
 *
 * Endpoints:
 * - GET /localisations.php : Liste des localisations
 * - GET /localisations.php?id=X : Détails d'une localisation
 * - POST /localisations.php : Créer une localisation
 * - PUT /localisations.php : Mettre à jour une localisation
 * - DELETE /localisations.php?id=X : Supprimer une localisation
 * - POST /localisations.php?action=geocode : Géocoder une adresse
 * - GET /localisations.php?action=zones : Récupérer les zones disponibles
 */

// Configuration CORS et en-têtes
include_once 'cors.php';
require_once '../config/env.php';
header("Content-Type: application/json; charset=UTF-8");

// Import des dépendances
include_once '../config/database.php';
include_once '../config/auth.php';
include_once '../utils/Validator.php';
include_once '../utils/ErrorHandler.php';

// Initialisation des services
$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    ErrorHandler::handleServerError("Erreur de connexion à la base de données");
}

$auth = new Auth($db);

// Vérification de l'authentification
if (!$auth->isAuthenticated()) {
    ErrorHandler::handleAuthError();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method . '_' . $action) {
        case 'GET_':
            if (isset($_GET['id'])) {
                obtenirLocalisation();
            } else {
                listerLocalisations();
            }
            break;

        case 'POST_':
            creerLocalisation();
            break;

        case 'PUT_':
            mettreAJourLocalisation();
            break;

        case 'DELETE_':
            supprimerLocalisation();
            break;

        case 'POST_geocode':
            geocoderAdresse();
            break;

        case 'GET_zones':
            obtenirZones();
            break;

        case 'POST_bulk_geocode':
            geocoderEnMasse();
            break;

        default:
            ErrorHandler::handleError(400, "Action non supportée");
    }

} catch (Exception $e) {
    ErrorHandler::handleServerError("Erreur interne: " . $e->getMessage());
}

/**
 * Liste toutes les localisations
 */
function listerLocalisations() {
    global $db;

    $page = $_GET['page'] ?? 1;
    $limit = $_GET['limit'] ?? 50;
    $offset = ($page - 1) * $limit;
    $search = $_GET['search'] ?? '';
    $zone = $_GET['zone'] ?? '';

    $whereConditions = [];
    $params = [];

    if ($search) {
        $whereConditions[] = "(l.adresse_complete LIKE :search OR l.ville LIKE :search)";
        $params[':search'] = "%$search%";
    }

    if ($zone) {
        $whereConditions[] = "l.zone_intervention = :zone";
        $params[':zone'] = $zone;
    }

    $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    $query = "
        SELECT l.*, c.nom as client_nom,
               COUNT(i.id) as nombre_interventions
        FROM localisation l
        LEFT JOIN clients c ON l.client_id = c.id
        LEFT JOIN interventions i ON l.id = i.localisation_id
        $whereClause
        GROUP BY l.id
        ORDER BY l.created_at DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $localisations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Compter le total
    $countQuery = "SELECT COUNT(DISTINCT l.id) as total FROM localisation l LEFT JOIN clients c ON l.client_id = c.id $whereClause";
    $countStmt = $db->prepare($countQuery);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    echo json_encode([
        'success' => true,
        'data' => $localisations,
        'pagination' => [
            'page' => (int)$page,
            'limit' => (int)$limit,
            'total' => (int)$total,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

/**
 * Obtient une localisation spécifique
 */
function obtenirLocalisation() {
    global $db;

    $id = $_GET['id'];

    $query = "
        SELECT l.*, c.nom as client_nom, c.email as client_email,
               COUNT(i.id) as nombre_interventions
        FROM localisation l
        LEFT JOIN clients c ON l.client_id = c.id
        LEFT JOIN interventions i ON l.id = i.localisation_id
        WHERE l.id = :id
        GROUP BY l.id
    ";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    $localisation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$localisation) {
        ErrorHandler::handleNotFoundError("Localisation non trouvée");
    }

    echo json_encode([
        'success' => true,
        'data' => $localisation
    ]);
}

/**
 * Crée une nouvelle localisation
 */
function creerLocalisation() {
    global $db, $auth;

    $input = json_decode(file_get_contents("php://input"), true);

    // Validation
    $validator = new Validator();
    $validator->required('adresse_complete', $input['adresse_complete'] ?? '');
    $validator->numeric('latitude', $input['latitude'] ?? '');
    $validator->numeric('longitude', $input['longitude'] ?? '');

    if (!$validator->isValid()) {
        ErrorHandler::handleValidationError($validator->getErrors());
    }

    try {
        // Déterminer automatiquement la zone si non fournie
        if (empty($input['zone_intervention'])) {
            $input['zone_intervention'] = determinerZoneAutomatique(
                $input['latitude'],
                $input['longitude']
            );
        }

        $query = "
            INSERT INTO localisation (
                client_id, adresse_complete, latitude, longitude,
                ville, code_postal, region, pays, zone_intervention
            ) VALUES (
                :client_id, :adresse_complete, :latitude, :longitude,
                :ville, :code_postal, :region, :pays, :zone_intervention
            )
        ";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':client_id', $input['client_id']);
        $stmt->bindParam(':adresse_complete', $input['adresse_complete']);
        $stmt->bindParam(':latitude', $input['latitude']);
        $stmt->bindParam(':longitude', $input['longitude']);
        $stmt->bindParam(':ville', $input['ville'] ?? '');
        $stmt->bindParam(':code_postal', $input['code_postal'] ?? '');
        $stmt->bindParam(':region', $input['region'] ?? '');
        $stmt->bindParam(':pays', $input['pays'] ?? 'France');
        $stmt->bindParam(':zone_intervention', $input['zone_intervention']);
        $stmt->execute();

        $localisationId = $db->lastInsertId();

        // Récupérer la localisation créée
        $getQuery = "SELECT * FROM localisation WHERE id = :id";
        $getStmt = $db->prepare($getQuery);
        $getStmt->bindParam(':id', $localisationId);
        $getStmt->execute();

        $localisation = $getStmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => $localisation,
            'message' => 'Localisation créée avec succès'
        ]);

    } catch (Exception $e) {
        ErrorHandler::handleServerError("Erreur lors de la création: " . $e->getMessage());
    }
}

/**
 * Met à jour une localisation
 */
function mettreAJourLocalisation() {
    global $db, $auth;

    $input = json_decode(file_get_contents("php://input"), true);

    // Validation
    $validator = new Validator();
    $validator->required('id', $input['id'] ?? '');
    $validator->required('adresse_complete', $input['adresse_complete'] ?? '');

    if (!$validator->isValid()) {
        ErrorHandler::handleValidationError($validator->getErrors());
    }

    try {
        // Recalculer la zone si les coordonnées ont changé
        if (isset($input['latitude']) && isset($input['longitude'])) {
            $input['zone_intervention'] = determinerZoneAutomatique(
                $input['latitude'],
                $input['longitude']
            );
        }

        $query = "
            UPDATE localisation SET
                adresse_complete = :adresse_complete,
                latitude = :latitude,
                longitude = :longitude,
                ville = :ville,
                code_postal = :code_postal,
                region = :region,
                zone_intervention = :zone_intervention,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $input['id']);
        $stmt->bindParam(':adresse_complete', $input['adresse_complete']);
        $stmt->bindParam(':latitude', $input['latitude'] ?? null);
        $stmt->bindParam(':longitude', $input['longitude'] ?? null);
        $stmt->bindParam(':ville', $input['ville'] ?? '');
        $stmt->bindParam(':code_postal', $input['code_postal'] ?? '');
        $stmt->bindParam(':region', $input['region'] ?? '');
        $stmt->bindParam(':zone_intervention', $input['zone_intervention'] ?? '');
        $stmt->execute();

        echo json_encode([
            'success' => true,
            'message' => 'Localisation mise à jour avec succès'
        ]);

    } catch (Exception $e) {
        ErrorHandler::handleServerError("Erreur lors de la mise à jour: " . $e->getMessage());
    }
}

/**
 * Supprime une localisation
 */
function supprimerLocalisation() {
    global $db, $auth;

    $id = $_GET['id'] ?? null;

    if (!$id) {
        ErrorHandler::handleError(400, "ID requis");
    }

    // Vérifier que la localisation n'est pas utilisée
    $checkQuery = "SELECT COUNT(*) as count FROM interventions WHERE localisation_id = :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $id);
    $checkStmt->execute();

    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
    if ($result['count'] > 0) {
        ErrorHandler::handleError(409, "Impossible de supprimer: localisation utilisée par des interventions");
    }

    try {
        $query = "DELETE FROM localisation WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        echo json_encode([
            'success' => true,
            'message' => 'Localisation supprimée avec succès'
        ]);

    } catch (Exception $e) {
        ErrorHandler::handleServerError("Erreur lors de la suppression: " . $e->getMessage());
    }
}

/**
 * Géocode une adresse
 */
function geocoderAdresse() {
    global $db;

    $input = json_decode(file_get_contents("php://input"), true);

    if (empty($input['adresse'])) {
        ErrorHandler::handleError(400, "Adresse requise");
    }

    try {
        $coordonnees = geocoderAvecNominatim($input['adresse']);

        if ($coordonnees) {
            $zone = determinerZoneAutomatique($coordonnees['latitude'], $coordonnees['longitude']);

            echo json_encode([
                'success' => true,
                'data' => [
                    'latitude' => $coordonnees['latitude'],
                    'longitude' => $coordonnees['longitude'],
                    'adresse_formatee' => $coordonnees['adresse_formatee'],
                    'zone_intervention' => $zone
                ]
            ]);
        } else {
            ErrorHandler::handleError(404, "Impossible de géocoder cette adresse");
        }

    } catch (Exception $e) {
        ErrorHandler::handleServerError("Erreur de géocodage: " . $e->getMessage());
    }
}

/**
 * Obtient les zones d'intervention disponibles
 */
function obtenirZones() {
    global $db;

    $query = "
        SELECT DISTINCT zone_intervention, COUNT(*) as nombre_localisations
        FROM localisation
        WHERE zone_intervention IS NOT NULL
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
 * Géocode en masse les adresses existantes
 */
function geocoderEnMasse() {
    global $db, $auth;

    // Vérifier les permissions admin
    if (!$auth->hasPermission('admin')) {
        ErrorHandler::handleAuthError("Permission insuffisante");
    }

    $limit = $_POST['limit'] ?? 50;

    try {
        // Récupérer les localisations sans coordonnées
        $query = "
            SELECT id, adresse_complete
            FROM localisation
            WHERE (latitude IS NULL OR longitude IS NULL)
            AND adresse_complete IS NOT NULL
            LIMIT :limit
        ";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $localisations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $traites = 0;
        $erreurs = 0;

        foreach ($localisations as $localisation) {
            try {
                $coordonnees = geocoderAvecNominatim($localisation['adresse_complete']);

                if ($coordonnees) {
                    $zone = determinerZoneAutomatique(
                        $coordonnees['latitude'],
                        $coordonnees['longitude']
                    );

                    $updateQuery = "
                        UPDATE localisation SET
                            latitude = :latitude,
                            longitude = :longitude,
                            zone_intervention = :zone,
                            updated_at = CURRENT_TIMESTAMP
                        WHERE id = :id
                    ";

                    $updateStmt = $db->prepare($updateQuery);
                    $updateStmt->bindParam(':latitude', $coordonnees['latitude']);
                    $updateStmt->bindParam(':longitude', $coordonnees['longitude']);
                    $updateStmt->bindParam(':zone', $zone);
                    $updateStmt->bindParam(':id', $localisation['id']);
                    $updateStmt->execute();

                    $traites++;
                } else {
                    $erreurs++;
                }

                // Pause pour éviter de surcharger l'API
                usleep(200000); // 200ms

            } catch (Exception $e) {
                $erreurs++;
                error_log("Erreur géocodage pour ID {$localisation['id']}: " . $e->getMessage());
            }
        }

        echo json_encode([
            'success' => true,
            'message' => "Géocodage terminé: $traites traités, $erreurs erreurs",
            'statistiques' => [
                'traites' => $traites,
                'erreurs' => $erreurs,
                'total' => count($localisations)
            ]
        ]);

    } catch (Exception $e) {
        ErrorHandler::handleServerError("Erreur géocodage en masse: " . $e->getMessage());
    }
}

/**
 * Fonctions utilitaires
 */

/**
 * Géocode avec l'API Nominatim
 */
function geocoderAvecNominatim($adresse) {
    $url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($adresse) . "&countrycodes=fr&limit=1";

    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'SuiviInterventions/1.0'
        ]
    ]);

    $response = file_get_contents($url, false, $context);

    if ($response === false) {
        throw new Exception("Erreur lors de l'appel à l'API de géocodage");
    }

    $data = json_decode($response, true);

    if (!empty($data) && isset($data[0]['lat']) && isset($data[0]['lon'])) {
        return [
            'latitude' => floatval($data[0]['lat']),
            'longitude' => floatval($data[0]['lon']),
            'adresse_formatee' => $data[0]['display_name']
        ];
    }

    return null;
}

/**
 * Détermine automatiquement la zone d'intervention
 */
function determinerZoneAutomatique($latitude, $longitude) {
    // Logique de détermination des zones basée sur les coordonnées
    // À adapter selon vos zones géographiques

    // Région parisienne
    if ($latitude >= 48.5 && $latitude <= 49.0 && $longitude >= 2.0 && $longitude <= 2.7) {
        return 'Paris-IDF';
    }

    // Lyon et région
    if ($latitude >= 45.5 && $latitude <= 46.0 && $longitude >= 4.5 && $longitude <= 5.2) {
        return 'Lyon-Rhone';
    }

    // Marseille et région
    if ($latitude >= 43.0 && $latitude <= 43.5 && $longitude >= 5.0 && $longitude <= 5.8) {
        return 'Marseille-PACA';
    }

    // Nord de la France
    if ($latitude >= 50.0) {
        return 'Nord';
    }

    // Sud de la France
    if ($latitude <= 44.0) {
        return 'Sud';
    }

    // Ouest de la France
    if ($longitude <= 0) {
        return 'Ouest';
    }

    // Est de la France
    if ($longitude >= 6) {
        return 'Est';
    }

    return 'Centre';
}
?>