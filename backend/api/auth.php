<?php
// api/auth.php
// Gérer CORS en premier
include_once 'cors.php';

require_once '../config/env.php';

header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';
include_once '../config/auth.php';

$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "Erreur de connexion à la base de données"));
    exit;
}

$auth = new Auth($db);

$method = $_SERVER['REQUEST_METHOD'];
$endpoint = $_GET['action'] ?? '';

switch($method . '_' . $endpoint) {
    case 'POST_login':
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->username) && !empty($data->password)) {
            $result = $auth->login($data->username, $data->password);
            
            if($result['success']) {
                http_response_code(200);
                echo json_encode($result);
            } else {
                http_response_code(401);
                echo json_encode($result);
            }
        } else {
            http_response_code(400);
            echo json_encode(array("success" => false, "message" => "Données incomplètes"));
        }
        break;

    case 'POST_logout':
        $result = $auth->logout();
        http_response_code(200);
        echo json_encode($result);
        break;

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

    case 'GET_test':
        echo json_encode([
            "message" => "API Auth fonctionne",
            "method" => $method,
            "endpoint" => $endpoint,
            "switch_case" => $method . '_' . $endpoint
        ]);
        break;

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