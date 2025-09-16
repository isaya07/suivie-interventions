<?php
/**
 * API d'authentification
 * Gère les opérations de connexion, déconnexion et vérification d'état
 *
 * Endpoints disponibles :
 * - POST /auth.php?action=login : Connexion utilisateur
 * - POST /auth.php?action=logout : Déconnexion utilisateur
 * - GET /auth.php?action=me : Récupération utilisateur connecté
 * - GET /auth.php?action=test : Test de fonctionnement API
 */

// Configuration CORS et en-têtes de réponse
include_once 'cors.php';
require_once '../config/env.php';
header("Content-Type: application/json; charset=UTF-8");

// Import des dépendances
include_once '../config/database.php';
include_once '../config/auth.php';

// Initialisation de la connexion base de données
$database = new Database();
$db = $database->getConnection();

// Vérification de la connexion DB
if ($db === null) {
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "Erreur de connexion à la base de données"));
    exit;
}

// Instance du service d'authentification
$auth = new Auth($db);

// Extraction des paramètres de requête
$method = $_SERVER['REQUEST_METHOD'];
$endpoint = $_GET['action'] ?? '';

// Routage des endpoints basé sur METHOD_ACTION
switch($method . '_' . $endpoint) {
    /**
     * ENDPOINT: POST /auth.php?action=login
     * Authentifie un utilisateur avec email/nom et mot de passe
     * Body JSON: { "username": "...", "password": "..." }
     * Retour: { "success": boolean, "message": string, "user": object }
     */
    case 'POST_login':
        $data = json_decode(file_get_contents("php://input"));

        // Validation des données requises
        if(!empty($data->username) && !empty($data->password)) {
            $result = $auth->login($data->username, $data->password);

            // Réponse selon le résultat de l'authentification
            if($result['success']) {
                http_response_code(200);  // Succès
                echo json_encode($result);
            } else {
                http_response_code(401);  // Non autorisé
                echo json_encode($result);
            }
        } else {
            // Données manquantes
            http_response_code(400);
            echo json_encode(array("success" => false, "message" => "Données incomplètes"));
        }
        break;

    /**
     * ENDPOINT: POST /auth.php?action=logout
     * Déconnecte l'utilisateur actuel et détruit la session
     * Aucun body requis
     * Retour: { "success": boolean, "message": string }
     */
    case 'POST_logout':
        $result = $auth->logout();
        http_response_code(200);
        echo json_encode($result);
        break;

    /**
     * ENDPOINT: GET /auth.php?action=me
     * Récupère les informations de l'utilisateur connecté
     * Headers: nécessite session authentifiée
     * Retour: { "success": boolean, "user": object } ou erreur 401
     */
    case 'GET_me':
        if($auth->isAuthenticated()) {
            $user = $auth->getCurrentUser();
            http_response_code(200);
            echo json_encode(array("success" => true, "user" => $user));
        } else {
            http_response_code(401);
            echo json_encode(array("success" => false, "message" => "Non authentifié"));
        }
        break;

    /**
     * ENDPOINT: GET /auth.php?action=test
     * Endpoint de test pour vérifier le bon fonctionnement de l'API
     * Retour: informations de debug sur la requête
     */
    case 'GET_test':
        echo json_encode([
            "message" => "API Auth fonctionne",
            "method" => $method,
            "endpoint" => $endpoint,
            "switch_case" => $method . '_' . $endpoint
        ]);
        break;

    /**
     * Gestion des endpoints non trouvés
     * Retourne une erreur 404 avec informations de debug
     */
    default:
        http_response_code(404);
        echo json_encode(array(
            "message" => "Endpoint non trouvé",
            "method" => $method,
            "endpoint" => $endpoint,
            "expected" => $method . '_' . $endpoint
        ));
        break;
}
?>