<?php
/**
 * API de gestion des interventions électriques avec phases
 *
 * Endpoints spécialisés pour les interventions de branchement électrique :
 * - GET /intervention_electrique.php : Liste des interventions électriques
 * - GET /intervention_electrique.php?id=X : Détails complets d'une intervention
 * - POST /intervention_electrique.php : Création d'une nouvelle intervention électrique
 * - PUT /intervention_electrique.php : Mise à jour d'une intervention
 * - POST /intervention_electrique.php?action=demarrer_phase : Démarrer une phase (chronomètre)
 * - POST /intervention_electrique.php?action=arreter_phase : Arrêter une phase (pause)
 * - POST /intervention_electrique.php?action=terminer_phase : Terminer définitivement une phase
 * - GET /intervention_electrique.php?action=sessions&id=X : Sessions de travail détaillées
 * - GET /intervention_electrique.php?action=get_active_session&intervention_id=X : Vérifier session active
 * - GET /intervention_electrique.php?action=statistiques&id=X : Statistiques d'intervention
 *
 * Ordre des phases : 1) Terrassement (si applicable), 2) Branchement électrique
 */

// Configuration CORS et en-têtes
include_once 'cors.php';
require_once '../config/env.php';
header("Content-Type: application/json; charset=UTF-8");

// Import des dépendances
include_once '../config/database.php';
include_once '../config/auth.php';
include_once '../models/InterventionElectrique.php';
include_once '../utils/Validator.php';
include_once '../utils/ErrorHandler.php';
include_once '../utils/AuditTrail.php';

// Initialisation des services
$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    ErrorHandler::handleServerError("Erreur de connexion à la base de données");
}

$auth = new Auth($db);
$intervention = new InterventionElectrique($db);
$auditTrail = AuditTrail::getInstance($db);

