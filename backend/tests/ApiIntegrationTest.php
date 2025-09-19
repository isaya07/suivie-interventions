<?php
/**
 * Tests d'intégration pour l'API intervention_electrique
 *
 * Tests des endpoints :
 * - Création d'intervention
 * - Mise à jour d'intervention
 * - Gestion des phases
 * - Audit trail
 * - Backup system
 */

class ApiIntegrationTest {

    private $baseUrl;
    private $sessionCookie;
    private $testResults = [];
    private $passedTests = 0;
    private $failedTests = 0;
    private $testData = [];

    public function __construct() {
        $this->baseUrl = 'http://localhost/api';

        // Données de test
        $this->testData = [
            'admin_user' => [
                'username' => 'admin',
                'password' => 'admin123'
            ],
            'test_intervention' => [
                'titre' => 'Test Intervention - ' . date('Y-m-d H:i:s'),
                'description' => 'Intervention de test automatique',
                'client_nom' => 'Client Test API',
                'type_prestation_id' => 1,
                'type_reglementaire' => 'type_1',
                'mode_pose' => 'souterrain',
                'longueur_liaison_reseau' => 50,
                'longueur_derivation_individuelle' => 25,
                'distance_raccordement' => 75
            ]
        ];
    }

    public function runAllTests() {
        echo "=== Tests d'intégration API ===\n\n";

        try {
            // 1. Authentification
            $this->testAuthentication();

            // 2. Tests CRUD interventions
            $this->testInterventionCrud();

            // 3. Tests de validation
            $this->testValidationIntegration();

            // 4. Tests des phases
            $this->testPhaseManagement();

            // 5. Tests audit trail
            $this->testAuditTrail();

            // 6. Tests backup system
            $this->testBackupSystem();

        } catch (Exception $e) {
            echo "ERREUR CRITIQUE: " . $e->getMessage() . "\n";
        }

        // Nettoyage
        $this->cleanup();

        // Affichage des résultats
        $this->displayResults();
    }

    /**
     * Test d'authentification
     */
    private function testAuthentication() {
        echo "--- Tests d'authentification ---\n";

        // Test login
        $loginData = [
            'username' => $this->testData['admin_user']['username'],
            'password' => $this->testData['admin_user']['password']
        ];

        $response = $this->makeRequest('POST', '/auth.php?action=login', $loginData);

        $this->assert(
            $response !== false && isset($response['success']) && $response['success'],
            "Authentification admin réussie"
        );

        if ($response && isset($response['success']) && $response['success']) {
            // Extraire le cookie de session pour les prochaines requêtes
            $this->sessionCookie = $this->extractSessionCookie();
        }

        echo "\n";
    }

    /**
     * Tests CRUD des interventions
     */
    private function testInterventionCrud() {
        echo "--- Tests CRUD interventions ---\n";

        // Test création
        $response = $this->makeRequest('POST', '/intervention_electrique.php', $this->testData['test_intervention']);

        $this->assert(
            $response !== false && isset($response['success']) && $response['success'],
            "Création d'intervention électrique"
        );

        if ($response && isset($response['data']['id'])) {
            $interventionId = $response['data']['id'];
            $this->testData['intervention_id'] = $interventionId;

            // Test lecture
            $response = $this->makeRequest('GET', "/intervention_electrique.php?id={$interventionId}");

            $this->assert(
                $response !== false && isset($response['success']) && $response['success'],
                "Lecture d'intervention électrique"
            );

            // Vérifier les données
            if ($response && isset($response['data'])) {
                $data = $response['data'];
                $this->assert(
                    $data['titre'] === $this->testData['test_intervention']['titre'],
                    "Données d'intervention correctes"
                );

                $this->assert(
                    $data['specifications']['type_reglementaire'] === 'type_1',
                    "Spécifications Enedis correctes"
                );
            }

            // Test mise à jour
            $updateData = [
                'id' => $interventionId,
                'description' => 'Description mise à jour - ' . date('H:i:s'),
                'mode_pose' => 'aerien'
            ];

            $response = $this->makeRequest('PUT', '/intervention_electrique.php', $updateData);

            $this->assert(
                $response !== false && isset($response['success']) && $response['success'],
                "Mise à jour d'intervention électrique"
            );
        }

        echo "\n";
    }

