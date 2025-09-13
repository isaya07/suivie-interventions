<?php
// api/download.php
require_once '../config/env.php';

$env = include '../config/env.php';
$cors_origin = $env['CORS_ORIGIN'] ?? 'http://localhost:3000';

header("Access-Control-Allow-Origin: $cors_origin");
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

// Vérifier l'authentification
if (!$auth->isAuthenticated()) {
    http_response_code(401);
    echo "Authentification requise";
    exit;
}

if(isset($_GET['id'])) {
    $file_id = $_GET['id'];
    
    $query = "SELECT * FROM fichiers_intervention WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $file_id);
    $stmt->execute();
    
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($file && file_exists($file['chemin_fichier'])) {
        // Définir les en-têtes appropriés
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $file['type_mime']);
        header('Content-Disposition: attachment; filename="' . $file['nom_original'] . '"');
        header('Content-Length: ' . filesize($file['chemin_fichier']));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        
        // Envoyer le fichier
        readfile($file['chemin_fichier']);
        exit;
    } else {
        http_response_code(404);
        echo "Fichier non trouvé";
    }
} else {
    http_response_code(400);
    echo "ID du fichier requis";
}
?>