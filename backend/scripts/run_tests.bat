@echo off
REM Script pour exécuter tous les tests du système

echo ===============================================
echo    SUITE DE TESTS - SUIVI INTERVENTIONS
echo ===============================================
echo.

REM Variables de configuration
set PHP_PATH=C:\wamp64\bin\php\php8.2.13\php.exe
set TESTS_DIR=%~dp0..\tests
set SCRIPT_PATH=%TESTS_DIR%\TestRunner.php

REM Vérifier que PHP existe
if not exist "%PHP_PATH%" (
    echo ERREUR: PHP non trouvé à l'emplacement %PHP_PATH%
    echo Veuillez ajuster le chemin PHP dans ce script.
    pause
    exit /b 1
)

REM Vérifier que le script de test existe
if not exist "%SCRIPT_PATH%" (
    echo ERREUR: Script de test non trouvé: %SCRIPT_PATH%
    pause
    exit /b 1
)

echo Démarrage des tests...
echo.

REM Variables d'environnement pour les tests
set RUN_PERFORMANCE_TESTS=false
set RUN_BACKUP_TEST=false
set CLEANUP_TEST_DATA=false

REM Afficher les options
echo Options de test:
echo - Tests de performance: %RUN_PERFORMANCE_TESTS%
echo - Test de backup complet: %RUN_BACKUP_TEST%
echo - Nettoyage des données: %CLEANUP_TEST_DATA%
echo.

REM Exécuter les tests
"%PHP_PATH%" "%SCRIPT_PATH%"

REM Vérifier le code de sortie
if %errorlevel% equ 0 (
    echo.
    echo ========================================
    echo    TESTS TERMINÉS AVEC SUCCÈS
    echo ========================================
) else (
    echo.
    echo ========================================
    echo    TESTS TERMINÉS AVEC ERREURS
    echo ========================================
)

echo.
echo Rapports générés dans: %TESTS_DIR%\reports\
echo.

REM Ouvrir le rapport HTML si disponible
if exist "%TESTS_DIR%\reports\test_report.html" (
    echo Voulez-vous ouvrir le rapport HTML ? (o/n)
    set /p choice=
    if /i "%choice%"=="o" (
        start "" "%TESTS_DIR%\reports\test_report.html"
    )
)

echo.
echo Options avancées:
echo - Pour activer les tests de performance: set RUN_PERFORMANCE_TESTS=true
echo - Pour activer le test de backup: set RUN_BACKUP_TEST=true
echo - Pour nettoyer les données de test: set CLEANUP_TEST_DATA=true
echo.

pause