    /**
     * Tests de validation intégrée
     */
    private function testValidationIntegration() {
        echo "--- Tests de validation intégrée ---\n";

        // Test données invalides - Type 1 avec DI > 30m
        $invalidData = [
            'titre' => 'Test Validation',
            'client_nom' => 'Client Test',
            'type_prestation_id' => 1,
            'type_reglementaire' => 'type_1',
            'longueur_derivation_individuelle' => 40 // Invalide pour Type 1
        ];

        $response = $this->makeRequest('POST', '/intervention_electrique.php', $invalidData);

        $this->assert(
            $response === false || (isset($response['success']) && !$response['success']),
            "Validation rejette Type 1 avec DI > 30m"
        );

        // Test données avec injection
        $maliciousData = [
            'titre' => '<script>alert("xss")</script>',
            'description' => "'; DROP TABLE interventions; --",
            'client_nom' => 'Client Test',
            'type_prestation_id' => 1
        ];

        $response = $this->makeRequest('POST', '/intervention_electrique.php', $maliciousData);

        $this->assert(
            $response === false || (isset($response['success']) && !$response['success']),
            "Validation rejette les données malveillantes"
        );

        echo "\n";
    }

    /**
     * Tests de gestion des phases
     */
    private function testPhaseManagement() {
        echo "--- Tests de gestion des phases ---\n";

        if (!isset($this->testData['intervention_id'])) {
            echo "Intervention de test non disponible - tests ignorés\n\n";
            return;
        }

        $interventionId = $this->testData['intervention_id'];

        // Test démarrage de phase
        $phaseData = [
            'intervention_id' => $interventionId,
            'phase' => 'branchement',
            'technicien_id' => 1
        ];

        $response = $this->makeRequest('POST', '/intervention_electrique.php?action=demarrer_phase', $phaseData);

        $this->assert(
            $response !== false && isset($response['success']) && $response['success'],
            "Démarrage de phase branchement"
        );

        // Attendre un moment pour avoir une durée mesurable
        sleep(1);

        // Test arrêt de phase
        $stopData = [
            'intervention_id' => $interventionId,
            'phase' => 'branchement',
            'notes' => 'Test d\'arrêt de phase'
        ];

        $response = $this->makeRequest('POST', '/intervention_electrique.php?action=arreter_phase', $stopData);

        $this->assert(
            $response !== false && isset($response['success']) && $response['success'],
            "Arrêt de phase branchement"
        );

        // Test fin de phase
        $endData = [
            'intervention_id' => $interventionId,
            'phase' => 'branchement',
            'notes' => 'Phase terminée avec succès'
        ];

        $response = $this->makeRequest('POST', '/intervention_electrique.php?action=terminer_phase', $endData);

        $this->assert(
            $response !== false && isset($response['success']) && $response['success'],
            "Fin de phase branchement"
        );

        echo "\n";
    }

    /**
     * Tests audit trail
     */
    private function testAuditTrail() {
        echo "--- Tests audit trail ---\n";

        if (!isset($this->testData['intervention_id'])) {
            echo "Intervention de test non disponible - tests ignorés\n\n";
            return;
        }

        $interventionId = $this->testData['intervention_id'];

        // Test récupération historique
        $response = $this->makeRequest('GET', "/intervention_electrique.php?action=audit&id={$interventionId}");

        $this->assert(
            $response !== false && isset($response['success']) && $response['success'],
            "Récupération historique d'audit"
        );

        if ($response && isset($response['data'])) {
            $this->assert(
                count($response['data']) > 0,
                "Historique d'audit contient des entrées"
            );

            // Vérifier qu'il y a des actions de création et de phase
            $actions = array_column($response['data'], 'action');
            $this->assert(
                in_array('creation_intervention_electrique', $actions),
                "Audit contient l'action de création"
            );
        }

        // Test actions critiques
        $response = $this->makeRequest('GET', '/intervention_electrique.php?action=audit_critical');

        $this->assert(
            $response !== false && isset($response['success']) && $response['success'],
            "Récupération actions critiques"
        );

        // Test statistiques d'audit
        $response = $this->makeRequest('GET', '/intervention_electrique.php?action=audit_stats');

        $this->assert(
            $response !== false && isset($response['success']) && $response['success'],
            "Récupération statistiques d'audit"
        );

        echo "\n";
    }

