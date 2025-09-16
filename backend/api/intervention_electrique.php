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

// Initialisation des services
$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    ErrorHandler::handleServerError("Erreur de connexion à la base de données");
}

$auth = new Auth($db);
$intervention = new InterventionElectrique($db);

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
                    'date_terrassement_prevue' => $row['date_terrassement_prevue'],
                    'date_branchement_prevue' => $row['date_branchement_prevue'],

                    // Totaux
                    'duree_totale_heures' => $row['duree_totale_heures'],
                    'cout_total_reel' => $row['cout_total_reel'],
                    'cout_total_estime' => $row['cout_total_estime'],

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

        // Validation des données requises
        if (empty($data->titre) || empty($data->type_prestation_id)) {
            ErrorHandler::handleError(400, "Titre et type de prestation requis");
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

            // Configuration de l'intervention
            $intervention->titre = $data->titre;
            $intervention->description = $data->description ?? '';
            $intervention->client_id = $data->client_id ?? null;
            $intervention->client_nom = $data->client_nom ?? '';
            $intervention->createur_id = $current_user['id'];
            $intervention->priorite = $data->priorite ?? 'normale';
            $intervention->type_prestation_id = $data->type_prestation_id;
            $intervention->has_terrassement = $prestation['has_terrassement'];

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
        $notes = $data->notes ?? null;

        if($intervention->arreterPhase($data->phase, $notes)) {
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
        $notes = $data->notes ?? null;

        if($intervention->terminerPhase($data->phase, $notes)) {
            // Récupérer les statistiques mises à jour
            $stats = $intervention->getStatistiques();

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
        if (!$intervention_id) {
            ErrorHandler::handleError(400, "ID d'intervention requis");
        }

        $intervention->id = $intervention_id;
        $phase_filter = $_GET['phase'] ?? null;

        $sessions = $intervention->getSessionsTravail($phase_filter);

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
     * PUT /intervention_electrique.php
     * Mise à jour d'une intervention électrique existante
     */
    case 'PUT_':
        $data = json_decode(file_get_contents("php://input"));

        if (!$data || !isset($data->id)) {
            ErrorHandler::handleError(400, "ID d'intervention requis");
        }

        try {
            // Récupération et mise à jour des données
            $intervention->id = $data->id;

            if (!$intervention->readOne()) {
                ErrorHandler::handleNotFoundError("Intervention non trouvée");
            }

            // Mise à jour des champs modifiables
            if (isset($data->titre)) $intervention->titre = $data->titre;
            if (isset($data->description)) $intervention->description = $data->description;
            if (isset($data->priorite)) $intervention->priorite = $data->priorite;
            if (isset($data->client_id)) $intervention->client_id = $data->client_id;
            if (isset($data->client_nom)) $intervention->client_nom = $data->client_nom;

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
                'GET /?action=statistiques&id=X' => 'Statistiques'
            ]
        ]);
        break;
}
?>