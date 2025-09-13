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
}
?>