<?php
// Créer un utilisateur de test
require_once 'config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->getConnection();

    if ($db === null) {
        throw new Exception("Connexion à la base de données échouée");
    }

    // Créer un utilisateur admin de test
    $username = 'admin';
    $email = 'admin@test.com';
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $nom = 'Admin';
    $prenom = 'Test';
    $role = 'admin';

    $query = "INSERT INTO users (username, email, password_hash, nom, prenom, role)
              VALUES (:username, :email, :password_hash, :nom, :prenom, :role)
              ON DUPLICATE KEY UPDATE
              password_hash = :password_hash2, nom = :nom2, prenom = :prenom2, role = :role2";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password_hash', $password);
    $stmt->bindParam(':password_hash2', $password);
    $stmt->bindParam(':nom', $nom);
    $stmt->bindParam(':nom2', $nom);
    $stmt->bindParam(':prenom', $prenom);
    $stmt->bindParam(':prenom2', $prenom);
    $stmt->bindParam(':role', $role);
    $stmt->bindParam(':role2', $role);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Utilisateur de test créé avec succès',
            'credentials' => [
                'username' => $username,
                'password' => 'admin123',
                'email' => $email
            ]
        ]);
    } else {
        throw new Exception("Erreur lors de la création de l'utilisateur");
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
?>