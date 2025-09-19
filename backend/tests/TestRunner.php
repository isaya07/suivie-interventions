<?php
/**
 * Lanceur de tests pour le système de suivi d'interventions
 *
 * Exécute tous les tests unitaires et d'intégration
 * Génère un rapport de tests complet
 */

class TestRunner {

    private $testResults = [];
    private $startTime;
    private $outputFormat = 'console'; // console, html, json

    public function __construct($outputFormat = 'console') {
        $this->outputFormat = $outputFormat;
        $this->startTime = microtime(true);
    }

    public function runAllTests() {
        $this->output("=== SUITE DE TESTS COMPLÈTE ===\n");
        $this->output("Démarrage: " . date('Y-m-d H:i:s') . "\n\n");

        // Tests unitaires
        $this->runUnitTests();

        // Tests d'intégration
        $this->runIntegrationTests();

        // Tests de performance (optionnel)
        if (getenv('RUN_PERFORMANCE_TESTS') === 'true') {
            $this->runPerformanceTests();
        }

        // Génération du rapport final
        $this->generateReport();
    }

    /**
     * Exécute les tests unitaires
     */
    private function runUnitTests() {
        $this->output("🧪 TESTS UNITAIRES\n");
        $this->output(str_repeat("=", 50) . "\n\n");

        // Test du validateur
        $this->runTest('Validator', function() {
            require_once __DIR__ . '/ValidatorTest.php';

            ob_start();
            $tester = new ValidatorTest();
            $tester->runAllTests();
            $output = ob_get_clean();

            return $this->parseTestOutput($output);
        });

        // Test de l'audit trail
        $this->runTest('AuditTrail', function() {
            return $this->testAuditTrailUnit();
        });

        // Test du système de backup
        $this->runTest('BackupSystem', function() {
            return $this->testBackupSystemUnit();
        });
    }

    /**
     * Exécute les tests d'intégration
     */
    private function runIntegrationTests() {
        $this->output("\n🔗 TESTS D'INTÉGRATION\n");
        $this->output(str_repeat("=", 50) . "\n\n");

        // Vérifier que le serveur web est accessible
        if (!$this->checkWebServerAvailability()) {
            $this->output("⚠️  Serveur web non accessible - tests d'intégration ignorés\n\n");
            return;
        }

        // Tests API
        $this->runTest('API Integration', function() {
            require_once __DIR__ . '/ApiIntegrationTest.php';

            ob_start();
            $tester = new ApiIntegrationTest();
            $tester->runAllTests();
            $output = ob_get_clean();

            return $this->parseTestOutput($output);
        });
    }

    /**
     * Tests de performance
     */
    private function runPerformanceTests() {
        $this->output("\n⚡ TESTS DE PERFORMANCE\n");
        $this->output(str_repeat("=", 50) . "\n\n");

        // Test de charge validation
        $this->runTest('Validation Performance', function() {
            return $this->testValidationPerformance();
        });

        // Test de charge base de données
        $this->runTest('Database Performance', function() {
            return $this->testDatabasePerformance();
        });
    }

    /**
     * Tests unitaires spécialisés
     */
    private function testAuditTrailUnit() {
        require_once __DIR__ . '/../utils/AuditTrail.php';
        require_once __DIR__ . '/../config/database.php';

        try {
            $database = new Database();
            $db = $database->getConnection();

            if (!$db) {
                return ['success' => false, 'message' => 'Connexion DB échouée'];
            }

            $auditTrail = AuditTrail::getInstance($db);

            // Test basique de logging
            $result = $auditTrail->logInterventionChange(
                999, // ID fictif
                1,   // User ID
                'test_action',
                ['old' => 'value'],
                ['new' => 'value'],
                'Test unitaire'
            );

            return [
                'success' => $result !== false,
                'message' => $result ? 'AuditTrail fonctionne' : 'Échec AuditTrail',
                'tests_passed' => $result ? 1 : 0,
                'tests_failed' => $result ? 0 : 1
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur AuditTrail: ' . $e->getMessage(),
                'tests_passed' => 0,
                'tests_failed' => 1
            ];
        }
    }

    private function testBackupSystemUnit() {
        require_once __DIR__ . '/../utils/BackupSystem.php';
        require_once __DIR__ . '/../config/database.php';

        try {
            $database = new Database();
            $db = $database->getConnection();

            if (!$db) {
                return ['success' => false, 'message' => 'Connexion DB échouée'];
            }

            $backupSystem = new BackupSystem($db);

            // Test de listage des backups
            $backups = $backupSystem->listBackups();

            return [
                'success' => is_array($backups),
                'message' => is_array($backups) ? 'BackupSystem fonctionne' : 'Échec BackupSystem',
                'tests_passed' => is_array($backups) ? 1 : 0,
                'tests_failed' => is_array($backups) ? 0 : 1
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur BackupSystem: ' . $e->getMessage(),
                'tests_passed' => 0,
                'tests_failed' => 1
            ];
        }
    }

