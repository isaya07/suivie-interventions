<?php
/**
 * API de gestion des interventions techniques
 *
 * Endpoints disponibles :
 * - GET /intervention.php : Liste de toutes les interventions
 * - GET /intervention.php?id=X : Détails d'une intervention spécifique
 * - POST /intervention.php : Création d'une nouvelle intervention
 * - PUT /intervention.php : Mise à jour d'une intervention existante
 * - DELETE /intervention.php : Suppression d'une intervention
 *
 * Tous les endpoints nécessitent une authentification valide
 */

// Configuration CORS et en-têtes
include_once 'cors.php';
require_once '../config/env.php';
header("Content-Type: application/json; charset=UTF-8");

// Import des dépendances
include_once '../config/database.php';
include_once '../config/auth.php';
include_once '../models/Intervention.php';
include_once '../utils/Validator.php';
include_once '../utils/ErrorHandler.php';

// Initialisation des services
$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$intervention = new Intervention($db);

// Vérification de l'authentification pour tous les endpoints
if (!$auth->isAuthenticated()) {
    ErrorHandler::handleAuthError();
}

$method = $_SERVER['REQUEST_METHOD'];

// Routage REST basé sur la méthode HTTP
switch($method) {
    /**
     * METHOD: GET
     * Récupération d'interventions (liste complète ou intervention spécifique)
     */
    case 'GET':
        if(isset($_GET['id'])) {
            /**
             * GET /intervention.php?id=X
             * Récupère les détails d'une intervention spécifique
             * Paramètres : id (integer) - ID de l'intervention
             * Retour : Objet intervention avec tous ses détails
             */
            $intervention->id = $_GET['id'];
            if($intervention->readOne()) {
                // Construction de l'objet de réponse avec les données principales
                $intervention_arr = array(
                    "id" => $intervention->id,
                    "titre" => $intervention->titre,
                    "description" => $intervention->description,
                    "client_nom" => $intervention->client_nom,
                    "technicien_nom" => $intervention->technicien_nom,
                    "statut" => $intervention->statut,
                    "priorite" => $intervention->priorite,
                    "date_creation" => $intervention->date_creation,
                    "date_intervention" => $intervention->date_intervention,
                    "date_fin" => $intervention->date_fin
                );
                http_response_code(200);
                echo json_encode($intervention_arr);
            } else {
                // Intervention non trouvée
                http_response_code(404);
                echo json_encode(array("message" => "Intervention non trouvée."));
            }
        } else {
            /**
             * GET /intervention.php
             * Récupère la liste complète des interventions
             * Retour : Array d'objets interventions dans un wrapper "records"
             */
            $stmt = $intervention->read();
            $num = $stmt->rowCount();

            if($num > 0) {
                // Initialisation du tableau de réponse
                $interventions_arr = array();
                $interventions_arr["records"] = array();

                // Construction de la liste des interventions
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    extract($row);
                    $intervention_item = array(
                        "id" => $id,
                        "titre" => $titre,
                        "description" => $description,
                        "client_nom" => $client_nom ?? '',
                        "technicien_nom" => $technicien_nom ?? '',
                        "statut" => $statut,
                        "priorite" => $priorite,
                        "date_creation" => $date_creation,
                        "date_intervention" => $date_intervention,
                        "date_fin" => $date_fin
                    );
                    array_push($interventions_arr["records"], $intervention_item);
                }

                http_response_code(200);
                echo json_encode($interventions_arr);
            } else {
                // Aucune intervention trouvée - retourner tableau vide
                http_response_code(200);
                echo json_encode(array("records" => array()));
            }
        }
        break;

    /**
     * METHOD: POST
     * Création d'une nouvelle intervention
     * Body JSON requis avec les champs obligatoires : titre, description
     * Champs optionnels : client_id, technicien_id, statut, priorité, etc.
     */
    case 'POST':
        $data = json_decode(file_get_contents("php://input"));

        // Validation du format JSON
        if (!$data) {
            ErrorHandler::handleError(400, "Format JSON invalide");
        }

        // Validation des données métier avec le validateur
        $validationErrors = Validator::validateInterventionData($data);
        if (!empty($validationErrors)) {
            ErrorHandler::handleValidationErrors($validationErrors);
        }

        try {
            // Récupération de l'utilisateur connecté pour traçabilité
            $current_user = $auth->getCurrentUser();

            // Assignation des données avec sanitisation
            $intervention->titre = Validator::sanitizeString($data->titre);
            $intervention->description = Validator::sanitizeString($data->description ?? '');
            $intervention->client_id = $data->client_id ?? null;
            $intervention->client_nom = Validator::sanitizeString($data->client_nom ?? '');
            $intervention->technicien_id = $data->technicien_id ?? null;
            $intervention->technicien_nom = Validator::sanitizeString($data->technicien_nom ?? '');
            $intervention->createur_id = $current_user['id']; // Traçabilité de la création

            // Valeurs par défaut si non spécifiées
            $intervention->statut = $data->statut ?? 'En attente';
            $intervention->priorite = $data->priorite ?? 'Normale';
            $intervention->type_intervention = $data->type_intervention ?? 'Réparation';
            $intervention->temps_estime = $data->temps_estime ?? null;
            $intervention->cout_prevu = $data->cout_prevu ?? null;

            // Timestamps automatiques
            $intervention->date_creation = date('Y-m-d H:i:s');
            $intervention->date_intervention = $data->date_intervention ?? null;
            $intervention->date_limite = $data->date_limite ?? null;

            // Tentative de création en base
            if($intervention->create()) {
                ErrorHandler::handleSuccess("Intervention créée avec succès", ['id' => $intervention->id], 201);
            } else {
                ErrorHandler::handleServerError("Impossible de créer l'intervention");
            }
        } catch (Exception $e) {
            // Logging de l'erreur pour debug
            ErrorHandler::logError($e->getMessage(), ['data' => $data]);
            ErrorHandler::handleServerError("Erreur lors de la création de l'intervention");
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"));

        if (!$data) {
            ErrorHandler::handleError(400, "Format JSON invalide");
        }

        if (!isset($data->id) || empty($data->id)) {
            ErrorHandler::handleError(400, "ID de l'intervention requis");
        }

        // Validate input data for update
        $validationErrors = Validator::validateInterventionData($data);
        if (!empty($validationErrors)) {
            ErrorHandler::handleValidationErrors($validationErrors);
        }

        try {
            $current_user = $auth->getCurrentUser();

            // Check if user has permission to update this intervention
            $intervention->id = $data->id;
            if (!$intervention->readOne()) {
                ErrorHandler::handleNotFoundError("Intervention non trouvée");
            }

            // Update fields with proper sanitization
            $intervention->titre = Validator::sanitizeString($data->titre);
            $intervention->description = Validator::sanitizeString($data->description ?? '');
            $intervention->client_id = $data->client_id ?? $intervention->client_id;
            $intervention->client_nom = Validator::sanitizeString($data->client_nom ?? $intervention->client_nom);
            $intervention->technicien_id = $data->technicien_id ?? $intervention->technicien_id;
            $intervention->technicien_nom = Validator::sanitizeString($data->technicien_nom ?? $intervention->technicien_nom);
            $intervention->statut = $data->statut ?? $intervention->statut;
            $intervention->priorite = $data->priorite ?? $intervention->priorite;
            $intervention->type_intervention = $data->type_intervention ?? $intervention->type_intervention;
            $intervention->temps_estime = $data->temps_estime ?? $intervention->temps_estime;
            $intervention->temps_reel = $data->temps_reel ?? $intervention->temps_reel;
            $intervention->cout_prevu = $data->cout_prevu ?? $intervention->cout_prevu;
            $intervention->cout_reel = $data->cout_reel ?? $intervention->cout_reel;
            $intervention->date_intervention = $data->date_intervention ?? $intervention->date_intervention;
            $intervention->date_fin = $data->date_fin ?? $intervention->date_fin;
            $intervention->date_limite = $data->date_limite ?? $intervention->date_limite;
            $intervention->notes_technicien = Validator::sanitizeString($data->notes_technicien ?? $intervention->notes_technicien);
            $intervention->satisfaction_client = $data->satisfaction_client ?? $intervention->satisfaction_client;

            if($intervention->update()) {
                ErrorHandler::handleSuccess("Intervention mise à jour avec succès");
            } else {
                ErrorHandler::handleServerError("Impossible de mettre à jour l'intervention");
            }
        } catch (Exception $e) {
            ErrorHandler::logError($e->getMessage(), ['data' => $data]);
            ErrorHandler::handleServerError("Erreur lors de la mise à jour de l'intervention");
        }
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents("php://input"));
        $intervention->id = $data->id;

        if($intervention->delete()) {
            http_response_code(200);
            echo json_encode(array("message" => "Intervention supprimée."));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Impossible de supprimer l'intervention."));
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(array("message" => "Méthode non autorisée."));
        break;
}
?>