<?php
// utils/Validator.php
class Validator {

    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function validateRequired($value) {
        return !empty(trim($value));
    }

    public static function validateLength($value, $min = 0, $max = null) {
        $length = strlen(trim($value));
        if ($length < $min) return false;
        if ($max !== null && $length > $max) return false;
        return true;
    }

    public static function validateEnum($value, $allowedValues) {
        return in_array($value, $allowedValues);
    }

    public static function validateInteger($value, $min = null, $max = null) {
        if (!filter_var($value, FILTER_VALIDATE_INT)) return false;
        if ($min !== null && $value < $min) return false;
        if ($max !== null && $value > $max) return false;
        return true;
    }

    public static function validateFloat($value, $min = null, $max = null) {
        if (!filter_var($value, FILTER_VALIDATE_FLOAT)) return false;
        if ($min !== null && $value < $min) return false;
        if ($max !== null && $value > $max) return false;
        return true;
    }

    public static function sanitizeString($value) {
        return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
    }

    public static function validateInterventionData($data) {
        $errors = [];

        if (!self::validateRequired($data->titre ?? '')) {
            $errors[] = "Le titre est obligatoire";
        } elseif (!self::validateLength($data->titre, 3, 255)) {
            $errors[] = "Le titre doit contenir entre 3 et 255 caractères";
        }

        if (isset($data->description) && !self::validateLength($data->description, 0, 2000)) {
            $errors[] = "La description ne peut pas dépasser 2000 caractères";
        }

        if (isset($data->statut) && !self::validateEnum($data->statut, ['En attente', 'En cours', 'En pause', 'Terminée', 'Annulée'])) {
            $errors[] = "Statut invalide";
        }

        if (isset($data->priorite) && !self::validateEnum($data->priorite, ['Basse', 'Normale', 'Haute', 'Urgente'])) {
            $errors[] = "Priorité invalide";
        }

        if (isset($data->type_intervention) && !self::validateEnum($data->type_intervention, ['Maintenance', 'Réparation', 'Installation', 'Diagnostic', 'Autre'])) {
            $errors[] = "Type d'intervention invalide";
        }

        return $errors;
    }

    public static function validateUserData($data) {
        $errors = [];

        if (!self::validateRequired($data->username ?? '')) {
            $errors[] = "Le nom d'utilisateur est obligatoire";
        } elseif (!self::validateLength($data->username, 3, 50)) {
            $errors[] = "Le nom d'utilisateur doit contenir entre 3 et 50 caractères";
        }

        if (!self::validateRequired($data->email ?? '')) {
            $errors[] = "L'email est obligatoire";
        } elseif (!self::validateEmail($data->email)) {
            $errors[] = "Format d'email invalide";
        }

        if (isset($data->role) && !self::validateEnum($data->role, ['admin', 'technicien', 'manager', 'client'])) {
            $errors[] = "Rôle invalide";
        }

        return $errors;
    }

    /**
     * Validation stricte des données métier Enedis
     */
    public static function validateInterventionElectrique($data) {
        $errors = [];

        // Validations de base
        if (!self::validateRequired($data['titre'] ?? '')) {
            $errors[] = "Le titre est obligatoire";
        }

        if (!self::validateRequired($data['client_nom'] ?? '')) {
            $errors[] = "Le nom du client est obligatoire";
        }

        if (!isset($data['type_prestation_id']) || !self::validateInteger($data['type_prestation_id'], 1)) {
            $errors[] = "Type de prestation invalide";
        }

        // Validations spécifiques Enedis
        $errors = array_merge($errors, self::validateEnedisSpecs($data));
        $errors = array_merge($errors, self::validateDistances($data));
        $errors = array_merge($errors, self::validatePhases($data));
        $errors = array_merge($errors, self::validateDatesProcessus($data));
        $errors = array_merge($errors, self::validateFinancial($data));

        return $errors;
    }