    private function testValidationPerformance() {
        require_once __DIR__ . '/../utils/Validator.php';

        $iterations = 1000;
        $startTime = microtime(true);

        $testData = [
            'titre' => 'Test Performance',
            'client_nom' => 'Client Performance',
            'type_prestation_id' => 1,
            'type_reglementaire' => 'type_1',
            'mode_pose' => 'souterrain',
            'longueur_liaison_reseau' => 50,
            'longueur_derivation_individuelle' => 25
        ];

        for ($i = 0; $i < $iterations; $i++) {
            Validator::validateInterventionElectrique($testData);
        }

        $duration = microtime(true) - $startTime;
        $avgTime = ($duration / $iterations) * 1000; // en ms

        return [
            'success' => $avgTime < 10, // Moins de 10ms par validation
            'message' => sprintf('Validation: %.2fms moyenne sur %d itérations', $avgTime, $iterations),
            'tests_passed' => $avgTime < 10 ? 1 : 0,
            'tests_failed' => $avgTime < 10 ? 0 : 1,
            'performance_data' => [
                'average_time_ms' => $avgTime,
                'total_time_s' => $duration,
                'iterations' => $iterations
            ]
        ];
    }

    private function testDatabasePerformance() {
        require_once __DIR__ . '/../config/database.php';

        try {
            $database = new Database();
            $db = $database->getConnection();

            if (!$db) {
                return ['success' => false, 'message' => 'Connexion DB échouée'];
            }

            $iterations = 100;
            $startTime = microtime(true);

            for ($i = 0; $i < $iterations; $i++) {
                $stmt = $db->query("SELECT COUNT(*) FROM interventions");
                $stmt->fetch();
            }

            $duration = microtime(true) - $startTime;
            $avgTime = ($duration / $iterations) * 1000; // en ms

            return [
                'success' => $avgTime < 50, // Moins de 50ms par requête
                'message' => sprintf('DB Query: %.2fms moyenne sur %d requêtes', $avgTime, $iterations),
                'tests_passed' => $avgTime < 50 ? 1 : 0,
                'tests_failed' => $avgTime < 50 ? 0 : 1,
                'performance_data' => [
                    'average_time_ms' => $avgTime,
                    'total_time_s' => $duration,
                    'iterations' => $iterations
                ]
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur performance DB: ' . $e->getMessage(),
                'tests_passed' => 0,
                'tests_failed' => 1
            ];
        }
    }

    /**
     * Méthodes utilitaires
     */
    private function runTest($testName, $testFunction) {
        $this->output("Exécution: {$testName}...\n");

        $startTime = microtime(true);

        try {
            $result = $testFunction();
            $duration = microtime(true) - $startTime;

            $this->testResults[] = [
                'name' => $testName,
                'result' => $result,
                'duration' => $duration,
                'timestamp' => date('Y-m-d H:i:s')
            ];

            if ($result['success']) {
                $this->output("✅ {$testName} - " . $result['message'] .
                             " (" . round($duration * 1000, 2) . "ms)\n");
            } else {
                $this->output("❌ {$testName} - " . $result['message'] .
                             " (" . round($duration * 1000, 2) . "ms)\n");
            }

        } catch (Exception $e) {
            $duration = microtime(true) - $startTime;

            $this->testResults[] = [
                'name' => $testName,
                'result' => [
                    'success' => false,
                    'message' => 'Exception: ' . $e->getMessage(),
                    'tests_passed' => 0,
                    'tests_failed' => 1
                ],
                'duration' => $duration,
                'timestamp' => date('Y-m-d H:i:s')
            ];

            $this->output("💥 {$testName} - Exception: " . $e->getMessage() .
                         " (" . round($duration * 1000, 2) . "ms)\n");
        }

        $this->output("\n");
    }

