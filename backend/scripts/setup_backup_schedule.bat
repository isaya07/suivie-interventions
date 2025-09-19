@echo off
REM Script de configuration des backups automatiques pour Windows
REM Ce script configure le Planificateur de tâches Windows pour exécuter les backups

echo Configuration du système de backup automatique...
echo.

REM Variables de configuration
set SCRIPT_PATH=%~dp0backup_cron.php
set PHP_PATH=C:\wamp64\bin\php\php8.2.13\php.exe
set LOG_PATH=%~dp0..\logs\backup_schedule.log

REM Vérifier que PHP existe
if not exist "%PHP_PATH%" (
    echo ERREUR: PHP non trouvé à l'emplacement %PHP_PATH%
    echo Veuillez ajuster le chemin PHP dans ce script.
    pause
    exit /b 1
)

REM Vérifier que le script de backup existe
if not exist "%SCRIPT_PATH%" (
    echo ERREUR: Script de backup non trouvé: %SCRIPT_PATH%
    pause
    exit /b 1
)

echo Configuration des tâches planifiées...
echo.

REM Supprimer les anciennes tâches si elles existent
schtasks /delete /tn "Backup_Interventions_Daily" /f >nul 2>&1
schtasks /delete /tn "Backup_Interventions_Weekly" /f >nul 2>&1

REM Créer la tâche de backup quotidien (2h du matin)
echo Création de la tâche de backup quotidien...
schtasks /create /tn "Backup_Interventions_Daily" ^
    /tr "\"%PHP_PATH%\" \"%SCRIPT_PATH%\" --type=full" ^
    /sc daily /st 02:00 ^
    /ru "SYSTEM" ^
    /rl highest ^
    /f

if %errorlevel% equ 0 (
    echo ✓ Tâche quotidienne créée avec succès
) else (
    echo ✗ Erreur lors de la création de la tâche quotidienne
)

REM Créer la tâche de backup hebdomadaire (dimanche 3h du matin)
echo Création de la tâche de backup hebdomadaire...
schtasks /create /tn "Backup_Interventions_Weekly" ^
    /tr "\"%PHP_PATH%\" \"%SCRIPT_PATH%\" --type=full --force" ^
    /sc weekly /d SUN /st 03:00 ^
    /ru "SYSTEM" ^
    /rl highest ^
    /f

if %errorlevel% equ 0 (
    echo ✓ Tâche hebdomadaire créée avec succès
) else (
    echo ✗ Erreur lors de la création de la tâche hebdomadaire
)

echo.
echo Configuration terminée !
echo.
echo Les tâches suivantes ont été configurées :
echo - Backup quotidien : Tous les jours à 2h00
echo - Backup hebdomadaire : Tous les dimanches à 3h00
echo.
echo Pour vérifier les tâches :
echo   schtasks /query /tn "Backup_Interventions_Daily"
echo   schtasks /query /tn "Backup_Interventions_Weekly"
echo.
echo Pour tester immédiatement :
echo   schtasks /run /tn "Backup_Interventions_Daily"
echo.
echo Logs disponibles dans : %LOG_PATH%
echo.

REM Créer le répertoire de logs s'il n'existe pas
if not exist "%~dp0..\logs" mkdir "%~dp0..\logs"

REM Test rapide du script
echo Test du script de backup...
"%PHP_PATH%" "%SCRIPT_PATH%" --help
echo.

echo Configuration terminée. Appuyez sur une touche pour continuer...
pause >nul