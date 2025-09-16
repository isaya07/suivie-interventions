<?php
/**
 * Modèle d'intervention électrique avec gestion des phases
 *
 * Gère les interventions de branchement électrique avec deux phases distinctes :
 * - Phase 1 : Branchement électrique (réalisé par un câbleur)
 * - Phase 2 : Terrassement (réalisé par un terrassier, optionnel)
 *
 * Fonctionnalités :
 * - Suivi du temps par phase avec chronomètre
 * - Calcul automatique des coûts selon taux horaires différenciés
 * - Gestion des statuts par phase
 * - Sessions de travail détaillées
 */

class InterventionElectrique {
    private $conn;
    private $table_name = "interventions";
    private $sessions_table = "intervention_sessions_travail";
    private $prestations_table = "types_prestations";

    // ===== PROPRIÉTÉS DE BASE =====
    public $id;
    public $numero;
    public $titre;
    public $description;
    public $client_id;
    public $client_nom;
    public $createur_id;
    public $priorite;

    // ===== TYPE DE PRESTATION =====
    public $type_prestation_id;
    public $type_prestation_nom;
    public $has_terrassement;

    // ===== PHASE BRANCHEMENT =====
    public $phase_branchement_statut;
    public $phase_branchement_technicien_id;
    public $phase_branchement_taux_horaire;
    public $phase_branchement_date_debut;
    public $phase_branchement_date_fin;
    public $phase_branchement_duree_heures;
    public $phase_branchement_cout;
    public $phase_branchement_notes;

    // ===== PHASE TERRASSEMENT =====
    public $phase_terrassement_statut;
    public $phase_terrassement_technicien_id;
    public $phase_terrassement_taux_horaire;
    public $phase_terrassement_date_debut;
    public $phase_terrassement_date_fin;
    public $phase_terrassement_duree_heures;
    public $phase_terrassement_cout;
    public $phase_terrassement_notes;

    // ===== PLANIFICATION =====
    public $date_branchement_prevue;
    public $date_terrassement_prevue;

    // ===== TOTAUX ET CALCULS =====
    public $duree_totale_heures;
    public $cout_total_reel;
    public $cout_total_estime;
    public $ecart_budget;
    public $ecart_pourcentage;

    // ===== MÉTADONNÉES =====
    public $date_creation;
    public $date_modification;

