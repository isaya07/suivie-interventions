<?php
/**
 * Tests unitaires pour la classe Validator
 *
 * Tests des fonctionnalités :
 * - Validation des données métier Enedis
 * - Validation des données sensibles
 * - Validation des formats (email, téléphone, SIRET)
 * - Cohérence des règles métier
 */

require_once __DIR__ . '/../utils/Validator.php';

class ValidatorTest {

    private $testResults = [];
    private $passedTests = 0;
    private $failedTests = 0;

    public function runAllTests() {
        echo "=== Tests du système de validation ===\n\n";

        // Tests de validation de base
        $this->testBasicValidation();

        // Tests de validation Enedis
        $this->testEnedisValidation();

        // Tests de sécurité
        $this->testSecurityValidation();

        // Tests de formats spécialisés
        $this->testSpecializedFormats();

        // Affichage des résultats
        $this->displayResults();
    }

    /**
     * Tests de validation de base
     */
    private function testBasicValidation() {
        echo "--- Tests de validation de base ---\n";

        // Test validateRequired
        $this->assert(
            Validator::validateRequired("test"),
            "validateRequired avec valeur valide"
        );

        $this->assert(
            !Validator::validateRequired(""),
            "validateRequired avec chaîne vide"
        );

        $this->assert(
            !Validator::validateRequired("   "),
            "validateRequired avec espaces seulement"
        );

        // Test validateEmail
        $this->assert(
            Validator::validateEmail("test@example.com"),
            "validateEmail avec email valide"
        );

        $this->assert(
            !Validator::validateEmail("invalid-email"),
            "validateEmail avec email invalide"
        );

        // Test validateLength
        $this->assert(
            Validator::validateLength("test", 3, 10),
            "validateLength avec longueur valide"
        );

        $this->assert(
            !Validator::validateLength("ab", 3, 10),
            "validateLength trop court"
        );

        $this->assert(
            !Validator::validateLength("trop long texte", 3, 10),
            "validateLength trop long"
        );

        // Test validateEnum
        $this->assert(
            Validator::validateEnum("option1", ["option1", "option2", "option3"]),
            "validateEnum avec valeur valide"
        );

        $this->assert(
            !Validator::validateEnum("invalid", ["option1", "option2", "option3"]),
            "validateEnum avec valeur invalide"
        );

        echo "\n";
    }

    /**
     * Tests de validation spécifique Enedis
     */
    private function testEnedisValidation() {
        echo "--- Tests de validation Enedis ---\n";

        // Test cohérence Type 1 vs DI
        $validType1Data = [
            'titre' => 'Test Type 1',
            'client_nom' => 'Client Test',
            'type_prestation_id' => 1,
            'type_reglementaire' => 'type_1',
            'longueur_derivation_individuelle' => 25, // <= 30m OK
            'mode_pose' => 'souterrain'
        ];

        $errors = Validator::validateInterventionElectrique($validType1Data);
        $this->assert(
            empty($errors),
            "Type 1 avec DI = 25m (valide)"
        );

        // Test incohérence Type 1 vs DI
        $invalidType1Data = [
            'titre' => 'Test Type 1 Invalide',
            'client_nom' => 'Client Test',
            'type_prestation_id' => 1,
            'type_reglementaire' => 'type_1',
            'longueur_derivation_individuelle' => 40, // > 30m KO
            'mode_pose' => 'souterrain'
        ];

        $errors = Validator::validateInterventionElectrique($invalidType1Data);
        $this->assert(
            !empty($errors) && strpos(implode(' ', $errors), 'Type 1') !== false,
            "Type 1 avec DI = 40m (invalide)"
        );

        // Test cohérence distances
        $validDistanceData = [
            'titre' => 'Test Distances',
            'client_nom' => 'Client Test',
            'type_prestation_id' => 1,
            'longueur_liaison_reseau' => 50,
            'longueur_derivation_individuelle' => 30,
            'distance_raccordement' => 80 // 50 + 30 = 80
        ];

        $errors = Validator::validateInterventionElectrique($validDistanceData);
        $this->assert(
            empty($errors) || !$this->containsDistanceError($errors),
            "Distances cohérentes (LR=50 + DI=30 = Total=80)"
        );

        // Test incohérence distances
        $invalidDistanceData = [
            'titre' => 'Test Distances Invalides',
            'client_nom' => 'Client Test',
            'type_prestation_id' => 1,
            'longueur_liaison_reseau' => 50,
            'longueur_derivation_individuelle' => 30,
            'distance_raccordement' => 100 // 50 + 30 ≠ 100
        ];

        $errors = Validator::validateInterventionElectrique($invalidDistanceData);
        $this->assert(
            !empty($errors) && $this->containsDistanceError($errors),
            "Distances incohérentes (LR=50 + DI=30 ≠ Total=100)"
        );

        // Test mode de pose valide
        $validModeData = [
            'titre' => 'Test Mode Pose',
            'client_nom' => 'Client Test',
            'type_prestation_id' => 1,
            'mode_pose' => 'aerien'
        ];

        $errors = Validator::validateInterventionElectrique($validModeData);
        $this->assert(
            empty($errors) || !$this->containsModeError($errors),
            "Mode de pose valide (aerien)"
        );

        // Test mode de pose invalide
        $invalidModeData = [
            'titre' => 'Test Mode Pose Invalide',
            'client_nom' => 'Client Test',
            'type_prestation_id' => 1,
            'mode_pose' => 'mode_inexistant'
        ];

        $errors = Validator::validateInterventionElectrique($invalidModeData);
        $this->assert(
            !empty($errors) && $this->containsModeError($errors),
            "Mode de pose invalide"
        );

        echo "\n";
    }

