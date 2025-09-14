<?php
// api/clients.php
include_once 'cors.php';

require_once '../config/env.php';
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';
include_once '../config/auth.php';
include_once '../models/Client.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$client = new Client($db);

// Vérifier l'authentification
if (!$auth->isAuthenticated()) {
    http_response_code(401);
    echo json_encode(array("message" => "Authentification requise"));
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$current_user = $auth->getCurrentUser();

switch($method) {
    case 'GET':
        if(isset($_GET['id'])) {
            // Lire un client spécifique
            $client->id = $_GET['id'];
            if($client->readOne()) {
                $client_arr = array(
                    "id" => $client->id,
                    "nom" => $client->nom,
                    "email" => $client->email,
                    "telephone" => $client->telephone,
                    "adresse" => $client->adresse,
                    "ville" => $client->ville,
                    "code_postal" => $client->code_postal,
                    "contact_principal" => $client->contact_principal,
                    "notes" => $client->notes,
                    "latitude" => $client->latitude,
                    "longitude" => $client->longitude,
                    "user_id" => $client->user_id,
                    "created_at" => $client->created_at,
                    "updated_at" => $client->updated_at
                );
                http_response_code(200);
                echo json_encode($client_arr);
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "Client non trouvé"));
            }
        } elseif(isset($_GET['location'])) {
            // Recherche par proximité GPS
            $lat = $_GET['lat'] ?? null;
            $lng = $_GET['lng'] ?? null;
            $radius = $_GET['radius'] ?? 10;

            if($lat && $lng) {
                $stmt = $client->findByLocation($lat, $lng, $radius);
                $clients = array();

                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $clients[] = array(
                        "id" => $row['id'],
                        "nom" => $row['nom'],
                        "email" => $row['email'],
                        "telephone" => $row['telephone'],
                        "adresse" => $row['adresse'],
                        "ville" => $row['ville'],
                        "code_postal" => $row['code_postal'],
                        "contact_principal" => $row['contact_principal'],
                        "latitude" => $row['latitude'],
                        "longitude" => $row['longitude'],
                        "distance" => round($row['distance'], 2)
                    );
                }

                http_response_code(200);
                echo json_encode(array("records" => $clients));
            } else {
                http_response_code(400);
                echo json_encode(array("message" => "Coordonnées GPS requises"));
            }
        } else {
            // Lister tous les clients
            $stmt = $client->read();
            $clients = array();

            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $clients[] = array(
                    "id" => $row['id'],
                    "nom" => $row['nom'],
                    "email" => $row['email'],
                    "telephone" => $row['telephone'],
                    "adresse" => $row['adresse'],
                    "ville" => $row['ville'],
                    "code_postal" => $row['code_postal'],
                    "contact_principal" => $row['contact_principal'],
                    "notes" => $row['notes'],
                    "latitude" => $row['latitude'],
                    "longitude" => $row['longitude'],
                    "user_id" => $row['user_id'],
                    "created_at" => $row['created_at'],
                    "updated_at" => $row['updated_at']
                );
            }

            http_response_code(200);
            echo json_encode(array("records" => $clients));
        }
        break;

    case 'POST':
        // Créer un nouveau client
        $data = json_decode(file_get_contents("php://input"));

        if(!empty($data->nom)) {
            $client->nom = $data->nom;
            $client->email = $data->email ?? '';
            $client->telephone = $data->telephone ?? '';
            $client->adresse = $data->adresse ?? '';
            $client->ville = $data->ville ?? '';
            $client->code_postal = $data->code_postal ?? '';
            $client->contact_principal = $data->contact_principal ?? '';
            $client->notes = $data->notes ?? '';
            $client->latitude = $data->latitude ?? null;
            $client->longitude = $data->longitude ?? null;
            $client->user_id = $current_user['id'];

            if($client->create()) {
                http_response_code(201);
                echo json_encode(array("message" => "Client créé avec succès", "id" => $client->id));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "Impossible de créer le client"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Nom du client requis"));
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"));

        if (!$data) {
            http_response_code(400);
            echo json_encode(array("message" => "Format JSON invalide"));
            exit;
        }

        if (!isset($data->id) || empty($data->id)) {
            http_response_code(400);
            echo json_encode(array("message" => "ID client requis"));
            exit;
        }

        // Validate email format if provided
        if(isset($data->email) && !empty($data->email) && !filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(array("message" => "Format d'email invalide"));
            exit;
        }

        $client->id = $data->id;

        // Check if client exists
        if (!$client->readOne()) {
            http_response_code(404);
            echo json_encode(array("message" => "Client non trouvé"));
            exit;
        }

        // Update fields
        $client->nom = $data->nom ?? $client->nom;
        $client->email = $data->email ?? $client->email;
        $client->telephone = $data->telephone ?? $client->telephone;
        $client->adresse = $data->adresse ?? $client->adresse;
        $client->ville = $data->ville ?? $client->ville;
        $client->code_postal = $data->code_postal ?? $client->code_postal;
        $client->contact_principal = $data->contact_principal ?? $client->contact_principal;
        $client->notes = $data->notes ?? $client->notes;
        $client->latitude = isset($data->latitude) ? $data->latitude : $client->latitude;
        $client->longitude = isset($data->longitude) ? $data->longitude : $client->longitude;

        if($client->update()) {
            http_response_code(200);
            echo json_encode(array("message" => "Client mis à jour avec succès"));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Impossible de mettre à jour le client"));
        }
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->id) || empty($data->id)) {
            http_response_code(400);
            echo json_encode(array("message" => "ID client requis"));
            exit;
        }

        // Seul l'admin peut supprimer des clients
        if($current_user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(array("message" => "Accès refusé"));
            exit;
        }

        $client->id = $data->id;

        if($client->delete()) {
            http_response_code(200);
            echo json_encode(array("message" => "Client supprimé avec succès"));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Impossible de supprimer le client"));
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(array("message" => "Méthode non autorisée"));
        break;
}
?>