    /**
     * Constructeur - Initialise la connexion à la base de données
     * @param PDO $db Instance de connexion PDO
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Crée une nouvelle intervention électrique
     * Configure automatiquement les phases selon le type de prestation
     * @return bool True si création réussie, False sinon
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET
                    titre = :titre,
                    description = :description,
                    client_id = :client_id,
                    client_nom = :client_nom,
                    createur_id = :createur_id,
                    priorite = :priorite,
                    type_prestation_id = :type_prestation_id,

                    -- Configuration phase branchement
                    phase_branchement_statut = 'en_attente',
                    phase_branchement_technicien_id = :technicien_branchement_id,
                    phase_branchement_taux_horaire = :taux_branchement,

                    -- Configuration phase terrassement (si applicable)
                    phase_terrassement_statut = CASE WHEN :has_terrassement = 1 THEN 'en_attente' ELSE 'non_applicable' END,
                    phase_terrassement_technicien_id = :technicien_terrassement_id,
                    phase_terrassement_taux_horaire = :taux_terrassement,

                    -- Planification
                    date_branchement_prevue = :date_branchement_prevue,
                    date_terrassement_prevue = :date_terrassement_prevue,

                    -- Estimation budgétaire
                    cout_total_estime = :cout_total_estime,

                    date_creation = NOW(),
                    date_modification = NOW()";

        $stmt = $this->conn->prepare($query);

        // Sanitisation des données textuelles
        $this->titre = htmlspecialchars(strip_tags($this->titre));
        $this->description = htmlspecialchars(strip_tags($this->description ?? ''));
        $this->client_nom = htmlspecialchars(strip_tags($this->client_nom ?? ''));
        $this->priorite = htmlspecialchars(strip_tags($this->priorite ?? 'normale'));

        // Binding des paramètres
        $stmt->bindParam(":titre", $this->titre);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":client_id", $this->client_id);
        $stmt->bindParam(":client_nom", $this->client_nom);
        $stmt->bindParam(":createur_id", $this->createur_id);
        $stmt->bindParam(":priorite", $this->priorite);
        $stmt->bindParam(":type_prestation_id", $this->type_prestation_id);
        $stmt->bindParam(":technicien_branchement_id", $this->phase_branchement_technicien_id);
        $stmt->bindParam(":taux_branchement", $this->phase_branchement_taux_horaire);
        $stmt->bindParam(":has_terrassement", $this->has_terrassement);
        $stmt->bindParam(":technicien_terrassement_id", $this->phase_terrassement_technicien_id);
        $stmt->bindParam(":taux_terrassement", $this->phase_terrassement_taux_horaire);
        $stmt->bindParam(":date_branchement_prevue", $this->date_branchement_prevue);
        $stmt->bindParam(":date_terrassement_prevue", $this->date_terrassement_prevue);
        $stmt->bindParam(":cout_total_estime", $this->cout_total_estime);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    /**
     * Récupère toutes les interventions avec informations complètes
     * Utilise la vue v_interventions_completes pour optimiser les requêtes
     * @return PDOStatement Résultat de la requête
     */
    public function readAll() {
        $query = "SELECT
                    i.*,
                    tp.nom as type_prestation_nom,
                    tp.code as type_prestation_code,
                    tp.has_terrassement,

                    -- Infos technicien branchement
                    ub.nom as technicien_branchement_nom,
                    ub.prenom as technicien_branchement_prenom,
                    CONCAT(ub.prenom, ' ', ub.nom) as technicien_branchement_complet,

                    -- Infos technicien terrassement
                    ut.nom as technicien_terrassement_nom,
                    ut.prenom as technicien_terrassement_prenom,
                    CONCAT(ut.prenom, ' ', ut.nom) as technicien_terrassement_complet,

                    -- Infos client
                    c.nom as client_nom_complet,
                    c.contact_principal as client_contact,

                    -- Statut global calculé
                    CASE
                        WHEN i.phase_branchement_statut = 'terminee' AND
                             (tp.has_terrassement = 0 OR i.phase_terrassement_statut IN ('terminee', 'non_applicable'))
                        THEN 'Terminée'
                        WHEN i.phase_branchement_statut = 'en_cours' OR i.phase_terrassement_statut = 'en_cours'
                        THEN 'En cours'
                        WHEN i.phase_branchement_statut = 'annulee' OR i.phase_terrassement_statut = 'annulee'
                        THEN 'Annulée'
                        ELSE 'En attente'
                    END as statut_global

                  FROM " . $this->table_name . " i
                  LEFT JOIN " . $this->prestations_table . " tp ON i.type_prestation_id = tp.id
                  LEFT JOIN users ub ON i.phase_branchement_technicien_id = ub.id
                  LEFT JOIN users ut ON i.phase_terrassement_technicien_id = ut.id
                  LEFT JOIN clients c ON i.client_id = c.id
                  ORDER BY i.date_creation DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Récupère une intervention spécifique avec tous ses détails
     * @return bool True si intervention trouvée, False sinon
     */
    public function readOne() {
        $query = "SELECT
                    i.*,
                    tp.nom as type_prestation_nom,
                    tp.code as type_prestation_code,
                    tp.has_terrassement,
                    tp.duree_branchement_heures as duree_branchement_estimee,
                    tp.duree_terrassement_heures as duree_terrassement_estimee,

                    -- Infos technicien branchement
                    ub.nom as technicien_branchement_nom,
                    ub.prenom as technicien_branchement_prenom,
                    ub.taux_horaire as technicien_branchement_taux_defaut,

                    -- Infos technicien terrassement
                    ut.nom as technicien_terrassement_nom,
                    ut.prenom as technicien_terrassement_prenom,
                    ut.taux_horaire as technicien_terrassement_taux_defaut,

                    -- Infos client
                    c.nom as client_nom_complet,
                    c.contact_principal as client_contact,
                    c.telephone as client_telephone,
                    c.email as client_email

                  FROM " . $this->table_name . " i
                  LEFT JOIN " . $this->prestations_table . " tp ON i.type_prestation_id = tp.id
                  LEFT JOIN users ub ON i.phase_branchement_technicien_id = ub.id
                  LEFT JOIN users ut ON i.phase_terrassement_technicien_id = ut.id
                  LEFT JOIN clients c ON i.client_id = c.id
                  WHERE i.id = :id
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row) {
            // Hydratation de l'objet avec toutes les données
            foreach($row as $key => $value) {
                if(property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
            return true;
        }

        return false;
    }

    /**
     * Démarre une phase d'intervention (chronomètre)
     * Crée une nouvelle session de travail et met à jour le statut
     * @param string $phase 'branchement' ou 'terrassement'
     * @param int $technicien_id ID du technicien qui démarre
     * @return bool True si démarrage réussi, False sinon
     */
    public function demarrerPhase($phase, $technicien_id) {
        try {
            $this->conn->beginTransaction();

            // Vérifier qu'aucune session n'est déjà en cours pour cette phase
            $check_query = "SELECT COUNT(*) FROM " . $this->sessions_table . "
                           WHERE intervention_id = :intervention_id
                           AND phase = :phase
                           AND fin IS NULL";

            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->bindParam(":intervention_id", $this->id);
            $check_stmt->bindParam(":phase", $phase);
            $check_stmt->execute();

            if($check_stmt->fetchColumn() > 0) {
                throw new Exception("Une session est déjà en cours pour cette phase");
            }

            // Créer nouvelle session de travail
            $session_query = "INSERT INTO " . $this->sessions_table . " SET
                             intervention_id = :intervention_id,
                             phase = :phase,
                             technicien_id = :technicien_id,
                             debut = NOW()";

            $session_stmt = $this->conn->prepare($session_query);
            $session_stmt->bindParam(":intervention_id", $this->id);
            $session_stmt->bindParam(":phase", $phase);
            $session_stmt->bindParam(":technicien_id", $technicien_id);
            $session_stmt->execute();

            // Mettre à jour le statut de la phase
            $status_field = "phase_" . $phase . "_statut";
            $date_debut_field = "phase_" . $phase . "_date_debut";

            $update_query = "UPDATE " . $this->table_name . " SET
                           {$status_field} = 'en_cours',
                           {$date_debut_field} = COALESCE({$date_debut_field}, NOW()),
                           date_modification = NOW()
                           WHERE id = :id";

            $update_stmt = $this->conn->prepare($update_query);
            $update_stmt->bindParam(":id", $this->id);
            $update_stmt->execute();

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Erreur démarrage phase: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Arrête une phase d'intervention (pause du chronomètre)
     * Met à jour la durée de la session en cours
     * @param string $phase 'branchement' ou 'terrassement'
     * @param string $notes Notes optionnelles pour la session
     * @return bool True si arrêt réussi, False sinon
     */
    public function arreterPhase($phase, $notes = null) {
        try {
            $this->conn->beginTransaction();

            // Trouver la session en cours
            $session_query = "SELECT id, debut FROM " . $this->sessions_table . "
                             WHERE intervention_id = :intervention_id
                             AND phase = :phase
                             AND fin IS NULL
                             ORDER BY debut DESC
                             LIMIT 1";

            $session_stmt = $this->conn->prepare($session_query);
            $session_stmt->bindParam(":intervention_id", $this->id);
            $session_stmt->bindParam(":phase", $phase);
            $session_stmt->execute();

            $session = $session_stmt->fetch(PDO::FETCH_ASSOC);
            if(!$session) {
                throw new Exception("Aucune session en cours trouvée pour cette phase");
            }

            // Calculer la durée
            $debut = new DateTime($session['debut']);
            $fin = new DateTime();
            $duree_minutes = $fin->diff($debut)->h * 60 + $fin->diff($debut)->i;

            // Mettre à jour la session
            $update_session_query = "UPDATE " . $this->sessions_table . " SET
                                   fin = NOW(),
                                   duree_minutes = :duree_minutes,
                                   notes = :notes
                                   WHERE id = :session_id";

            $update_session_stmt = $this->conn->prepare($update_session_query);
            $update_session_stmt->bindParam(":duree_minutes", $duree_minutes);
            $update_session_stmt->bindParam(":notes", $notes);
            $update_session_stmt->bindParam(":session_id", $session['id']);
            $update_session_stmt->execute();

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Erreur arrêt phase: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Termine définitivement une phase d'intervention
     * Calcule les coûts finaux et met à jour les totaux
     * @param string $phase 'branchement' ou 'terrassement'
     * @param string $notes Notes finales pour la phase
     * @return bool True si finalisation réussie, False sinon
     */
    public function terminerPhase($phase, $notes = null) {
        try {
            $this->conn->beginTransaction();

            // D'abord arrêter toute session en cours
            $this->arreterPhase($phase, $notes);

            // Calculer le total des heures pour cette phase
            $total_query = "SELECT COALESCE(SUM(duree_minutes), 0) as total_minutes
                           FROM " . $this->sessions_table . "
                           WHERE intervention_id = :intervention_id
                           AND phase = :phase
                           AND duree_minutes IS NOT NULL";

            $total_stmt = $this->conn->prepare($total_query);
            $total_stmt->bindParam(":intervention_id", $this->id);
            $total_stmt->bindParam(":phase", $phase);
            $total_stmt->execute();

            $total_minutes = $total_stmt->fetchColumn();
            $total_heures = round($total_minutes / 60.0, 2);

            // Mettre à jour les données de la phase
            $status_field = "phase_" . $phase . "_statut";
            $duree_field = "phase_" . $phase . "_duree_heures";
            $date_fin_field = "phase_" . $phase . "_date_fin";
            $notes_field = "phase_" . $phase . "_notes";

            $update_query = "UPDATE " . $this->table_name . " SET
                           {$status_field} = 'terminee',
                           {$duree_field} = :duree_heures,
                           {$date_fin_field} = NOW(),
                           {$notes_field} = :notes,
                           date_modification = NOW()
                           WHERE id = :id";

            $update_stmt = $this->conn->prepare($update_query);
            $update_stmt->bindParam(":duree_heures", $total_heures);
            $update_stmt->bindParam(":notes", $notes);
            $update_stmt->bindParam(":id", $this->id);
            $update_stmt->execute();

            // Les calculs de coût sont automatiques grâce au trigger

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Erreur finalisation phase: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère les sessions de travail détaillées pour une intervention
     * @param string $phase Filtrer par phase (optionnel)
     * @return array Sessions de travail avec détails
     */
    public function getSessionsTravail($phase = null) {
        $where_clause = $phase ? "AND s.phase = :phase" : "";

        $query = "SELECT
                    s.*,
                    u.nom as technicien_nom,
                    u.prenom as technicien_prenom,
                    CONCAT(u.prenom, ' ', u.nom) as technicien_complet
                  FROM " . $this->sessions_table . " s
                  LEFT JOIN users u ON s.technicien_id = u.id
                  WHERE s.intervention_id = :intervention_id
                  {$where_clause}
                  ORDER BY s.debut ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":intervention_id", $this->id);

        if($phase) {
            $stmt->bindParam(":phase", $phase);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Met à jour une intervention existante
     * Gère les modifications de planification et d'attribution
     * @return bool True si mise à jour réussie, False sinon
     */
    public function update() {
        $query = "UPDATE " . $this->table_name . " SET
                    titre = :titre,
                    description = :description,
                    client_id = :client_id,
                    client_nom = :client_nom,
                    priorite = :priorite,
                    type_prestation_id = :type_prestation_id,

                    -- Mise à jour assignations techniciens
                    phase_branchement_technicien_id = :technicien_branchement_id,
                    phase_branchement_taux_horaire = :taux_branchement,
                    phase_terrassement_technicien_id = :technicien_terrassement_id,
                    phase_terrassement_taux_horaire = :taux_terrassement,

                    -- Mise à jour planification
                    date_branchement_prevue = :date_branchement_prevue,
                    date_terrassement_prevue = :date_terrassement_prevue,

                    -- Mise à jour estimation si fournie
                    cout_total_estime = COALESCE(:cout_total_estime, cout_total_estime),

                    date_modification = NOW()

                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Sanitisation des données
        $this->titre = htmlspecialchars(strip_tags($this->titre));
        $this->description = htmlspecialchars(strip_tags($this->description ?? ''));
        $this->client_nom = htmlspecialchars(strip_tags($this->client_nom ?? ''));
        $this->priorite = htmlspecialchars(strip_tags($this->priorite ?? 'normale'));

        // Binding des paramètres
        $stmt->bindParam(":titre", $this->titre);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":client_id", $this->client_id);
        $stmt->bindParam(":client_nom", $this->client_nom);
        $stmt->bindParam(":priorite", $this->priorite);
        $stmt->bindParam(":type_prestation_id", $this->type_prestation_id);
        $stmt->bindParam(":technicien_branchement_id", $this->phase_branchement_technicien_id);
        $stmt->bindParam(":taux_branchement", $this->phase_branchement_taux_horaire);
        $stmt->bindParam(":technicien_terrassement_id", $this->phase_terrassement_technicien_id);
        $stmt->bindParam(":taux_terrassement", $this->phase_terrassement_taux_horaire);
        $stmt->bindParam(":date_branchement_prevue", $this->date_branchement_prevue);
        $stmt->bindParam(":date_terrassement_prevue", $this->date_terrassement_prevue);
        $stmt->bindParam(":cout_total_estime", $this->cout_total_estime);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    /**
     * Supprime une intervention et toutes ses sessions de travail associées
     * @return bool True si suppression réussie, False sinon
     */
    public function delete() {
        try {
            $this->conn->beginTransaction();

            // Supprimer d'abord les sessions de travail (contrainte FK)
            $delete_sessions_query = "DELETE FROM " . $this->sessions_table . "
                                    WHERE intervention_id = :id";
            $delete_sessions_stmt = $this->conn->prepare($delete_sessions_query);
            $delete_sessions_stmt->bindParam(":id", $this->id);
            $delete_sessions_stmt->execute();

            // Puis supprimer l'intervention
            $delete_intervention_query = "DELETE FROM " . $this->table_name . "
                                        WHERE id = :id";
            $delete_intervention_stmt = $this->conn->prepare($delete_intervention_query);
            $delete_intervention_stmt->bindParam(":id", $this->id);
            $delete_intervention_stmt->execute();

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Erreur suppression intervention: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère les statistiques d'une intervention
     * @return array Statistiques complètes (durées, coûts, écarts, etc.)
     */
    public function getStatistiques() {
        $query = "SELECT
                    i.*,
                    tp.nom as type_prestation,
                    tp.duree_branchement_heures as duree_branchement_estimee,
                    tp.duree_terrassement_heures as duree_terrassement_estimee,

                    -- Calculs de performance
                    CASE
                        WHEN i.phase_branchement_duree_heures IS NOT NULL AND tp.duree_branchement_heures > 0
                        THEN ROUND((i.phase_branchement_duree_heures / tp.duree_branchement_heures - 1) * 100, 1)
                        ELSE NULL
                    END as ecart_duree_branchement_pct,

                    CASE
                        WHEN i.phase_terrassement_duree_heures IS NOT NULL AND tp.duree_terrassement_heures > 0
                        THEN ROUND((i.phase_terrassement_duree_heures / tp.duree_terrassement_heures - 1) * 100, 1)
                        ELSE NULL
                    END as ecart_duree_terrassement_pct

                  FROM " . $this->table_name . " i
                  LEFT JOIN " . $this->prestations_table . " tp ON i.type_prestation_id = tp.id
                  WHERE i.id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>