    private function checkWebServerAvailability() {
        $testUrl = 'http://localhost/api/auth.php';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $testUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_NOBODY, true);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $result !== false && $httpCode < 500;
    }

    private function parseTestOutput($output) {
        // Parser simple pour extraire les résultats
        $lines = explode("\n", $output);
        $passed = 0;
        $failed = 0;

        foreach ($lines as $line) {
            if (strpos($line, '✓') === 0) {
                $passed++;
            } elseif (strpos($line, '✗') === 0) {
                $failed++;
            }
        }

        return [
            'success' => $failed === 0,
            'message' => "Tests: {$passed} réussis, {$failed} échoués",
            'tests_passed' => $passed,
            'tests_failed' => $failed,
            'raw_output' => $output
        ];
    }

    private function generateReport() {
        $totalDuration = microtime(true) - $this->startTime;

        $this->output("\n📊 RAPPORT FINAL\n");
        $this->output(str_repeat("=", 50) . "\n");

        $totalPassed = 0;
        $totalFailed = 0;
        $totalTests = count($this->testResults);

        foreach ($this->testResults as $testResult) {
            $result = $testResult['result'];
            $totalPassed += $result['tests_passed'] ?? 0;
            $totalFailed += $result['tests_failed'] ?? 0;
        }

        $successRate = $totalTests > 0 ? round(($totalPassed / ($totalPassed + $totalFailed)) * 100, 2) : 0;

        $this->output("Suites de tests exécutées: {$totalTests}\n");
        $this->output("Tests individuels: " . ($totalPassed + $totalFailed) . "\n");
        $this->output("✅ Réussis: {$totalPassed}\n");
        $this->output("❌ Échoués: {$totalFailed}\n");
        $this->output("📈 Taux de réussite: {$successRate}%\n");
        $this->output("⏱️  Durée totale: " . round($totalDuration, 2) . "s\n");

        // Générer des rapports dans différents formats
        $this->generateHtmlReport();
        $this->generateJsonReport();

        $this->output("\n📁 Rapports générés:\n");
        $this->output("- tests/reports/test_report.html\n");
        $this->output("- tests/reports/test_report.json\n");
    }

    private function generateHtmlReport() {
        $reportDir = __DIR__ . '/reports';
        if (!is_dir($reportDir)) {
            mkdir($reportDir, 0755, true);
        }

        $totalPassed = 0;
        $totalFailed = 0;

        foreach ($this->testResults as $testResult) {
            $result = $testResult['result'];
            $totalPassed += $result['tests_passed'] ?? 0;
            $totalFailed += $result['tests_failed'] ?? 0;
        }

        $html = '<!DOCTYPE html>
<html>
<head>
    <title>Rapport de Tests - Suivi Interventions</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { background: #f0f0f0; padding: 20px; border-radius: 5px; }
        .success { color: green; }
        .failure { color: red; }
        .test-suite { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .performance { background: #fff3cd; padding: 10px; border-radius: 3px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f8f9fa; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Rapport de Tests</h1>
        <p><strong>Date:</strong> ' . date('Y-m-d H:i:s') . '</p>
        <p><strong>Taux de réussite:</strong> ' . round(($totalPassed / ($totalPassed + $totalFailed)) * 100, 2) . '%</p>
        <p><span class="success">✅ ' . $totalPassed . ' réussis</span> | <span class="failure">❌ ' . $totalFailed . ' échoués</span></p>
    </div>

    <h2>Détails des Tests</h2>
    <table>
        <tr>
            <th>Suite de Tests</th>
            <th>Statut</th>
            <th>Message</th>
            <th>Durée (ms)</th>
        </tr>';

        foreach ($this->testResults as $testResult) {
            $result = $testResult['result'];
            $status = $result['success'] ? '<span class="success">✅ Réussi</span>' : '<span class="failure">❌ Échoué</span>';
            $duration = round($testResult['duration'] * 1000, 2);

            $html .= "<tr>
                <td>{$testResult['name']}</td>
                <td>{$status}</td>
                <td>{$result['message']}</td>
                <td>{$duration}</td>
            </tr>";
        }

        $html .= '</table>
</body>
</html>';

        file_put_contents($reportDir . '/test_report.html', $html);
    }

    private function generateJsonReport() {
        $reportDir = __DIR__ . '/reports';
        if (!is_dir($reportDir)) {
            mkdir($reportDir, 0755, true);
        }

        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'total_duration' => microtime(true) - $this->startTime,
            'test_results' => $this->testResults,
            'summary' => [
                'total_suites' => count($this->testResults),
                'passed_suites' => count(array_filter($this->testResults, function($r) { return $r['result']['success']; })),
                'failed_suites' => count(array_filter($this->testResults, function($r) { return !$r['result']['success']; }))
            ]
        ];

        file_put_contents($reportDir . '/test_report.json', json_encode($report, JSON_PRETTY_PRINT));
    }

    private function output($message) {
        echo $message;
    }
}

// Exécution si appelé directement
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $outputFormat = $argv[1] ?? 'console';
    $runner = new TestRunner($outputFormat);
    $runner->runAllTests();
}
?>