    /**
     * Tests système de backup
     */
    private function testBackupSystem() {
        echo "--- Tests système de backup ---\n";

        // Test statut du système
        $response = $this->makeRequest('GET', '/backup.php?action=status');

        $this->assert(
            $response !== false && isset($response['success']) && $response['success'],
            "Statut du système de backup"
        );

        // Test liste des backups
        $response = $this->makeRequest('GET', '/backup.php?action=list');

        $this->assert(
            $response !== false && isset($response['success']) && $response['success'],
            "Liste des backups disponibles"
        );

        // Test backup complet (optionnel - peut être long)
        if (getenv('RUN_BACKUP_TEST') === 'true') {
            $response = $this->makeRequest('POST', '/backup.php?action=full');

            $this->assert(
                $response !== false && isset($response['success']) && $response['success'],
                "Création d'un backup complet"
            );
        } else {
            echo "⏸ Test backup complet ignoré (définir RUN_BACKUP_TEST=true pour l'activer)\n";
        }

        // Test logs de backup
        $response = $this->makeRequest('GET', '/backup.php?action=logs');

        $this->assert(
            $response !== false && isset($response['success']) && $response['success'],
            "Récupération logs de backup"
        );

        echo "\n";
    }

    /**
     * Nettoyage après les tests
     */
    private function cleanup() {
        echo "--- Nettoyage ---\n";

        // Optionnel: supprimer l'intervention de test
        if (isset($this->testData['intervention_id']) && getenv('CLEANUP_TEST_DATA') === 'true') {
            // Ici on pourrait implémenter la suppression
            echo "Nettoyage des données de test activé\n";
        } else {
            echo "Données de test conservées (intervention ID: " .
                 ($this->testData['intervention_id'] ?? 'N/A') . ")\n";
        }

        // Déconnexion
        $this->makeRequest('POST', '/auth.php?action=logout');

        echo "\n";
    }

    /**
     * Méthodes utilitaires
     */
    private function makeRequest($method, $endpoint, $data = null) {
        $url = $this->baseUrl . $endpoint;

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ]
        ]);

        // Ajouter le cookie de session si disponible
        if ($this->sessionCookie) {
            curl_setopt($ch, CURLOPT_COOKIE, $this->sessionCookie);
        }

        // Ajouter les données pour POST/PUT
        if ($data && in_array($method, ['POST', 'PUT'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        // Capturer les headers pour récupérer les cookies
        curl_setopt($ch, CURLOPT_HEADER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

        curl_close($ch);

        if ($response === false) {
            return false;
        }

        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        // Extraire et sauvegarder les cookies de session
        if (preg_match('/Set-Cookie: PHPSESSID=([^;]+)/', $headers, $matches)) {
            $this->sessionCookie = 'PHPSESSID=' . $matches[1];
        }

        $decodedResponse = json_decode($body, true);

        return $decodedResponse;
    }

    private function extractSessionCookie() {
        // Cette méthode est appelée après une authentification réussie
        return $this->sessionCookie;
    }

    private function assert($condition, $testName) {
        if ($condition) {
            echo "✓ {$testName}\n";
            $this->passedTests++;
        } else {
            echo "✗ {$testName}\n";
            $this->failedTests++;
        }

        $this->testResults[] = [
            'name' => $testName,
            'passed' => $condition
        ];
    }

    private function displayResults() {
        $total = $this->passedTests + $this->failedTests;

        echo "=== Résultats des tests d'intégration ===\n";
        echo "Total: {$total} tests\n";
        echo "Réussis: {$this->passedTests}\n";
        echo "Échoués: {$this->failedTests}\n";
        echo "Taux de réussite: " . round(($this->passedTests / $total) * 100, 2) . "%\n\n";

        if ($this->failedTests > 0) {
            echo "Tests échoués:\n";
            foreach ($this->testResults as $result) {
                if (!$result['passed']) {
                    echo "- {$result['name']}\n";
                }
            }
        }
    }
}

// Exécution des tests si le script est appelé directement
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $tester = new ApiIntegrationTest();
    $tester->runAllTests();
}
?>