    private static function validateEnedisSpecs($data) {
        $errors = [];

        // Type réglementaire vs DI
        if (isset($data['type_reglementaire']) && isset($data['longueur_derivation_individuelle'])) {
            $di = (float) $data['longueur_derivation_individuelle'];
            $type = $data['type_reglementaire'];

            if ($type === 'type_1' && $di > 30) {
                $errors[] = "Incohérence : Type 1 avec DI = {$di}m > 30m. Devrait être Type 2.";
            }

            if ($type === 'type_2' && $di <= 30) {
                $errors[] = "Incohérence : Type 2 avec DI = {$di}m ≤ 30m. Devrait être Type 1.";
            }
        }

        // Mode de pose valide
        if (isset($data['mode_pose'])) {
            $modesValides = ['aerien', 'souterrain', 'aerosouterrain', 'souterrain_boite', 'di_seule'];
            if (!in_array($data['mode_pose'], $modesValides)) {
                $errors[] = "Mode de pose non valide : " . $data['mode_pose'];
            }
        }

        // Limites de puissance par type
        if (isset($data['type_reglementaire']) && isset($data['puissance_souscrite'])) {
            $puissance = (float) $data['puissance_souscrite'];
            $type = $data['type_reglementaire'];

            if ($type === 'type_1' && $puissance > 36) {
                $errors[] = 'Type 1 limité à 36 kVA maximum.';
            }

            if ($type === 'type_2' && $puissance > 250) {
                $errors[] = 'Type 2 limité à 250 kVA maximum.';
            }
        }

        return $errors;
    }

    private static function validateDistances($data) {
        $errors = [];

        $lr = isset($data['longueur_liaison_reseau']) ? (float) $data['longueur_liaison_reseau'] : 0;
        $di = isset($data['longueur_derivation_individuelle']) ? (float) $data['longueur_derivation_individuelle'] : 0;
        $total = isset($data['distance_raccordement']) ? (float) $data['distance_raccordement'] : 0;

        // Distances positives
        if ($lr < 0) {
            $errors[] = 'La longueur de la liaison réseau ne peut pas être négative.';
        }

        if ($di < 0) {
            $errors[] = 'La longueur de la dérivation individuelle ne peut pas être négative.';
        }

        // Cohérence entre distances
        if ($lr > 0 && $di > 0 && $total > 0) {
            $calculated = $lr + $di;
            $tolerance = max(1, $total * 0.1); // 10% de tolérance

            if (abs($calculated - $total) > $tolerance) {
                $errors[] = "Distance totale incohérente : LR({$lr}m) + DI({$di}m) = {$calculated}m ≠ Total({$total}m)";
            }
        }

        // Limites techniques Enedis
        if ($lr > 1000) {
            $errors[] = 'Liaison réseau > 1000m : validation technique requise.';
        }

        if ($di > 100) {
            $errors[] = 'Dérivation individuelle > 100m : cas exceptionnel.';
        }

        return $errors;
    }

    private static function validatePhases($data) {
        $errors = [];

        $hasTerrass = $data['has_terrassement'] ?? false;
        $modePosse = $data['mode_pose'] ?? null;

        // Cohérence terrassement/mode
        $modesSansTerrass = ['aerien', 'di_seule'];

        if ($hasTerrass && in_array($modePosse, $modesSansTerrass)) {
            $errors[] = "Incohérence : terrassement prévu pour mode '{$modePosse}'";
        }

        if (!$hasTerrass && !in_array($modePosse, $modesSansTerrass) && $modePosse !== null) {
            $errors[] = "Terrassement requis pour mode '{$modePosse}'";
        }

        // Validation des statuts
        foreach (['terrassement', 'branchement'] as $phase) {
            $statut = $data["phase_{$phase}_statut"] ?? null;
            $dateDebut = $data["phase_{$phase}_date_debut"] ?? null;
            $dateFin = $data["phase_{$phase}_date_fin"] ?? null;
            $technicienId = $data["phase_{$phase}_technicien_id"] ?? null;

            if ($statut && !in_array($statut, ['en_attente', 'en_cours', 'terminee', 'annulee'])) {
                $errors[] = "Statut de phase {$phase} invalide : {$statut}";
            }

            if ($statut === 'en_cours' && !$technicienId) {
                $errors[] = "Technicien requis pour phase {$phase} en cours";
            }

            if ($statut === 'terminee' && (!$dateDebut || !$dateFin)) {
                $errors[] = "Dates début et fin requises pour phase {$phase} terminée";
            }

            if ($dateDebut && $dateFin && strtotime($dateFin) <= strtotime($dateDebut)) {
                $errors[] = "Date de fin doit être postérieure à la date de début pour phase {$phase}";
            }
        }

        return $errors;
    }

