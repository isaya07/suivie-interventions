<?php
// config/logger.php
class Logger {
    public static function logLogin($username, $success, $ip) {
        $status = $success ? 'SUCCESS' : 'FAILED';
        error_log("[LOGIN {$status}] User: {$username}, IP: {$ip}");
    }
    
    public static function logFileUpload($user_id, $filename, $size) {
        error_log("[FILE_UPLOAD] User: {$user_id}, File: {$filename}, Size: {$size}");
    }
    
    public static function logInterventionAction($user_id, $intervention_id, $action) {
        error_log("[INTERVENTION] User: {$user_id}, ID: {$intervention_id}, Action: {$action}");
    }
}
?>