    /**
     * Tests de validation de sécurité
     */
    private function testSecurityValidation() {
        echo "--- Tests de validation de sécurité ---\n";

        // Test sanitizeString
        $this->assert(
            Validator::sanitizeString("<script>alert('xss')</script>") === "alert('xss')",
            "sanitizeString supprime les balises script"
        );

        // Test validateSensitiveData avec contenu sûr
        try {
            $result = Validator::validateSensitiveData("Contenu normal", "test");
            $this->assert(true, "validateSensitiveData avec contenu sûr");
        } catch (Exception $e) {
            $this->assert(false, "validateSensitiveData avec contenu sûr: " . $e->getMessage());
        }

        // Test validateSensitiveData avec contenu dangereux
        try {
            Validator::validateSensitiveData("<script>alert('xss')</script>", "test");
            $this->assert(false, "validateSensitiveData devrait rejeter le script");
        } catch (Exception $e) {
            $this->assert(true, "validateSensitiveData rejette le contenu dangereux");
        }

        // Test injection SQL
        try {
            Validator::validateSensitiveData("'; DROP TABLE interventions; --", "test");
            $this->assert(false, "validateSensitiveData devrait rejeter l'injection SQL");
        } catch (Exception $e) {
            $this->assert(true, "validateSensitiveData rejette l'injection SQL");
        }

        echo "\n";
    }

    /**
     * Tests des formats spécialisés
     */
    private function testSpecializedFormats() {
        echo "--- Tests des formats spécialisés ---\n";

        // Tests téléphone français
        $this->assert(
            Validator::validatePhoneNumber("0123456789"),
            "Téléphone français valide (01 23 45 67 89)"
        );

        $this->assert(
            Validator::validatePhoneNumber("+33123456789"),
            "Téléphone français avec +33"
        );

        $this->assert(
            !Validator::validatePhoneNumber("123456"),
            "Téléphone trop court"
        );

        $this->assert(
            !Validator::validatePhoneNumber("0023456789"),
            "Téléphone français invalide (commence par 00)"
        );

        // Tests SIRET
        $this->assert(
            Validator::validateSiret("73282932000074"), // SIRET valide d'exemple
            "SIRET valide"
        );

        $this->assert(
            !Validator::validateSiret("12345678901234"),
            "SIRET invalide (mauvais checksum)"
        );

        $this->assert(
            !Validator::validateSiret("123456789"),
            "SIRET trop court"
        );

        $this->assert(
            !Validator::validateSiret("abcd1234567890"),
            "SIRET avec caractères non numériques"
        );

        echo "\n";
    }

    /**
     * Méthodes utilitaires pour les tests
     */
    private function containsDistanceError($errors) {
        return array_filter($errors, function($error) {
            return strpos($error, 'Distance') !== false || strpos($error, 'incohérente') !== false;
        });
    }

    private function containsModeError($errors) {
        return array_filter($errors, function($error) {
            return strpos($error, 'Mode de pose') !== false || strpos($error, 'valide') !== false;
        });
    }

    /**
     * Framework de test simple
     */
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

        echo "=== Résultats des tests ===\n";
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
    $tester = new ValidatorTest();
    $tester->runAllTests();
}
?>