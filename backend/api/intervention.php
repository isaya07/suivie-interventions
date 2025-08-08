<?php
// api/interventions.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../models/Intervention.php';

$database = new Database();
$db = $database->getConnection();
$intervention = new Intervention($db);

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

        if(!empty($data->titre) && !empty($data->client)) {
            $intervention->titre = $data->titre;
            $intervention->description = $data->description;
            $intervention->client = $data->client;
            $intervention->technicien = $data->technicien;
            $intervention->statut = $data->statut ?? 'En attente';
            $intervention->priorite = $data->priorite ?? 'Normale';
            $intervention->date_creation = date('Y-m-d H:i:s');
            $intervention->date_intervention = $data->date_intervention;

            if($intervention->create()) {
                http_response_code(201);
                echo json_encode(array("message" => "Intervention créée avec succès."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "Impossible de créer l'intervention."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Données incomplètes."));
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