<?php
// Créer des utilisateurs de test
require_once 'config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->getConnection();

    if ($db === null) {
        throw new Exception("Connexion à la base de données échouée");
    }

    // Utilisateurs de test
    $users_test = [
        [
            'username' => 'jean.martin',
            'email' => 'jean.martin@entreprise.com',
            'password' => 'password123',
            'nom' => 'Martin',
            'prenom' => 'Jean',
            'role' => 'technicien',
            'telephone' => '06.12.34.56.78',
            'specialite' => 'Informatique, Réseaux'
        ],
        [
            'username' => 'marie.dubois',
            'email' => 'marie.dubois@entreprise.com',
            'password' => 'password123',
            'nom' => 'Dubois',
            'prenom' => 'Marie',
            'role' => 'technicien',
            'telephone' => '06.23.45.67.89',
            'specialite' => 'Électricité, Climatisation'
        ],
        [
            'username' => 'pierre.manager',
            'email' => 'pierre.manager@entreprise.com',
            'password' => 'password123',
            'nom' => 'Leroy',
            'prenom' => 'Pierre',
            'role' => 'manager',
            'telephone' => '06.34.56.78.90',
            'specialite' => 'Management, Coordination'
        ]
    ];

    $created_users = [];

    foreach ($users_test as $user_data) {
        // Vérifier si l'utilisateur existe déjà
        $check_query = "SELECT id FROM users WHERE username = :username OR email = :email";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':username', $user_data['username']);
        $check_stmt->bindParam(':email', $user_data['email']);
        $check_stmt->execute();

        if ($check_stmt->rowCount() > 0) {
            continue; // Utilisateur existe déjà
        }

        $query = "INSERT INTO users (
            username, email, password_hash, nom, prenom, role, telephone, specialite
        ) VALUES (
            :username, :email, :password_hash, :nom, :prenom, :role, :telephone, :specialite
        )";

        $stmt = $db->prepare($query);

        $password_hash = password_hash($user_data['password'], PASSWORD_DEFAULT);

        $params = [
            ':username' => $user_data['username'],
            ':email' => $user_data['email'],
            ':password_hash' => $password_hash,
            ':nom' => $user_data['nom'],
            ':prenom' => $user_data['prenom'],
            ':role' => $user_data['role'],
            ':telephone' => $user_data['telephone'],
            ':specialite' => $user_data['specialite']
        ];

        if ($stmt->execute($params)) {
            $created_users[] = [
                'id' => $db->lastInsertId(),
                'username' => $user_data['username'],
                'nom_complet' => $user_data['prenom'] . ' ' . $user_data['nom'],
                'role' => $user_data['role'],
                'specialite' => $user_data['specialite']
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'message' => count($created_users) . ' utilisateurs de test créés avec succès',
        'users' => $created_users
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
?>