    private static function validateDatesProcessus($data) {
        $errors = [];

        $datesProcessus = [
            'date_reception_dossier',
            'date_etude_technique',
            'date_validation_devis',
            'date_realisation_terrassement',
            'date_realisation_cablage',
            'date_mise_en_service'
        ];

        $datesPrecedentes = null;

        foreach ($datesProcessus as $dateField) {
            $dateActuelle = $data[$dateField] ?? null;

            if ($dateActuelle) {
                $timestamp = strtotime($dateActuelle);

                // Pas dans le futur (sauf planification)
                if (!in_array($dateField, ['date_intervention']) && $timestamp > time()) {
                    $errors[] = "Date dans le futur non autorisée pour : " . str_replace('_', ' ', $dateField);
                }

                // Ordre chronologique
                if ($datesPrecedentes && $timestamp < $datesPrecedentes) {
                    $errors[] = "Dates du processus non chronologiques";
                }

                $datesPrecedentes = $timestamp;
            }
        }

        // Délais maximums Enedis
        $dateReception = $data['date_reception_dossier'] ?? null;
        $dateMiseEnService = $data['date_mise_en_service'] ?? null;
        $type = $data['type_reglementaire'] ?? null;

        if ($dateReception && $dateMiseEnService && $type) {
            $delai = (strtotime($dateMiseEnService) - strtotime($dateReception)) / (24 * 3600);
            $delaiMax = ($type === 'type_1') ? 21 : 30; // jours

            if ($delai > $delaiMax) {
                $errors[] = "Délai Enedis dépassé : {$delai}j > {$delaiMax}j pour {$type}";
            }
        }

        return $errors;
    }

    private static function validateFinancial($data) {
        $errors = [];

        $coutEstime = isset($data['cout_total_estime']) ? (float) $data['cout_total_estime'] : 0;
        $coutReel = isset($data['cout_total_reel']) ? (float) $data['cout_total_reel'] : 0;
        $tauxHoraire = isset($data['taux_horaire']) ? (float) $data['taux_horaire'] : 0;

        // Montants positifs
        if ($coutEstime < 0) {
            $errors[] = 'Coût estimé ne peut pas être négatif';
        }

        if ($coutReel < 0) {
            $errors[] = 'Coût réel ne peut pas être négatif';
        }

        if ($tauxHoraire < 0) {
            $errors[] = 'Taux horaire ne peut pas être négatif';
        }

        // Limites raisonnables
        if ($coutEstime > 50000) {
            $errors[] = 'Coût estimé exceptionnellement élevé (> 50k€)';
        }

        if ($tauxHoraire > 200) {
            $errors[] = 'Taux horaire exceptionnellement élevé (> 200€/h)';
        }

        // Écart coût estimé/réel
        if ($coutEstime > 0 && $coutReel > 0) {
            $ecart = abs($coutReel - $coutEstime) / $coutEstime * 100;
            if ($ecart > 50) {
                $errors[] = "Écart important coût estimé/réel : {$ecart}%";
            }
        }

        return $errors;
    }

    /**
     * Validation des données sensibles
     */
    public static function validateSensitiveData($data, $fieldName) {
        $sanitized = self::sanitizeString($data);

        // Détection de tentatives d'injection
        $dangerousPatterns = [
            '/script/i',
            '/javascript/i',
            '/onload/i',
            '/onerror/i',
            '/eval\(/i',
            '/union.*select/i',
            '/drop.*table/i',
            '/insert.*into/i',
            '/update.*set/i',
            '/delete.*from/i'
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $sanitized)) {
                throw new InvalidArgumentException("Données potentiellement dangereuses détectées dans {$fieldName}");
            }
        }

        return $sanitized;
    }

    /**
     * Validation d'un numéro de téléphone français
     */
    public static function validatePhoneNumber($phone) {
        $pattern = '/^(?:(?:\+33|0)[1-9](?:[0-9]{8}))$/';
        $cleaned = preg_replace('/[\s\-\.]/', '', $phone);
        return preg_match($pattern, $cleaned);
    }

    /**
     * Validation d'un SIRET
     */
    public static function validateSiret($siret) {
        $siret = preg_replace('/[\s\-]/', '', $siret);
        if (strlen($siret) !== 14 || !ctype_digit($siret)) {
            return false;
        }

        // Algorithme de Luhn pour SIRET
        $sum = 0;
        for ($i = 0; $i < 14; $i++) {
            $digit = (int) $siret[$i];
            if ($i % 2 === 1) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit = $digit % 10 + 1;
                }
            }
            $sum += $digit;
        }

        return $sum % 10 === 0;
    }
}
?>