// Vérification de l'authentification
if (!$auth->isAuthenticated()) {
    ErrorHandler::handleAuthError();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$intervention_id = $_GET['id'] ?? null;

// Routage des endpoints selon méthode HTTP et action
switch($method . '_' . $action) {
    /**
     * GET /intervention_electrique.php
     * Récupère la liste complète des interventions électriques avec informations des phases
     */
    case 'GET_':
        if($intervention_id) {
            // Récupération d'une intervention spécifique
            $intervention->id = $intervention_id;

            try {
                if($intervention->readOne()) {
                $response = [
                    'success' => true,
                    'data' => [
                        // Informations de base
                        'id' => $intervention->id,
                        'numero' => $intervention->numero,
                        'titre' => $intervention->titre,
                        'description' => $intervention->description,
                        'priorite' => $intervention->priorite,
                        'client_id' => $intervention->client_id,
                        'client_nom' => $intervention->client_nom_complet,
                        'client_contact' => $intervention->client_contact,
                        'client_telephone' => $intervention->client_telephone,
                        'createur_id' => $intervention->createur_id,

                        // Type de prestation
                        'type_prestation' => [
                            'id' => $intervention->type_prestation_id,
                            'nom' => $intervention->type_prestation_nom,
                            'code' => $intervention->type_prestation_code,
                            'has_terrassement' => $intervention->has_terrassement,
                            'duree_terrassement_estimee' => $intervention->duree_terrassement_estimee,
                            'duree_branchement_estimee' => $intervention->duree_branchement_estimee
                        ],

                        // Spécifications Enedis
                        'specifications' => [
                            'type_reglementaire' => $intervention->type_reglementaire,
                            'mode_pose' => $intervention->mode_pose,
                            'longueur_liaison_reseau' => $intervention->longueur_liaison_reseau,
                            'longueur_derivation_individuelle' => $intervention->longueur_derivation_individuelle,
                            'distance_raccordement' => $intervention->distance_raccordement
                        ],

                        // Suivi du processus
                        'suivi_processus' => [
                            'date_reception_dossier' => $intervention->date_reception_dossier,
                            'date_etude_technique' => $intervention->date_etude_technique,
                            'date_validation_devis' => $intervention->date_validation_devis,
                            'date_realisation_terrassement' => $intervention->date_realisation_terrassement,
                            'date_realisation_cablage' => $intervention->date_realisation_cablage,
                            'date_mise_en_service' => $intervention->date_mise_en_service
                        ],

                        // Phase 1: Terrassement (si applicable)
                        'phase_terrassement' => [
                            'statut' => $intervention->phase_terrassement_statut,
                            'technicien_id' => $intervention->phase_terrassement_technicien_id,
                            'technicien_nom' => $intervention->technicien_terrassement_nom . ' ' . $intervention->technicien_terrassement_prenom,
                            'taux_horaire' => $intervention->phase_terrassement_taux_horaire,
                            'date_debut' => $intervention->phase_terrassement_date_debut,
                            'date_fin' => $intervention->phase_terrassement_date_fin,
                            'duree_heures' => $intervention->phase_terrassement_duree_heures,
                            'cout' => $intervention->phase_terrassement_cout,
                            'notes' => $intervention->phase_terrassement_notes
                        ],

                        // Phase 2: Branchement électrique
                        'phase_branchement' => [
                            'statut' => $intervention->phase_branchement_statut,
                            'technicien_id' => $intervention->phase_branchement_technicien_id,
                            'technicien_nom' => $intervention->technicien_branchement_nom . ' ' . $intervention->technicien_branchement_prenom,
                            'taux_horaire' => $intervention->phase_branchement_taux_horaire,
                            'date_debut' => $intervention->phase_branchement_date_debut,
                            'date_fin' => $intervention->phase_branchement_date_fin,
                            'duree_heures' => $intervention->phase_branchement_duree_heures,
                            'cout' => $intervention->phase_branchement_cout,
                            'notes' => $intervention->phase_branchement_notes
                        ],

                        // Planification
                        'planification' => [
                            'date_terrassement_prevue' => $intervention->date_terrassement_prevue,
                            'date_branchement_prevue' => $intervention->date_branchement_prevue
                        ],

                        // Totaux et calculs
                        'totaux' => [
                            'duree_totale_heures' => $intervention->duree_totale_heures,
                            'cout_total_reel' => $intervention->cout_total_reel,
                            'cout_total_estime' => $intervention->cout_total_estime,
                            'ecart_budget' => $intervention->ecart_budget,
                            'ecart_pourcentage' => $intervention->ecart_pourcentage
                        ],

                        // Métadonnées
                        'date_creation' => $intervention->date_creation,
                        'date_modification' => $intervention->date_modification
                    ]
                ];

                    http_response_code(200);
                    echo json_encode($response);
                } else {
                    ErrorHandler::handleNotFoundError("Intervention non trouvée");
                }
            } catch (Exception $e) {
                error_log("Erreur readOne intervention: " . $e->getMessage());
                ErrorHandler::handleServerError("Erreur lors du chargement de l'intervention");
            }
        } else {
            // Liste de toutes les interventions
            $stmt = $intervention->readAll();
            $interventions_arr = [];

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $intervention_item = [
                    'id' => $row['id'],
                    'numero' => $row['numero'],
                    'titre' => $row['titre'],
                    'description' => $row['description'],
                    'priorite' => $row['priorite'],
                    'client_nom' => $row['client_nom_complet'],
                    'type_prestation_nom' => $row['type_prestation_nom'],
                    'has_terrassement' => $row['has_terrassement'],

                    // Statuts des phases
                    'phase_terrassement_statut' => $row['phase_terrassement_statut'],
                    'phase_branchement_statut' => $row['phase_branchement_statut'],
                    'statut_global' => $row['statut_global'],

                    // Techniciens assignés
                    'technicien_terrassement' => $row['technicien_terrassement_complet'],
                    'technicien_branchement' => $row['technicien_branchement_complet'],

                    // Planification
                    'date_terrassement_prevue' => $row['date_terrassement_prevue'] ?? null,
                    'date_branchement_prevue' => $row['date_branchement_prevue'] ?? null,

                    // Totaux
                    'duree_totale_heures' => $row['duree_totale_heures'] ?? null,
                    'cout_total_reel' => $row['cout_total_reel'] ?? null,
                    'cout_total_estime' => $row['cout_total_estime'] ?? null,

                    'date_creation' => $row['date_creation']
                ];

                array_push($interventions_arr, $intervention_item);
            }

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $interventions_arr,
                'total' => count($interventions_arr)
            ]);
        }
        break;

    /**
     * POST /intervention_electrique.php
     * Création d'une nouvelle intervention électrique avec configuration des phases
     */
    case 'POST_':
        $data = json_decode(file_get_contents("php://input"));

        if (!$data) {
            ErrorHandler::handleError(400, "Format JSON invalide");
        }

        // Validation stricte des données métier Enedis
        $validationData = [
            'titre' => $data->titre ?? '',
            'client_nom' => $data->client_nom ?? '',
            'type_prestation_id' => $data->type_prestation_id ?? null,
            'type_reglementaire' => $data->type_reglementaire ?? '',
            'mode_pose' => $data->mode_pose ?? '',
            'longueur_liaison_reseau' => $data->longueur_liaison_reseau ?? 0,
            'longueur_derivation_individuelle' => $data->longueur_derivation_individuelle ?? 0,
            'distance_raccordement' => $data->distance_raccordement ?? 0,
            'puissance_souscrite' => $data->puissance_souscrite ?? null,
            'has_terrassement' => $data->has_terrassement ?? false,
            'cout_total_estime' => $data->cout_total_estime ?? 0,
            'taux_horaire' => $data->taux_horaire ?? 0
        ];

        $validationErrors = Validator::validateInterventionElectrique($validationData);

        if (!empty($validationErrors)) {
            ErrorHandler::handleError(400, "Erreurs de validation: " . implode(', ', $validationErrors));
        }

        try {
            $current_user = $auth->getCurrentUser();

            // Récupération des informations du type de prestation
            $prestation_query = "SELECT * FROM types_prestations WHERE id = :id";
            $prestation_stmt = $db->prepare($prestation_query);
            $prestation_stmt->bindParam(":id", $data->type_prestation_id);
            $prestation_stmt->execute();
            $prestation = $prestation_stmt->fetch(PDO::FETCH_ASSOC);

            if (!$prestation) {
                ErrorHandler::handleError(400, "Type de prestation invalide");
            }

            // Récupération des taux horaires des techniciens
            $taux_terrassement = 38.00; // Valeur par défaut
            $taux_branchement = 45.00;  // Valeur par défaut

            if (isset($data->technicien_terrassement_id)) {
                $taux_query = "SELECT taux_horaire FROM users WHERE id = :id";
                $taux_stmt = $db->prepare($taux_query);
                $taux_stmt->bindParam(":id", $data->technicien_terrassement_id);
                $taux_stmt->execute();
                $taux_result = $taux_stmt->fetch(PDO::FETCH_ASSOC);
                if ($taux_result) $taux_terrassement = $taux_result['taux_horaire'];
            }

            if (isset($data->technicien_branchement_id)) {
                $taux_query = "SELECT taux_horaire FROM users WHERE id = :id";
                $taux_stmt = $db->prepare($taux_query);
                $taux_stmt->bindParam(":id", $data->technicien_branchement_id);
                $taux_stmt->execute();
                $taux_result = $taux_stmt->fetch(PDO::FETCH_ASSOC);
                if ($taux_result) $taux_branchement = $taux_result['taux_horaire'];
            }

            // Calcul du coût estimé
            $cout_estime = 0;
            if ($prestation['has_terrassement']) {
                $cout_estime += $prestation['duree_terrassement_heures'] * $taux_terrassement;
            }
            $cout_estime += $prestation['duree_branchement_heures'] * $taux_branchement;

            // Configuration de l'intervention avec données sécurisées
            $intervention->titre = Validator::sanitizeString($data->titre);
            $intervention->description = isset($data->description) ? Validator::validateSensitiveData($data->description, 'description') : '';
            $intervention->client_id = $data->client_id ?? null;
            $intervention->client_nom = isset($data->client_nom) ? Validator::sanitizeString($data->client_nom) : '';
            $intervention->createur_id = $current_user['id'];
            $intervention->priorite = $data->priorite ?? 'normale';
            $intervention->type_prestation_id = $data->type_prestation_id;
            $intervention->has_terrassement = $prestation['has_terrassement'];

            // Nouveaux champs Enedis
            $intervention->type_reglementaire = isset($data->type_reglementaire) ? $data->type_reglementaire : ($prestation['type_reglementaire'] ?? 'type_1');
            $intervention->mode_pose = isset($data->mode_pose) ? $data->mode_pose : ($prestation['mode_pose'] ?? 'souterrain');
            $intervention->longueur_liaison_reseau = isset($data->longueur_liaison_reseau) ? $data->longueur_liaison_reseau : 0;
            $intervention->longueur_derivation_individuelle = isset($data->longueur_derivation_individuelle) ? $data->longueur_derivation_individuelle : 0;

            // Calculer distance si pas fournie
            if (isset($data->distance_raccordement)) {
                $intervention->distance_raccordement = $data->distance_raccordement;
            } else {
                $intervention->distance_raccordement = $intervention->longueur_liaison_reseau + $intervention->longueur_derivation_individuelle;
            }

            // Dates de suivi du processus
            $intervention->date_reception_dossier = isset($data->date_reception_dossier) ? $data->date_reception_dossier : date('Y-m-d H:i:s');
            $intervention->date_etude_technique = isset($data->date_etude_technique) ? $data->date_etude_technique : null;
            $intervention->date_validation_devis = isset($data->date_validation_devis) ? $data->date_validation_devis : null;

            // Configuration phase terrassement
            $intervention->phase_terrassement_technicien_id = $data->technicien_terrassement_id ?? null;
            $intervention->phase_terrassement_taux_horaire = $taux_terrassement;

            // Configuration phase branchement
            $intervention->phase_branchement_technicien_id = $data->technicien_branchement_id ?? null;
            $intervention->phase_branchement_taux_horaire = $taux_branchement;

            // Planification
            $intervention->date_terrassement_prevue = $data->date_terrassement_prevue ?? null;
            $intervention->date_branchement_prevue = $data->date_branchement_prevue ?? null;

            // Estimation budgétaire
            $intervention->cout_total_estime = $cout_estime;

            if($intervention->create()) {
                // Audit trail : enregistrer la création
                $newData = [
                    'titre' => $intervention->titre,
                    'client_nom' => $intervention->client_nom,
                    'type_reglementaire' => $intervention->type_reglementaire,
                    'mode_pose' => $intervention->mode_pose,
                    'cout_total_estime' => $intervention->cout_total_estime,
                    'statut' => 'nouvelle'
                ];

                $auditTrail->logInterventionChange(
                    $intervention->id,
                    $current_user['id'],
                    'creation_intervention_electrique',
                    null,
                    $newData,
                    "Création d'une nouvelle intervention électrique: {$intervention->titre}"
                );

                http_response_code(201);
                echo json_encode([
                    'success' => true,
                    'message' => 'Intervention électrique créée avec succès',
                    'data' => [
                        'id' => $intervention->id,
                        'cout_estime' => $cout_estime,
                        'phases' => $prestation['has_terrassement'] ?
                            ['terrassement', 'branchement'] : ['branchement']
                    ]
                ]);
            } else {
                ErrorHandler::handleServerError("Impossible de créer l'intervention");
            }

        } catch (Exception $e) {
            error_log("Erreur création intervention électrique: " . $e->getMessage());
            ErrorHandler::handleServerError("Erreur lors de la création");
        }
        break;

    /**
     * POST /intervention_electrique.php?action=demarrer_phase
     * Démarre une phase d'intervention (active le chronomètre)
     * Body: { "intervention_id": X, "phase": "terrassement|branchement", "technicien_id": Y }
     */
    case 'POST_demarrer_phase':
        $data = json_decode(file_get_contents("php://input"));

        if (!$data || !isset($data->intervention_id) || !isset($data->phase) || !isset($data->technicien_id)) {
            ErrorHandler::handleError(400, "Données requises: intervention_id, phase, technicien_id");
        }

        if (!in_array($data->phase, ['terrassement', 'branchement'])) {
            ErrorHandler::handleError(400, "Phase invalide. Valeurs autorisées: terrassement, branchement");
        }

        $intervention->id = $data->intervention_id;

        if($intervention->demarrerPhase($data->phase, $data->technicien_id)) {
            // Audit trail : démarrage de phase
            $current_user = $auth->getCurrentUser();
            $auditTrail->logInterventionChange(
                $data->intervention_id,
                $current_user['id'],
                "demarrage_phase_{$data->phase}",
                null,
                ['phase' => $data->phase, 'technicien_id' => $data->technicien_id, 'statut' => 'en_cours'],
                "Démarrage de la phase {$data->phase} par technicien ID: {$data->technicien_id}"
            );

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => "Phase {$data->phase} démarrée avec succès",
                'data' => [
                    'intervention_id' => $data->intervention_id,
                    'phase' => $data->phase,
                    'technicien_id' => $data->technicien_id,
                    'heure_debut' => date('Y-m-d H:i:s')
                ]
            ]);
        } else {
            ErrorHandler::handleServerError("Impossible de démarrer la phase");
        }
        break;

    /**
     * POST /intervention_electrique.php?action=arreter_phase
     * Arrête une phase en cours (pause du chronomètre)
     * Body: { "intervention_id": X, "phase": "terrassement|branchement", "notes": "..." }
     */
    case 'POST_arreter_phase':
        $data = json_decode(file_get_contents("php://input"));

        if (!$data || !isset($data->intervention_id) || !isset($data->phase)) {
            ErrorHandler::handleError(400, "Données requises: intervention_id, phase");
        }

        $intervention->id = $data->intervention_id;
        $notes = isset($data->notes) ? Validator::validateSensitiveData($data->notes, 'notes') : null;

        if($intervention->arreterPhase($data->phase, $notes)) {
            // Audit trail : arrêt de phase
            $current_user = $auth->getCurrentUser();
            $auditTrail->logInterventionChange(
                $data->intervention_id,
                $current_user['id'],
                "arret_phase_{$data->phase}",
                ['statut' => 'en_cours'],
                ['phase' => $data->phase, 'statut' => 'en_pause', 'notes' => $notes],
                "Arrêt de la phase {$data->phase}" . ($notes ? " - Notes: {$notes}" : "")
            );

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => "Phase {$data->phase} arrêtée avec succès",
                'data' => [
                    'intervention_id' => $data->intervention_id,
                    'phase' => $data->phase,
                    'heure_fin' => date('Y-m-d H:i:s')
                ]
            ]);
        } else {
            ErrorHandler::handleServerError("Impossible d'arrêter la phase");
        }
        break;

    /**
     * POST /intervention_electrique.php?action=terminer_phase
     * Termine définitivement une phase (calcul des coûts finaux)
     * Body: { "intervention_id": X, "phase": "terrassement|branchement", "notes": "..." }
     */
    case 'POST_terminer_phase':
        $data = json_decode(file_get_contents("php://input"));

        if (!$data || !isset($data->intervention_id) || !isset($data->phase)) {
            ErrorHandler::handleError(400, "Données requises: intervention_id, phase");
        }

        $intervention->id = $data->intervention_id;
        $notes = isset($data->notes) ? Validator::validateSensitiveData($data->notes, 'notes') : null;

        if($intervention->terminerPhase($data->phase, $notes)) {
            // Récupérer les statistiques mises à jour
            $stats = $intervention->getStatistiques();

            // Audit trail : fin de phase (critique)
            $current_user = $auth->getCurrentUser();
            $auditTrail->logInterventionChange(
                $data->intervention_id,
                $current_user['id'],
                "fin_phase_{$data->phase}",
                ['statut' => 'en_cours'],
                [
                    'phase' => $data->phase,
                    'statut' => 'terminee',
                    'duree_totale_heures' => $stats['duree_totale_heures'],
                    'cout_total_reel' => $stats['cout_total_reel'],
                    'notes' => $notes
                ],
                "Fin de la phase {$data->phase}" . ($notes ? " - Notes: {$notes}" : "")
            );

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => "Phase {$data->phase} terminée avec succès",
                'data' => [
                    'intervention_id' => $data->intervention_id,
                    'phase' => $data->phase,
                    'duree_totale_heures' => $stats['duree_totale_heures'],
                    'cout_total_reel' => $stats['cout_total_reel'],
                    'ecart_budget' => $stats['ecart_budget']
                ]
            ]);
        } else {
            ErrorHandler::handleServerError("Impossible de terminer la phase");
        }
        break;

    /**
     * GET /intervention_electrique.php?action=sessions&id=X
     * Récupère les sessions de travail détaillées d'une intervention
     * Paramètres optionnels: &phase=terrassement|branchement
     */
    case 'GET_sessions':
        $intervention_id_param = $intervention_id ?? $_GET['intervention_id'] ?? null;

        if (!$intervention_id_param) {
            ErrorHandler::handleError(400, "ID d'intervention requis");
        }

        try {
            // Vérifier si l'intervention existe
            $check_query = "SELECT id FROM interventions WHERE id = :intervention_id";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(":intervention_id", $intervention_id_param);
            $check_stmt->execute();

            if (!$check_stmt->fetch(PDO::FETCH_ASSOC)) {
                ErrorHandler::handleNotFoundError("Intervention non trouvée");
            }

            $intervention->id = $intervention_id_param;
            $phase_filter = $_GET['phase'] ?? null;

            // Vérifier si la table des sessions existe
            $table_exists_query = "SHOW TABLES LIKE 'intervention_sessions_travail'";
            $table_exists_stmt = $db->prepare($table_exists_query);
            $table_exists_stmt->execute();

            if (!$table_exists_stmt->fetch(PDO::FETCH_ASSOC)) {
                // Table n'existe pas, retourner un tableau vide
                $sessions = [];
            } else {
                $sessions = $intervention->getSessionsTravail($phase_filter);
            }
        } catch (Exception $e) {
            error_log("Erreur sessions: " . $e->getMessage());
            $sessions = [];
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $sessions,
            'total_sessions' => count($sessions)
        ]);
        break;

    /**
     * GET /intervention_electrique.php?action=statistiques&id=X
     * Récupère les statistiques complètes d'une intervention
     */
    case 'GET_statistiques':
        if (!$intervention_id) {
            ErrorHandler::handleError(400, "ID d'intervention requis");
        }

        $intervention->id = $intervention_id;
        $stats = $intervention->getStatistiques();

        if ($stats) {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
        } else {
            ErrorHandler::handleNotFoundError("Statistiques non trouvées");
        }
        break;

    /**
     * GET /intervention_electrique.php?action=delais&id=X
     * Calcule et récupère les délais et indicateurs de performance d'une intervention
     */
    case 'GET_delais':
        if (!$intervention_id) {
            ErrorHandler::handleError(400, "ID d'intervention requis");
        }

        $intervention->id = $intervention_id;
        $delais = $intervention->calculerDelais();

        if ($delais) {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $delais,
                'message' => 'Délais calculés avec succès'
            ]);
        } else {
            ErrorHandler::handleNotFoundError("Intervention non trouvée pour le calcul des délais");
        }
        break;

    /**
     * PUT /intervention_electrique.php
     * Mise à jour d'une intervention électrique existante
     */
    case 'PUT_':
        $data = json_decode(file_get_contents("php://input"));

        if (!$data || !isset($data->id)) {
            ErrorHandler::handleError(400, "ID d'intervention requis");
        }

        try {
            // Validation stricte des données de mise à jour
            if (isset($data->titre) || isset($data->description) || isset($data->client_nom) ||
                isset($data->type_reglementaire) || isset($data->mode_pose) ||
                isset($data->longueur_liaison_reseau) || isset($data->longueur_derivation_individuelle)) {

                $updateValidationData = [
                    'titre' => $data->titre ?? '',
                    'client_nom' => $data->client_nom ?? '',
                    'type_reglementaire' => $data->type_reglementaire ?? '',
                    'mode_pose' => $data->mode_pose ?? '',
                    'longueur_liaison_reseau' => $data->longueur_liaison_reseau ?? 0,
                    'longueur_derivation_individuelle' => $data->longueur_derivation_individuelle ?? 0,
                    'distance_raccordement' => $data->distance_raccordement ?? 0
                ];

                $updateValidationErrors = Validator::validateInterventionElectrique($updateValidationData);
                if (!empty($updateValidationErrors)) {
                    ErrorHandler::handleError(400, "Erreurs de validation: " . implode(', ', $updateValidationErrors));
                }
            }

            // Récupération et mise à jour des données
            $intervention->id = $data->id;

            if (!$intervention->readOne()) {
                ErrorHandler::handleNotFoundError("Intervention non trouvée");
            }

            // Capture des données avant modification pour audit trail
            $oldData = [
                'titre' => $intervention->titre,
                'description' => $intervention->description,
                'client_nom' => $intervention->client_nom,
                'type_reglementaire' => $intervention->type_reglementaire,
                'mode_pose' => $intervention->mode_pose,
                'longueur_liaison_reseau' => $intervention->longueur_liaison_reseau,
                'longueur_derivation_individuelle' => $intervention->longueur_derivation_individuelle,
                'distance_raccordement' => $intervention->distance_raccordement,
                'cout_total_estime' => $intervention->cout_total_estime,
                'phase_terrassement_technicien_id' => $intervention->phase_terrassement_technicien_id,
                'phase_branchement_technicien_id' => $intervention->phase_branchement_technicien_id,
                'statut' => $intervention->statut ?? 'en_cours'
            ];

            // Mise à jour des champs modifiables avec sécurisation
            if (isset($data->titre)) $intervention->titre = Validator::sanitizeString($data->titre);
            if (isset($data->description)) $intervention->description = Validator::validateSensitiveData($data->description, 'description');
            if (isset($data->priorite)) $intervention->priorite = $data->priorite;
            if (isset($data->client_id)) $intervention->client_id = $data->client_id;
            if (isset($data->client_nom)) $intervention->client_nom = Validator::sanitizeString($data->client_nom);

            // Mise à jour des nouveaux champs Enedis
            if (isset($data->type_reglementaire)) $intervention->type_reglementaire = $data->type_reglementaire;
            if (isset($data->mode_pose)) $intervention->mode_pose = $data->mode_pose;
            if (isset($data->longueur_liaison_reseau)) $intervention->longueur_liaison_reseau = $data->longueur_liaison_reseau;
            if (isset($data->longueur_derivation_individuelle)) $intervention->longueur_derivation_individuelle = $data->longueur_derivation_individuelle;
            if (isset($data->distance_raccordement)) $intervention->distance_raccordement = $data->distance_raccordement;

            // Mise à jour des dates de suivi
            if (isset($data->date_reception_dossier)) $intervention->date_reception_dossier = $data->date_reception_dossier;
            if (isset($data->date_etude_technique)) $intervention->date_etude_technique = $data->date_etude_technique;
            if (isset($data->date_validation_devis)) $intervention->date_validation_devis = $data->date_validation_devis;
            if (isset($data->date_realisation_terrassement)) $intervention->date_realisation_terrassement = $data->date_realisation_terrassement;
            if (isset($data->date_realisation_cablage)) $intervention->date_realisation_cablage = $data->date_realisation_cablage;
            if (isset($data->date_mise_en_service)) $intervention->date_mise_en_service = $data->date_mise_en_service;

            // Mise à jour des assignations techniciens avec recalcul des taux
            if (isset($data->technicien_terrassement_id)) {
                $intervention->phase_terrassement_technicien_id = $data->technicien_terrassement_id;
                // Récupérer le nouveau taux
                $taux_query = "SELECT taux_horaire FROM users WHERE id = :id";
                $taux_stmt = $db->prepare($taux_query);
                $taux_stmt->bindParam(":id", $data->technicien_terrassement_id);
                $taux_stmt->execute();
                $taux_result = $taux_stmt->fetch(PDO::FETCH_ASSOC);
                if ($taux_result) {
                    $intervention->phase_terrassement_taux_horaire = $taux_result['taux_horaire'];
                }
            }

            if (isset($data->technicien_branchement_id)) {
                $intervention->phase_branchement_technicien_id = $data->technicien_branchement_id;
                // Récupérer le nouveau taux
                $taux_query = "SELECT taux_horaire FROM users WHERE id = :id";
                $taux_stmt = $db->prepare($taux_query);
                $taux_stmt->bindParam(":id", $data->technicien_branchement_id);
                $taux_stmt->execute();
                $taux_result = $taux_stmt->fetch(PDO::FETCH_ASSOC);
                if ($taux_result) {
                    $intervention->phase_branchement_taux_horaire = $taux_result['taux_horaire'];
                }
            }

            // Mise à jour de la planification
            if (isset($data->date_terrassement_prevue)) {
                $intervention->date_terrassement_prevue = $data->date_terrassement_prevue;
            }
            if (isset($data->date_branchement_prevue)) {
                $intervention->date_branchement_prevue = $data->date_branchement_prevue;
            }

            if($intervention->update()) {
                // Capture des nouvelles données pour audit trail
                $newData = [
                    'titre' => $intervention->titre,
                    'description' => $intervention->description,
                    'client_nom' => $intervention->client_nom,
                    'type_reglementaire' => $intervention->type_reglementaire,
                    'mode_pose' => $intervention->mode_pose,
                    'longueur_liaison_reseau' => $intervention->longueur_liaison_reseau,
                    'longueur_derivation_individuelle' => $intervention->longueur_derivation_individuelle,
                    'distance_raccordement' => $intervention->distance_raccordement,
                    'cout_total_estime' => $intervention->cout_total_estime,
                    'phase_terrassement_technicien_id' => $intervention->phase_terrassement_technicien_id,
                    'phase_branchement_technicien_id' => $intervention->phase_branchement_technicien_id,
                    'statut' => $intervention->statut ?? 'en_cours'
                ];

                // Audit trail : enregistrer la modification
                $current_user = $auth->getCurrentUser();
                $auditTrail->logInterventionChange(
                    $intervention->id,
                    $current_user['id'],
                    'modification_intervention_electrique',
                    $oldData,
                    $newData,
                    "Modification de l'intervention électrique: {$intervention->titre}"
                );

                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Intervention mise à jour avec succès'
                ]);
            } else {
                ErrorHandler::handleServerError("Impossible de mettre à jour l'intervention");
            }

        } catch (Exception $e) {
            error_log("Erreur mise à jour intervention: " . $e->getMessage());
            ErrorHandler::handleServerError("Erreur lors de la mise à jour");
        }
        break;

    /**
     * GET /intervention_electrique.php?action=audit&id=X
     * Récupère l'historique d'audit d'une intervention
     */
    case 'GET_audit':
        if (!$intervention_id) {
            ErrorHandler::handleError(400, "ID d'intervention requis");
        }

        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $auditHistory = $auditTrail->getInterventionHistory($intervention_id, $limit);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $auditHistory,
            'total' => count($auditHistory),
            'intervention_id' => $intervention_id
        ]);
        break;

    /**
     * GET /intervention_electrique.php?action=audit_critical
     * Récupère les actions critiques récentes
     */
    case 'GET_audit_critical':
        $days = isset($_GET['days']) ? (int)$_GET['days'] : 7;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;

        $criticalActions = $auditTrail->getCriticalActions($days, $limit);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $criticalActions,
            'total' => count($criticalActions),
            'period_days' => $days
        ]);
        break;

    /**
     * GET /intervention_electrique.php?action=audit_stats
     * Récupère les statistiques d'audit
     */
    case 'GET_audit_stats':
        $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
        $auditStats = $auditTrail->getAuditStats($days);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $auditStats,
            'period_days' => $days
        ]);
        break;

    /**
     * GET /intervention_electrique.php?action=get_active_session&intervention_id=X
     * Vérifie s'il y a une session en cours pour une intervention
     */
    case 'GET_get_active_session':
        $intervention_id_param = $_GET['intervention_id'] ?? null;

        if (!$intervention_id_param) {
            ErrorHandler::handleError(400, "ID d'intervention requis");
        }

        try {
            // D'abord vérifier si l'intervention existe
            $check_query = "SELECT id FROM interventions WHERE id = :intervention_id";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(":intervention_id", $intervention_id_param);
            $check_stmt->execute();

            if (!$check_stmt->fetch(PDO::FETCH_ASSOC)) {
                ErrorHandler::handleNotFoundError("Intervention non trouvée");
            }

            // Vérifier si les colonnes de phases existent
            $columns_query = "SHOW COLUMNS FROM interventions LIKE 'phase_%'";
            $columns_stmt = $db->prepare($columns_query);
            $columns_stmt->execute();
            $phase_columns = $columns_stmt->fetchAll(PDO::FETCH_ASSOC);

            // Si les colonnes de phases n'existent pas encore, retourner aucune session active
            if (empty($phase_columns)) {
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'session' => null,
                    'message' => 'Aucune session active (schema non migré)'
                ]);
                return;
            }

            // Rechercher une session active dans la base de données
            $query = "SELECT
                        ie.id,
                        ie.phase_terrassement_statut,
                        ie.phase_branchement_statut,
                        ie.phase_terrassement_date_debut,
                        ie.phase_branchement_date_debut,
                        ie.phase_terrassement_technicien_id,
                        ie.phase_branchement_technicien_id
                      FROM interventions ie
                      WHERE ie.id = :intervention_id";

            $stmt = $db->prepare($query);
            $stmt->bindParam(":intervention_id", $intervention_id_param);
            $stmt->execute();
            $intervention_data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$intervention_data) {
                ErrorHandler::handleNotFoundError("Intervention non trouvée");
            }

            $activeSession = null;

            // Vérifier s'il y a une phase terrassement en cours
            if (isset($intervention_data['phase_terrassement_statut']) &&
                $intervention_data['phase_terrassement_statut'] === 'en_cours' &&
                $intervention_data['phase_terrassement_date_debut'] &&
                $intervention_data['phase_terrassement_technicien_id']) {

                $activeSession = [
                    'id' => $intervention_id_param . '_terrassement',
                    'phase' => 'terrassement',
                    'debut' => $intervention_data['phase_terrassement_date_debut'],
                    'technicien_id' => $intervention_data['phase_terrassement_technicien_id']
                ];
            }
            // Sinon vérifier s'il y a une phase branchement en cours
            elseif (isset($intervention_data['phase_branchement_statut']) &&
                    $intervention_data['phase_branchement_statut'] === 'en_cours' &&
                    $intervention_data['phase_branchement_date_debut'] &&
                    $intervention_data['phase_branchement_technicien_id']) {

                $activeSession = [
                    'id' => $intervention_id_param . '_branchement',
                    'phase' => 'branchement',
                    'debut' => $intervention_data['phase_branchement_date_debut'],
                    'technicien_id' => $intervention_data['phase_branchement_technicien_id']
                ];
            }

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'session' => $activeSession,
                'message' => $activeSession ? 'Session active trouvée' : 'Aucune session active'
            ]);

        } catch (Exception $e) {
            error_log("Erreur get_active_session: " . $e->getMessage());
            ErrorHandler::handleServerError("Erreur lors de la vérification de session active");
        }
        break;

    /**
     * Gestion des endpoints non trouvés
     */
    default:
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Endpoint non trouvé',
            'available_actions' => [
                'GET /' => 'Liste des interventions',
                'GET /?id=X' => 'Détails d\'une intervention',
                'POST /' => 'Créer une intervention',
                'PUT /' => 'Mettre à jour une intervention',
                'POST /?action=demarrer_phase' => 'Démarrer une phase',
                'POST /?action=arreter_phase' => 'Arrêter une phase',
                'POST /?action=terminer_phase' => 'Terminer une phase',
                'GET /?action=sessions&id=X' => 'Sessions de travail',
                'GET /?action=get_active_session&intervention_id=X' => 'Vérifier session active',
                'GET /?action=statistiques&id=X' => 'Statistiques',
                'GET /?action=delais&id=X' => 'Calculs des délais et indicateurs',
                'GET /?action=audit&id=X' => 'Historique d\'audit d\'une intervention',
                'GET /?action=audit_critical' => 'Actions critiques récentes',
                'GET /?action=audit_stats' => 'Statistiques d\'audit'
            ]
        ]);
        break;
}
?>