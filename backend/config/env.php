<?php
// Configuration environnement
define('DB_HOST', 'localhost');
define('DB_NAME', 'suivi_interventions');
define('DB_USER', 'suivi_interventions');
define('DB_PASS', 'Fraisse@43');

define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('SESSION_LIFETIME', 86400); // 24h

// Sécurité
define('BCRYPT_COST', 12);
define('SESSION_NAME', 'INTERVENTION_SESS');

// Email (pour notifications futures)
define('SMTP_HOST', 'your-smtp-server');
define('SMTP_USER', 'your-email@domain.com');
define('SMTP_PASS', 'your-smtp-password');
?>