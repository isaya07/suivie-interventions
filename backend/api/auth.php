<?php
// api/auth.php
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
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../config/database.php';
include_once '../config/auth.php';

$database = new Database();
$db = $database->getConnection();
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

    default:
        http_response_code(404);
        echo json_encode(array("message" => "Endpoint non trouvé"));
        break;
}
?>