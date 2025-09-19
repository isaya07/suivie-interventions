/**
 * Script de lancement des tests frontend
 * Exécute tous les tests et génère des rapports
 */

import { execSync } from 'child_process'
import { existsSync, writeFileSync, mkdirSync } from 'fs'
import { join } from 'path'

class FrontendTestRunner {
  private startTime: number
  private testResults: any[] = []

  constructor() {
    this.startTime = Date.now()
  }

  async runAllTests() {
    console.log('🧪 TESTS FRONTEND - SUIVI INTERVENTIONS')
    console.log('=' .repeat(50))
    console.log(`Démarrage: ${new Date().toLocaleString('fr-FR')}`)
    console.log()

    try {
      // 1. Type checking
      await this.runTypeCheck()

      // 2. Linting
      await this.runLinting()

      // 3. Tests unitaires
      await this.runUnitTests()

      // 4. Tests de couverture
      await this.runCoverageTests()

      // 5. Génération des rapports
      await this.generateReports()

    } catch (error) {
      console.error('❌ Erreur lors de l\'exécution des tests:', error)
      process.exit(1)
    }
  }

  private async runTypeCheck() {
    console.log('🔍 Vérification TypeScript...')

    try {
      execSync('npm run type-check', {
        stdio: 'inherit',
        cwd: process.cwd()
      })

      console.log('✅ Type checking réussi')
      this.testResults.push({
        suite: 'TypeScript',
        status: 'success',
        duration: 0,
        message: 'Aucune erreur de type détectée'
      })
    } catch (error) {
      console.log('❌ Erreurs de type détectées')
      this.testResults.push({
        suite: 'TypeScript',
        status: 'failed',
        duration: 0,
        message: 'Erreurs de type détectées'
      })
      throw error
    }
    console.log()
  }

  private async runLinting() {
    console.log('🔧 Vérification du code (ESLint)...')

    try {
      execSync('npm run lint', {
        stdio: 'inherit',
        cwd: process.cwd()
      })

      console.log('✅ Linting réussi')
      this.testResults.push({
        suite: 'ESLint',
        status: 'success',
        duration: 0,
        message: 'Code conforme aux standards'
      })
    } catch (error) {
      console.log('⚠️  Avertissements de linting (non bloquants)')
      this.testResults.push({
        suite: 'ESLint',
        status: 'warning',
        duration: 0,
        message: 'Avertissements de style détectés'
      })
      // Ne pas faire échouer les tests pour le linting
    }
    console.log()
  }

  private async runUnitTests() {
    console.log('🧪 Tests unitaires...')

    try {
      const output = execSync('npm test -- --reporter=json', {
        encoding: 'utf8',
        cwd: process.cwd()
      })

      // Parser les résultats Vitest
      const results = this.parseVitestResults(output)

      console.log(`✅ Tests unitaires terminés`)
      console.log(`   Tests passés: ${results.passed}`)
      console.log(`   Tests échoués: ${results.failed}`)
      console.log(`   Durée: ${results.duration}ms`)

      this.testResults.push({
        suite: 'Unit Tests',
        status: results.failed === 0 ? 'success' : 'failed',
        duration: results.duration,
        message: `${results.passed} passés, ${results.failed} échoués`,
        details: results
      })

      if (results.failed > 0) {
        throw new Error(`${results.failed} tests échoués`)
      }
    } catch (error) {
      console.log('❌ Échec des tests unitaires')
      this.testResults.push({
        suite: 'Unit Tests',
        status: 'failed',
        duration: 0,
        message: 'Tests unitaires échoués'
      })
      throw error
    }
    console.log()
  }

  private async runCoverageTests() {
    console.log('📊 Tests de couverture...')

    try {
      execSync('npm run test:coverage', {
        stdio: 'inherit',
        cwd: process.cwd()
      })

      console.log('✅ Rapport de couverture généré')
      this.testResults.push({
        suite: 'Coverage',
        status: 'success',
        duration: 0,
        message: 'Rapport de couverture disponible dans /coverage'
      })
    } catch (error) {
      console.log('⚠️  Couverture incomplète (non bloquant)')
      this.testResults.push({
        suite: 'Coverage',
        status: 'warning',
        duration: 0,
        message: 'Seuils de couverture non atteints'
      })
      // Ne pas faire échouer pour la couverture
    }
    console.log()
  }

  private parseVitestResults(output: string): any {
    try {
      // Essayer de parser la sortie JSON de Vitest
      const lines = output.split('\n')
      const jsonLine = lines.find(line => line.startsWith('{'))

      if (jsonLine) {
        const results = JSON.parse(jsonLine)
        return {
          passed: results.testResults?.reduce((acc: number, test: any) =>
            acc + (test.assertionResults?.filter((a: any) => a.status === 'passed').length || 0), 0) || 0,
          failed: results.testResults?.reduce((acc: number, test: any) =>
            acc + (test.assertionResults?.filter((a: any) => a.status === 'failed').length || 0), 0) || 0,
          duration: results.totalTime || 0
        }
      }
    } catch (e) {
      // Fallback si le parsing JSON échoue
    }

    // Fallback: parser la sortie texte
    const passedMatch = output.match(/(\d+) passed/)
    const failedMatch = output.match(/(\d+) failed/)
    const durationMatch = output.match(/(\d+(?:\.\d+)?)ms/)

    return {
      passed: passedMatch ? parseInt(passedMatch[1]) : 0,
      failed: failedMatch ? parseInt(failedMatch[1]) : 0,
      duration: durationMatch ? parseFloat(durationMatch[1]) : 0
    }
  }

