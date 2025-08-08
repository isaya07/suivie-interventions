<?php
// api/upload.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../config/auth.php';
include_once '../models/FileUpload.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Vérifier l'authentification
if (!$auth->isAuthenticated()) {
    http_response_code(401);
    echo json_encode(array("message" => "Authentification requise"));
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$current_user = $auth->getCurrentUser();
$fileUpload = new FileUpload($db);

switch($method) {
    case 'POST':
        if(isset($_FILES['files']) && isset($_POST['intervention_id'])) {
            $intervention_id = $_POST['intervention_id'];
            $type_fichier = $_POST['type_fichier'] ?? 'autre';
            $description = $_POST['description'] ?? '';

            // Vérifier que l'intervention existe et que l'utilisateur a les droits
            // (À implémenter selon vos règles métier)

            $uploaded_files = $fileUpload->upload(
                $intervention_id, 
                $_FILES['files'], 
                $current_user['id'], 
                $type_fichier, 
                $description
            );

            if(!empty($uploaded_files)) {
                http_response_code(200);
                echo json_encode(array(
                    "success" => true, 
                    "message" => count($uploaded_files) . " fichier(s) uploadé(s) avec succès",
                    "files" => $uploaded_files
                ));
            } else {
                http_response_code(400);
                echo json_encode(array("success" => false, "message" => "Aucun fichier n'a pu être uploadé"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("success" => false, "message" => "Données incomplètes"));
        }
        break;

    case 'GET':
        if(isset($_GET['intervention_id'])) {
            $intervention_id = $_GET['intervention_id'];
            $stmt = $fileUpload->getByIntervention($intervention_id);
            $files = array();

            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $files[] = array(
                    "id" => $row['id'],
                    "nom_original" => $row['nom_original'],
                    "nom_fichier" => $row['nom_fichier'],
                    "type_mime" => $row['type_mime'],
                    "taille" => $row['taille'],
                    "type_fichier" => $row['type_fichier'],
                    "description" => $row['description'],
                    "uploaded_at" => $row['uploaded_at'],
                    "uploader" => $row['uploader_prenom'] . ' ' . $row['uploader_nom'],
                    "download_url" => "download.php?id=" . $row['id']
                );
            }

            http_response_code(200);
            echo json_encode(array("files" => $files));
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "intervention_id requis"));
        }
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->id)) {
            if($fileUpload->delete($data->id, $current_user['id'], $current_user['role'])) {
                http_response_code(200);
                echo json_encode(array("message" => "Fichier supprimé avec succès"));
            } else {
                http_response_code(403);
                echo json_encode(array("message" => "Accès refusé ou fichier non trouvé"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "ID du fichier requis"));
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(array("message" => "Méthode non autorisée"));
        break;
}
?>