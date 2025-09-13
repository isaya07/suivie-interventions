<?php
// utils/ErrorHandler.php
class ErrorHandler {

    public static function handleError($code, $message, $data = null) {
        http_response_code($code);

        $response = [
            'success' => false,
            'message' => $message,
            'code' => $code,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        echo json_encode($response);
        exit;
    }

    public static function handleSuccess($message, $data = null, $code = 200) {
        http_response_code($code);

        $response = [
            'success' => true,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        echo json_encode($response);
    }

    public static function handleValidationErrors($errors) {
        self::handleError(400, 'Erreurs de validation', ['errors' => $errors]);
    }

    public static function handleAuthError($message = 'Authentification requise') {
        self::handleError(401, $message);
    }

    public static function handleForbiddenError($message = 'Accès interdit') {
        self::handleError(403, $message);
    }

    public static function handleNotFoundError($message = 'Ressource non trouvée') {
        self::handleError(404, $message);
    }

    public static function handleServerError($message = 'Erreur interne du serveur') {
        self::handleError(500, $message);
    }

    public static function logError($error, $context = []) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'error' => $error,
            'context' => $context,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];

        // In production, write to actual log file
        error_log(json_encode($logEntry));
    }
}
?>