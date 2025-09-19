<?php

/**
 * Optimiseur de planning d'interventions
 *
 * Utilise des algorithmes d'optimisation pour planifier efficacement les interventions
 * en tenant compte de :
 * - La localisation et temps de trajet
 * - La durée des interventions
 * - Les délais et priorités
 * - La disponibilité des techniciens
 * - Les coûts de déplacement
 */
class PlanningOptimizer {
    private $db;
    private $parametres;

    public function __construct($database) {
        $this->db = $database;
        $this->loadParametresDefaut();
    }

    /**
     * Charge les paramètres d'optimisation par défaut
     */
    private function loadParametresDefaut() {
        $query = "SELECT * FROM parametres_optimisation WHERE is_default = 1 LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $this->parametres = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$this->parametres) {
            // Paramètres par défaut si aucun en base
            $this->parametres = [
                'poids_distance' => 0.30,
                'poids_temps' => 0.25,
                'poids_priorite' => 0.25,
                'poids_cout' => 0.20,
                'duree_max_journee' => 480,
                'temps_pause_min' => 30,
                'distance_max_trajet' => 100
            ];
        }
    }

    /**
     * Optimise le planning pour une période donnée
     *
     * @param string $dateDebut Date de début (Y-m-d)
     * @param string $dateFin Date de fin (Y-m-d)
     * @param array $options Options d'optimisation
     * @return array Planning optimisé
     */
    public function optimiserPlanning($dateDebut, $dateFin, $options = []) {
        $startTime = microtime(true);

        try {
            // 1. Récupérer les interventions à planifier
            $interventions = $this->getInterventionsAPlanifier($dateDebut, $dateFin);

            // 2. Récupérer les techniciens disponibles
            $techniciens = $this->getTechniciensDisponibles($dateDebut, $dateFin);

            // 3. Calculer la matrice des temps de trajet
            $matriceTrajet = $this->calculerMatriceTrajet($interventions);

            // 4. Appliquer l'algorithme d'optimisation
            $planningOptimal = $this->algorithmeGenetique($interventions, $techniciens, $matriceTrajet, $options);

            // 5. Calculer le score de qualité
            $score = $this->calculerScoreOptimisation($planningOptimal);

            // 6. Sauvegarder le planning
            $planningId = $this->sauvegarderPlanning($planningOptimal, $score, $startTime, $options);

            return [
                'success' => true,
                'planning_id' => $planningId,
                'planning' => $planningOptimal,
                'score' => $score,
                'temps_calcul_ms' => round((microtime(true) - $startTime) * 1000),
                'statistiques' => $this->calculerStatistiques($planningOptimal)
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'temps_calcul_ms' => round((microtime(true) - $startTime) * 1000)
            ];
        }
    }

    /**
     * Récupère les interventions à planifier pour la période
     */
    private function getInterventionsAPlanifier($dateDebut, $dateFin) {
        $query = "
            SELECT i.*, l.latitude, l.longitude, l.zone_intervention,
                   c.nom as client_nom, c.adresse, c.ville,
                   CASE
                       WHEN i.priorite = 'Urgente' THEN 4
                       WHEN i.priorite = 'Haute' THEN 3
                       WHEN i.priorite = 'Normale' THEN 2
                       ELSE 1
                   END as priorite_numerique
            FROM interventions i
            LEFT JOIN localisation l ON i.localisation_id = l.id
            LEFT JOIN clients c ON i.client_id = c.id
            WHERE i.statut IN ('En attente', 'En cours')
            AND (i.date_intervention BETWEEN :date_debut AND :date_fin
                 OR i.date_intervention IS NULL)
            AND i.localisation_id IS NOT NULL
            ORDER BY priorite_numerique DESC, i.date_limite ASC
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':date_debut', $dateDebut);
        $stmt->bindParam(':date_fin', $dateFin);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les techniciens disponibles avec leurs zones
     */
    private function getTechniciensDisponibles($dateDebut, $dateFin) {
        $query = "
            SELECT DISTINCT u.id, u.nom, u.prenom, u.specialite,
                   tz.zone_intervention, tz.rayon_action, tz.cout_deplacement_km,
                   d.date_debut, d.date_fin, d.type_creneau
            FROM users u
            INNER JOIN technicien_zones tz ON u.id = tz.technicien_id
            LEFT JOIN disponibilites_technicien d ON u.id = d.technicien_id
                AND d.date_debut >= :date_debut AND d.date_fin <= :date_fin
            WHERE u.role IN ('technicien', 'manager')
            AND u.is_active = 1
            AND tz.is_active = 1
            ORDER BY u.id, d.date_debut
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':date_debut', $dateDebut);
        $stmt->bindParam(':date_fin', $dateFin);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Regrouper par technicien
        $techniciens = [];
        foreach ($results as $row) {
            $id = $row['id'];
            if (!isset($techniciens[$id])) {
                $techniciens[$id] = [
                    'id' => $id,
                    'nom' => $row['nom'],
                    'prenom' => $row['prenom'],
                    'specialite' => $row['specialite'],
                    'zones' => [],
                    'disponibilites' => []
                ];
            }

            // Ajouter la zone si pas déjà présente
            if (!in_array($row['zone_intervention'], array_column($techniciens[$id]['zones'], 'zone'))) {
                $techniciens[$id]['zones'][] = [
                    'zone' => $row['zone_intervention'],
                    'rayon_action' => $row['rayon_action'],
                    'cout_deplacement_km' => $row['cout_deplacement_km']
                ];
            }

            // Ajouter les disponibilités
            if ($row['date_debut']) {
                $techniciens[$id]['disponibilites'][] = [
                    'debut' => $row['date_debut'],
                    'fin' => $row['date_fin'],
                    'type' => $row['type_creneau']
                ];
            }
        }

        return array_values($techniciens);
    }

    /**
     * Calcule la matrice des temps de trajet entre toutes les interventions
     */
    private function calculerMatriceTrajet($interventions) {
        $matrice = [];
        $count = count($interventions);

        for ($i = 0; $i < $count; $i++) {
            for ($j = 0; $j < $count; $j++) {
                if ($i === $j) {
                    $matrice[$i][$j] = 0;
                    continue;
                }

                $intervention1 = $interventions[$i];
                $intervention2 = $interventions[$j];

                // Vérifier si on a déjà calculé ce trajet
                $tempsTrajet = $this->getTempsTrajetCache(
                    $intervention1['localisation_id'],
                    $intervention2['localisation_id']
                );

                if ($tempsTrajet === null) {
                    // Calculer le temps de trajet
                    $tempsTrajet = $this->calculerTempsTrajet(
                        $intervention1['latitude'], $intervention1['longitude'],
                        $intervention2['latitude'], $intervention2['longitude']
                    );

                    // Mettre en cache
                    $this->sauvegarderTempsTrajet(
                        $intervention1['localisation_id'],
                        $intervention2['localisation_id'],
                        $tempsTrajet
                    );
                }

                $matrice[$i][$j] = $tempsTrajet;
            }
        }

        return $matrice;
    }

    /**
     * Algorithme génétique d'optimisation
     */
    private function algorithmeGenetique($interventions, $techniciens, $matriceTrajet, $options) {
        $populationSize = $options['population_size'] ?? 50;
        $generations = $options['generations'] ?? 100;
        $mutationRate = $options['mutation_rate'] ?? 0.1;

        // Population initiale
        $population = $this->genererPopulationInitiale($interventions, $techniciens, $populationSize);

        for ($gen = 0; $gen < $generations; $gen++) {
            // Évaluer la fitness de chaque individu
            $fitness = [];
            foreach ($population as $individu) {
                $fitness[] = $this->evaluerFitness($individu, $matriceTrajet);
            }

            // Sélection, croisement et mutation
            $nouvellePopulation = [];

            // Garder les meilleurs (élitisme)
            $indices = array_keys($fitness);
            usort($indices, function($a, $b) use ($fitness) {
                return $fitness[$b] <=> $fitness[$a]; // Tri décroissant
            });

            $eliteSize = max(1, intval($populationSize * 0.1));
            for ($i = 0; $i < $eliteSize; $i++) {
                $nouvellePopulation[] = $population[$indices[$i]];
            }

            // Générer le reste par croisement et mutation
            while (count($nouvellePopulation) < $populationSize) {
                $parent1 = $this->selectionTournoi($population, $fitness);
                $parent2 = $this->selectionTournoi($population, $fitness);

                $enfant = $this->croisement($parent1, $parent2);

                if (random_int(0, 100) / 100 < $mutationRate) {
                    $enfant = $this->mutation($enfant, $techniciens);
                }

                $nouvellePopulation[] = $enfant;
            }

            $population = $nouvellePopulation;
        }

        // Retourner le meilleur individu
        $fitness = [];
        foreach ($population as $individu) {
            $fitness[] = $this->evaluerFitness($individu, $matriceTrajet);
        }

        $bestIndex = array_keys($fitness, max($fitness))[0];
        return $this->convertirEnPlanning($population[$bestIndex], $interventions, $techniciens);
    }

    /**
     * Génère une population initiale aléatoire
     */
    private function genererPopulationInitiale($interventions, $techniciens, $taille) {
        $population = [];

        for ($i = 0; $i < $taille; $i++) {
            $individu = [];

            // Assigner chaque intervention à un technicien et un créneau
            foreach ($interventions as $index => $intervention) {
                // Choisir un technicien compatible (même zone si possible)
                $techniciensCompatibles = array_filter($techniciens, function($tech) use ($intervention) {
                    return in_array($intervention['zone_intervention'], array_column($tech['zones'], 'zone'));
                });

                if (empty($techniciensCompatibles)) {
                    $techniciensCompatibles = $techniciens; // Fallback
                }

                $technicien = $techniciensCompatibles[array_rand($techniciensCompatibles)];

                // Assigner un créneau horaire aléatoire dans la journée
                $heureDebut = random_int(8, 16); // Entre 8h et 16h
                $minuteDebut = random_int(0, 3) * 15; // Par quart d'heure

                $individu[] = [
                    'intervention_index' => $index,
                    'technicien_id' => $technicien['id'],
                    'heure_debut' => $heureDebut,
                    'minute_debut' => $minuteDebut
                ];
            }

            $population[] = $individu;
        }

        return $population;
    }

    /**
     * Évalue la fitness d'un individu (planning)
     */
    private function evaluerFitness($individu, $matriceTrajet) {
        $score = 0;
        $penalites = 0;

        // Regrouper par technicien
        $planningParTechnicien = [];
        foreach ($individu as $assignation) {
            $techId = $assignation['technicien_id'];
            if (!isset($planningParTechnicien[$techId])) {
                $planningParTechnicien[$techId] = [];
            }
            $planningParTechnicien[$techId][] = $assignation;
        }

        // Évaluer chaque technicien
        foreach ($planningParTechnicien as $techId => $assignations) {
            // Trier par heure de début
            usort($assignations, function($a, $b) {
                return ($a['heure_debut'] * 60 + $a['minute_debut']) <=>
                       ($b['heure_debut'] * 60 + $b['minute_debut']);
            });

            $tempsTotal = 0;
            $distanceTotal = 0;

            for ($i = 0; $i < count($assignations); $i++) {
                $current = $assignations[$i];

                // Temps de l'intervention
                $dureeIntervention = $this->getDureeIntervention($current['intervention_index']);
                $tempsTotal += $dureeIntervention;

                // Temps de trajet vers la prochaine intervention
                if ($i < count($assignations) - 1) {
                    $next = $assignations[$i + 1];
                    $tempsTrajet = $matriceTrajet[$current['intervention_index']][$next['intervention_index']];
                    $tempsTotal += $tempsTrajet['temps'];
                    $distanceTotal += $tempsTrajet['distance'];
                }

                // Bonus pour respecter les priorités
                $priorite = $this->getPrioriteIntervention($current['intervention_index']);
                $score += $priorite * $this->parametres['poids_priorite'] * 100;
            }

            // Pénalité si dépasse la durée max de journée
            if ($tempsTotal > $this->parametres['duree_max_journee']) {
                $penalites += ($tempsTotal - $this->parametres['duree_max_journee']) * 10;
            }

            // Bonus pour minimiser les déplacements
            $score += (1000 - $distanceTotal) * $this->parametres['poids_distance'];

            // Bonus pour minimiser le temps total
            $score += (1000 - $tempsTotal) * $this->parametres['poids_temps'];
        }

        return max(0, $score - $penalites);
    }

    /**
     * Sélection par tournoi
     */
    private function selectionTournoi($population, $fitness, $taileTournoi = 3) {
        $participants = [];
        for ($i = 0; $i < $taileTournoi; $i++) {
            $index = random_int(0, count($population) - 1);
            $participants[] = ['index' => $index, 'fitness' => $fitness[$index]];
        }

        usort($participants, function($a, $b) {
            return $b['fitness'] <=> $a['fitness'];
        });

        return $population[$participants[0]['index']];
    }

    /**
     * Croisement de deux parents
     */
    private function croisement($parent1, $parent2) {
        $taille = count($parent1);
        $pointCroisement = random_int(1, $taille - 1);

        $enfant = array_slice($parent1, 0, $pointCroisement);
        $enfant = array_merge($enfant, array_slice($parent2, $pointCroisement));

        return $enfant;
    }

    /**
     * Mutation d'un individu
     */
    private function mutation($individu, $techniciens) {
        $index = random_int(0, count($individu) - 1);

        // Changer aléatoirement le technicien ou l'heure
        if (random_int(0, 1)) {
            // Changer le technicien
            $individu[$index]['technicien_id'] = $techniciens[array_rand($techniciens)]['id'];
        } else {
            // Changer l'heure
            $individu[$index]['heure_debut'] = random_int(8, 16);
            $individu[$index]['minute_debut'] = random_int(0, 3) * 15;
        }

        return $individu;
    }

    /**
     * Convertit un individu en planning lisible
     */
    private function convertirEnPlanning($individu, $interventions, $techniciens) {
        $planning = [];

        foreach ($individu as $assignation) {
            $intervention = $interventions[$assignation['intervention_index']];
            $technicien = array_filter($techniciens, function($t) use ($assignation) {
                return $t['id'] == $assignation['technicien_id'];
            });
            $technicien = array_values($technicien)[0];

            $planning[] = [
                'intervention' => $intervention,
                'technicien' => $technicien,
                'heure_debut' => sprintf('%02d:%02d', $assignation['heure_debut'], $assignation['minute_debut']),
                'heure_fin' => $this->calculerHeureFin($assignation, $intervention)
            ];
        }

        return $planning;
    }

    /**
     * Calcule le temps de trajet entre deux points géographiques
     */
    private function calculerTempsTrajet($lat1, $lon1, $lat2, $lon2) {
        // Formule de Haversine pour calculer la distance
        $earthRadius = 6371; // Rayon de la Terre en km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * asin(sqrt($a));
        $distance = $earthRadius * $c;

        // Estimation du temps (vitesse moyenne de 40 km/h en ville, 80 km/h sur route)
        $vitesseMoyenne = $distance > 20 ? 80 : 40;
        $tempsTrajetMinutes = ($distance / $vitesseMoyenne) * 60;

        return [
            'distance' => round($distance, 2),
            'temps' => round($tempsTrajetMinutes),
            'cout' => round($distance * $this->parametres['poids_cout'], 2)
        ];
    }

    /**
     * Récupère le temps de trajet depuis le cache
     */
    private function getTempsTrajetCache($localisation1, $localisation2) {
        $query = "
            SELECT distance_km, temps_trajet_minutes, cout_trajet
            FROM temps_trajet
            WHERE (localisation_depart_id = :loc1 AND localisation_arrivee_id = :loc2)
               OR (localisation_depart_id = :loc2 AND localisation_arrivee_id = :loc1)
            AND is_valid = 1
            AND date_calcul > DATE_SUB(NOW(), INTERVAL 30 DAY)
            LIMIT 1
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':loc1', $localisation1);
        $stmt->bindParam(':loc2', $localisation2);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            return [
                'distance' => $result['distance_km'],
                'temps' => $result['temps_trajet_minutes'],
                'cout' => $result['cout_trajet']
            ];
        }

        return null;
    }

    /**
     * Sauvegarde le temps de trajet en cache
     */
    private function sauvegarderTempsTrajet($localisation1, $localisation2, $tempsTrajet) {
        $query = "
            INSERT INTO temps_trajet (localisation_depart_id, localisation_arrivee_id, distance_km, temps_trajet_minutes, cout_trajet)
            VALUES (:loc1, :loc2, :distance, :temps, :cout)
            ON DUPLICATE KEY UPDATE
            distance_km = VALUES(distance_km),
            temps_trajet_minutes = VALUES(temps_trajet_minutes),
            cout_trajet = VALUES(cout_trajet),
            date_calcul = CURRENT_TIMESTAMP
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':loc1', $localisation1);
        $stmt->bindParam(':loc2', $localisation2);
        $stmt->bindParam(':distance', $tempsTrajet['distance']);
        $stmt->bindParam(':temps', $tempsTrajet['temps']);
        $stmt->bindParam(':cout', $tempsTrajet['cout']);
        $stmt->execute();
    }

    /**
     * Calcule le score d'optimisation du planning
     */
    private function calculerScoreOptimisation($planning) {
        // Score basé sur différents critères
        $scoreTotal = 0;
        $nombreInterventions = count($planning);

        if ($nombreInterventions === 0) return 0;

        // Analyser la répartition des charges
        $chargeParTechnicien = [];
        $distanceTotale = 0;
        $tempsTotalTrajet = 0;

        foreach ($planning as $creneau) {
            $techId = $creneau['technicien']['id'];
            if (!isset($chargeParTechnicien[$techId])) {
                $chargeParTechnicien[$techId] = 0;
            }
            $chargeParTechnicien[$techId]++;
        }

        // Score d'équilibrage des charges (plus c'est équilibré, mieux c'est)
        $moyenne = $nombreInterventions / count($chargeParTechnicien);
        $variance = 0;
        foreach ($chargeParTechnicien as $charge) {
            $variance += pow($charge - $moyenne, 2);
        }
        $variance /= count($chargeParTechnicien);
        $scoreEquilibrage = max(0, 10 - $variance); // Score de 0 à 10

        $scoreTotal = $scoreEquilibrage * 0.4; // 40% du score

        return round($scoreTotal, 4);
    }

    /**
     * Sauvegarde le planning optimisé en base
     */
    private function sauvegarderPlanning($planning, $score, $startTime, $options) {
        $tempsCalcul = round((microtime(true) - $startTime) * 1000);

        // Insérer le planning principal
        $query = "
            INSERT INTO planning_optimise
            (nom_planning, date_debut, date_fin, algorithme_utilise, score_optimisation, temps_calcul_ms, parametres_optimisation, created_by)
            VALUES (:nom, :debut, :fin, :algo, :score, :temps, :params, :user)
        ";

        $stmt = $this->db->prepare($query);
        $nom = 'Planning optimisé ' . date('Y-m-d H:i:s');
        $debut = date('Y-m-d');
        $fin = date('Y-m-d', strtotime('+7 days'));
        $algo = 'genetic_algorithm';
        $params = json_encode($options);
        $userId = $_SESSION['user_id'] ?? 1;

        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':debut', $debut);
        $stmt->bindParam(':fin', $fin);
        $stmt->bindParam(':algo', $algo);
        $stmt->bindParam(':score', $score);
        $stmt->bindParam(':temps', $tempsCalcul);
        $stmt->bindParam(':params', $params);
        $stmt->bindParam(':user', $userId);
        $stmt->execute();

        $planningId = $this->db->lastInsertId();

        // Insérer les créneaux
        $queryCreneaux = "
            INSERT INTO planning_creneaux
            (planning_id, intervention_id, technicien_id, date_debut, date_fin, ordre_sequence)
            VALUES (:planning, :intervention, :technicien, :debut, :fin, :ordre)
        ";

        $stmtCreneaux = $this->db->prepare($queryCreneaux);

        foreach ($planning as $index => $creneau) {
            $stmtCreneaux->bindParam(':planning', $planningId);
            $stmtCreneaux->bindParam(':intervention', $creneau['intervention']['id']);
            $stmtCreneaux->bindParam(':technicien', $creneau['technicien']['id']);
            $stmtCreneaux->bindParam(':debut', $debut . ' ' . $creneau['heure_debut']);
            $stmtCreneaux->bindParam(':fin', $debut . ' ' . $creneau['heure_fin']);
            $stmtCreneaux->bindParam(':ordre', $index);
            $stmtCreneaux->execute();
        }

        return $planningId;
    }

    /**
     * Calcule les statistiques du planning
     */
    private function calculerStatistiques($planning) {
        $stats = [
            'nombre_interventions' => count($planning),
            'nombre_techniciens' => count(array_unique(array_column($planning, 'technicien'))),
            'duree_totale_estimee' => 0,
            'distance_totale_estimee' => 0,
            'cout_total_estime' => 0
        ];

        foreach ($planning as $creneau) {
            $stats['duree_totale_estimee'] += $creneau['intervention']['duree_estimee_minutes'] ?? 120;
        }

        return $stats;
    }

    // Méthodes utilitaires
    private function getDureeIntervention($index) {
        // À implémenter selon vos données
        return 120; // 2 heures par défaut
    }

    private function getPrioriteIntervention($index) {
        // À implémenter selon vos données
        return 1; // Priorité normale par défaut
    }

    private function calculerHeureFin($assignation, $intervention) {
        $heureDebut = $assignation['heure_debut'] * 60 + $assignation['minute_debut'];
        $duree = $intervention['duree_estimee_minutes'] ?? 120;
        $heureFin = $heureDebut + $duree;

        return sprintf('%02d:%02d', floor($heureFin / 60), $heureFin % 60);
    }
}
?>