  private async generateReports() {
    console.log('📋 Génération des rapports...')

    const reportsDir = join(process.cwd(), 'tests', 'reports')
    if (!existsSync(reportsDir)) {
      mkdirSync(reportsDir, { recursive: true })
    }

    const totalDuration = Date.now() - this.startTime
    const successCount = this.testResults.filter(r => r.status === 'success').length
    const failedCount = this.testResults.filter(r => r.status === 'failed').length
    const warningCount = this.testResults.filter(r => r.status === 'warning').length

    // Rapport JSON
    const jsonReport = {
      timestamp: new Date().toISOString(),
      duration: totalDuration,
      summary: {
        total: this.testResults.length,
        success: successCount,
        failed: failedCount,
        warnings: warningCount
      },
      results: this.testResults
    }

    writeFileSync(
      join(reportsDir, 'frontend-test-report.json'),
      JSON.stringify(jsonReport, null, 2)
    )

    // Rapport HTML
    const htmlReport = this.generateHtmlReport(jsonReport)
    writeFileSync(
      join(reportsDir, 'frontend-test-report.html'),
      htmlReport
    )

    console.log('✅ Rapports générés :')
    console.log(`   - ${join(reportsDir, 'frontend-test-report.json')}`)
    console.log(`   - ${join(reportsDir, 'frontend-test-report.html')}`)
    console.log()

    // Résumé final
    console.log('📊 RÉSUMÉ FINAL')
    console.log('=' .repeat(30))
    console.log(`⏱️  Durée totale: ${Math.round(totalDuration / 1000)}s`)
    console.log(`✅ Suites réussies: ${successCount}`)
    console.log(`❌ Suites échouées: ${failedCount}`)
    console.log(`⚠️  Avertissements: ${warningCount}`)
    console.log(`📈 Taux de réussite: ${Math.round((successCount / this.testResults.length) * 100)}%`)

    if (failedCount === 0) {
      console.log()
      console.log('🎉 TOUS LES TESTS SONT PASSÉS !')
      console.log('Frontend prêt pour la production')
    } else {
      console.log()
      console.log('⚠️  DES TESTS ONT ÉCHOUÉ')
      console.log('Vérifiez les erreurs ci-dessus')
    }
  }

  private generateHtmlReport(data: any): string {
    return `<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport de Tests Frontend - Suivi Interventions</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; margin: 20px; line-height: 1.6; }
        .header { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .stat-card { background: white; border: 1px solid #e9ecef; border-radius: 8px; padding: 15px; text-align: center; }
        .stat-value { font-size: 2em; font-weight: bold; margin-bottom: 5px; }
        .success { color: #28a745; }
        .failed { color: #dc3545; }
        .warning { color: #ffc107; }
        .test-results { margin-top: 30px; }
        .test-suite { border: 1px solid #e9ecef; border-radius: 8px; margin-bottom: 15px; overflow: hidden; }
        .suite-header { padding: 15px; font-weight: bold; display: flex; justify-content: space-between; align-items: center; }
        .suite-content { padding: 15px; background: #f8f9fa; }
        .status-badge { padding: 4px 12px; border-radius: 20px; color: white; font-size: 0.9em; }
        .status-success { background: #28a745; }
        .status-failed { background: #dc3545; }
        .status-warning { background: #ffc107; color: #212529; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6; }
        th { background: #f8f9fa; font-weight: 600; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🧪 Rapport de Tests Frontend</h1>
        <p><strong>Application:</strong> Suivi d'Interventions Électriques</p>
        <p><strong>Date:</strong> ${new Date(data.timestamp).toLocaleString('fr-FR')}</p>
        <p><strong>Durée totale:</strong> ${Math.round(data.duration / 1000)}s</p>
    </div>

    <div class="summary">
        <div class="stat-card">
            <div class="stat-value">${data.summary.total}</div>
            <div>Suites de tests</div>
        </div>
        <div class="stat-card">
            <div class="stat-value success">${data.summary.success}</div>
            <div>Réussies</div>
        </div>
        <div class="stat-card">
            <div class="stat-value failed">${data.summary.failed}</div>
            <div>Échouées</div>
        </div>
        <div class="stat-card">
            <div class="stat-value warning">${data.summary.warnings}</div>
            <div>Avertissements</div>
        </div>
    </div>

    <div class="test-results">
        <h2>Détails des Tests</h2>
        ${data.results.map((result: any) => `
            <div class="test-suite">
                <div class="suite-header">
                    <span>${result.suite}</span>
                    <span class="status-badge status-${result.status}">${result.status.toUpperCase()}</span>
                </div>
                <div class="suite-content">
                    <p><strong>Message:</strong> ${result.message}</p>
                    ${result.duration ? `<p><strong>Durée:</strong> ${result.duration}ms</p>` : ''}
                    ${result.details ? `
                        <table>
                            <tr><th>Métrique</th><th>Valeur</th></tr>
                            <tr><td>Tests passés</td><td class="success">${result.details.passed}</td></tr>
                            <tr><td>Tests échoués</td><td class="failed">${result.details.failed}</td></tr>
                            <tr><td>Durée</td><td>${result.details.duration}ms</td></tr>
                        </table>
                    ` : ''}
                </div>
            </div>
        `).join('')}
    </div>

    <div style="margin-top: 40px; padding: 20px; background: #e9ecef; border-radius: 8px; text-align: center;">
        <h3>${data.summary.failed === 0 ? '🎉 Tous les tests sont passés !' : '⚠️ Des tests ont échoué'}</h3>
        <p>Taux de réussite: ${Math.round((data.summary.success / data.summary.total) * 100)}%</p>
    </div>
</body>
</html>`
  }
}

// Exécution si appelé directement
if (require.main === module) {
  const runner = new FrontendTestRunner()
  runner.runAllTests().catch((error) => {
    console.error('Erreur fatale:', error)
    process.exit(1)
  })
}

export default FrontendTestRunner