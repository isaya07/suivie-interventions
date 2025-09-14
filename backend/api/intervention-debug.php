<?php
// Debug API interventions sans auth
include_once 'cors.php';

require_once '../config/database.php';
require_once '../models/Intervention.php';

header("Content-Type: application/json; charset=UTF-8");

$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "Erreur de connexion à la base de données"));
    exit;
}

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
                http_response_code(200);
                echo json_encode(array("records" => array()));
            }
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(array("message" => "Méthode non autorisée."));
        break;
}
?>