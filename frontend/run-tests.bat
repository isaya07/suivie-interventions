@echo off
REM Script pour exécuter tous les tests frontend

echo ===============================================
echo    TESTS FRONTEND - SUIVI INTERVENTIONS
echo ===============================================
echo.

REM Vérifier que nous sommes dans le bon répertoire
if not exist "package.json" (
    echo ERREUR: package.json non trouvé
    echo Assurez-vous d'être dans le répertoire frontend
    pause
    exit /b 1
)

REM Vérifier que node_modules existe
if not exist "node_modules" (
    echo Installation des dépendances...
    npm install
    if %errorlevel% neq 0 (
        echo ERREUR: Échec de l'installation des dépendances
        pause
        exit /b 1
    )
)

echo Démarrage des tests...
echo.

REM Variables d'environnement
set NODE_ENV=test

REM 1. Type checking
echo [1/5] Vérification TypeScript...
call npm run type-check
if %errorlevel% neq 0 (
    echo ❌ Erreurs de type détectées
    echo.
    goto :show_results
) else (
    echo ✅ Type checking réussi
    echo.
)

REM 2. Linting
echo [2/5] Vérification du code (ESLint)...
call npm run lint
if %errorlevel% neq 0 (
    echo ⚠️  Avertissements de linting (non bloquants)
) else (
    echo ✅ Linting réussi
)
echo.

REM 3. Tests unitaires
echo [3/5] Tests unitaires...
call npm test
if %errorlevel% neq 0 (
    echo ❌ Échec des tests unitaires
    echo.
    goto :show_results
) else (
    echo ✅ Tests unitaires réussis
    echo.
)

REM 4. Tests de couverture
echo [4/5] Tests de couverture...
call npm run test:coverage
if %errorlevel% neq 0 (
    echo ⚠️  Couverture incomplète (non bloquant)
) else (
    echo ✅ Rapport de couverture généré
)
echo.

REM 5. Génération des rapports
echo [5/5] Génération des rapports...
if exist "tests\test-runner.ts" (
    call npx tsx tests/test-runner.ts
    if %errorlevel% equ 0 (
        echo ✅ Rapports générés
    ) else (
        echo ⚠️  Erreur de génération des rapports
    )
) else (
    echo ⚠️  Script de rapport non trouvé
)
echo.

:show_results
echo ===============================================
echo              RÉSULTATS FINAUX
echo ===============================================

REM Vérifier si les rapports ont été générés
if exist "tests\reports\frontend-test-report.html" (
    echo.
    echo 📋 Rapport HTML disponible : tests\reports\frontend-test-report.html
    echo 📋 Rapport JSON disponible : tests\reports\frontend-test-report.json
    echo.

    echo Voulez-vous ouvrir le rapport HTML ? (o/n)
    set /p choice=
    if /i "%choice%"=="o" (
        start "" "tests\reports\frontend-test-report.html"
    )
)

REM Vérifier si le rapport de couverture existe
if exist "coverage\index.html" (
    echo.
    echo 📊 Rapport de couverture disponible : coverage\index.html
    echo.

    echo Voulez-vous ouvrir le rapport de couverture ? (o/n)
    set /p choice=
    if /i "%choice%"=="o" (
        start "" "coverage\index.html"
    )
)

echo.
echo ===============================================
echo Options pour les prochaines exécutions :
echo.
echo npm test                 - Tests unitaires seulement
echo npm run test:ui         - Interface de test interactive
echo npm run test:coverage   - Tests avec couverture
echo npm run type-check      - Vérification TypeScript
echo npm run lint           - Vérification du code
echo npm run lint:fix       - Correction automatique du code
echo.
echo ===============================================

pause