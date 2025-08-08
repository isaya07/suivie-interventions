<?php
// api/users.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../config/auth.php';
include_once '../models/User.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$user = new User($db);

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
            // Lire un utilisateur spécifique
            $user->id = $_GET['id'];
            if($user->readOne()) {
                $user_arr = array(
                    "id" => $user->id,
                    "username" => $user->username,
                    "email" => $user->email,
                    "nom" => $user->nom,
                    "prenom" => $user->prenom,
                    "role" => $user->role,
                    "telephone" => $user->telephone,
                    "specialite" => $user->specialite,
                    "avatar" => $user->avatar,
                    "is_active" => $user->is_active,
                    "last_login" => $user->last_login
                );
                http_response_code(200);
                echo json_encode($user_arr);
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "Utilisateur non trouvé"));
            }
        } elseif(isset($_GET['techniciens'])) {
            // Lister les techniciens
            $stmt = $user->readTechniciens();
            $techniciens = array();
            
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $techniciens[] = array(
                    "id" => $row['id'],
                    "nom_complet" => $row['prenom'] . ' ' . $row['nom'],
                    "specialite" => $row['specialite'],
                    "telephone" => $row['telephone'],
                    "email" => $row['email']
                );
            }
            
            http_response_code(200);
            echo json_encode(array("records" => $techniciens));
        } else {
            // Lister tous les utilisateurs (admin seulement)
            if(!$auth->hasPermission('admin')) {
                http_response_code(403);
                echo json_encode(array("message" => "Accès refusé"));
                exit;
            }
            
            $stmt = $user->read();
            $users = array();
            
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $users[] = array(
                    "id" => $row['id'],
                    "username" => $row['username'],
                    "email" => $row['email'],
                    "nom_complet" => $row['prenom'] . ' ' . $row['nom'],
                    "role" => $row['role'],
                    "telephone" => $row['telephone'],
                    "specialite" => $row['specialite'],
                    "is_active" => $row['is_active'],
                    "last_login" => $row['last_login'],
                    "created_at" => $row['created_at']
                );
            }
            
            http_response_code(200);
            echo json_encode(array("records" => $users));
        }
        break;

    case 'POST':
        // Créer un nouvel utilisateur (admin seulement)
        if(!$auth->hasPermission('admin')) {
            http_response_code(403);
            echo json_encode(array("message" => "Accès refusé"));
            exit;
        }
        
        $data = json_decode(file_get_contents("php://input"));

        if(!empty($data->username) && !empty($data->email) && !empty($data->password)) {
            $user->username = $data->username;
            $user->email = $data->email;
            $user->password_hash = password_hash($data->password, PASSWORD_DEFAULT);
            $user->nom = $data->nom;
            $user->prenom = $data->prenom;
            $user->role = $data->role ?? 'technicien';
            $user->telephone = $data->telephone ?? '';
            $user->specialite = $data->specialite ?? '';

            if($user->create()) {
                http_response_code(201);
                echo json_encode(array("message" => "Utilisateur créé avec succès", "id" => $user->id));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "Impossible de créer l'utilisateur"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Données incomplètes"));
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"));

        // Vérifier les permissions
        if($current_user['role'] !== 'admin' && $current_user['id'] != $data->id) {
            http_response_code(403);
            echo json_encode(array("message" => "Accès refusé"));
            exit;
        }

        $user->id = $data->id;
        $user->email = $data->email;
        $user->nom = $data->nom;
        $user->prenom = $data->prenom;
        $user->telephone = $data->telephone ?? '';
        $user->specialite = $data->specialite ?? '';
        
        // Seul l'admin peut modifier le rôle et le statut
        if($current_user['role'] === 'admin') {
            $user->role = $data->role;
            $user->is_active = $data->is_active ?? true;
        } else {
            // Récupérer les valeurs actuelles
            if($user->readOne()) {
                // Les valeurs sont déjà définies par readOne()
            }
        }

        if($user->update()) {
            http_response_code(200);
            echo json_encode(array("message" => "Utilisateur mis à jour"));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Impossible de mettre à jour l'utilisateur"));
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(array("message" => "Méthode non autorisée"));
        break;
}
?>