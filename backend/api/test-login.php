<?php
// Test de l'endpoint de login
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

// Simuler une requête POST vers auth.php
$url = 'http://localhost/suivie-interventions/backend/api/auth.php?action=login';

$data = [
    'username' => 'admin', // Changez selon vos données de test
    'password' => 'password' // Changez selon vos données de test
];

$options = [
    'http' => [
        'header' => "Content-Type: application/json\r\n",
        'method' => 'POST',
        'content' => json_encode($data)
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo json_encode([
    'test_result' => $result,
    'response_headers' => $http_response_header ?? null
]);
?>