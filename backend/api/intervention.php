<?php
// api/interventions.php
require_once '../config/env.php';

$env = include '../config/env.php';
$cors_origin = $env['CORS_ORIGIN'] ?? 'http://localhost:3000';

header("Access-Control-Allow-Origin: $cors_origin");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../config/database.php';
include_once '../config/auth.php';
include_once '../models/Intervention.php';
include_once '../utils/Validator.php';
include_once '../utils/ErrorHandler.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$intervention = new Intervention($db);

// Check authentication for all endpoints
if (!$auth->isAuthenticated()) {
    ErrorHandler::handleAuthError();
}

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        if(isset($_GET['id'])) {
            // Lire une intervention spécifique
            $intervention->id = $_GET['id'];
            if($intervention->readOne()) {
                $intervention_arr = array(
                    "id" => $intervention->id,
                    "titre" => $intervention->titre,
                    "description" => $intervention->description,
                    "client" => $intervention->client,
                    "technicien" => $intervention->technicien,
                    "statut" => $intervention->statut,
                    "priorite" => $intervention->priorite,
                    "date_creation" => $intervention->date_creation,
                    "date_intervention" => $intervention->date_intervention,
                    "date_fin" => $intervention->date_fin
                );
                http_response_code(200);
                echo json_encode($intervention_arr);
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "Intervention non trouvée."));
            }
        } else {
            // Lire toutes les interventions
            $stmt = $intervention->read();
            $num = $stmt->rowCount();

            if($num > 0) {
                $interventions_arr = array();
                $interventions_arr["records"] = array();

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    extract($row);
                    $intervention_item = array(
                        "id" => $id,
                        "titre" => $titre,
                        "description" => $description,
                        "client" => $client,
                        "technicien" => $technicien,
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
                http_response_code(200);
                echo json_encode(array("records" => array()));
            }
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));

        if (!$data) {
            ErrorHandler::handleError(400, "Format JSON invalide");
        }

        // Validate input data
        $validationErrors = Validator::validateInterventionData($data);
        if (!empty($validationErrors)) {
            ErrorHandler::handleValidationErrors($validationErrors);
        }

        try {
            $current_user = $auth->getCurrentUser();

            $intervention->titre = Validator::sanitizeString($data->titre);
            $intervention->description = Validator::sanitizeString($data->description ?? '');
            $intervention->client_id = $data->client_id ?? null;
            $intervention->client_nom = Validator::sanitizeString($data->client_nom ?? '');
            $intervention->technicien_id = $data->technicien_id ?? null;
            $intervention->technicien_nom = Validator::sanitizeString($data->technicien_nom ?? '');
            $intervention->createur_id = $current_user['id'];
            $intervention->statut = $data->statut ?? 'En attente';
            $intervention->priorite = $data->priorite ?? 'Normale';
            $intervention->type_intervention = $data->type_intervention ?? 'Réparation';
            $intervention->temps_estime = $data->temps_estime ?? null;
            $intervention->cout_prevu = $data->cout_prevu ?? null;
            $intervention->date_creation = date('Y-m-d H:i:s');
            $intervention->date_intervention = $data->date_intervention ?? null;
            $intervention->date_limite = $data->date_limite ?? null;

            if($intervention->create()) {
                ErrorHandler::handleSuccess("Intervention créée avec succès", ['id' => $intervention->id], 201);
            } else {
                ErrorHandler::handleServerError("Impossible de créer l'intervention");
            }
        } catch (Exception $e) {
            ErrorHandler::logError($e->getMessage(), ['data' => $data]);
            ErrorHandler::handleServerError("Erreur lors de la création de l'intervention");
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"));

        $intervention->id = $data->id;
        $intervention->titre = $data->titre;
        $intervention->description = $data->description;
        $intervention->client = $data->client;
        $intervention->technicien = $data->technicien;
        $intervention->statut = $data->statut;
        $intervention->priorite = $data->priorite;
        $intervention->date_intervention = $data->date_intervention;
        $intervention->date_fin = $data->date_fin;

        if($intervention->update()) {
            http_response_code(200);
            echo json_encode(array("message" => "Intervention mise à jour."));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Impossible de mettre à jour l'intervention."));
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