<?php
// Environment configuration - SECURE VERSION
// IMPORTANT: This file should not be committed to version control

return [
    'DB_HOST' => 'localhost',
    'DB_NAME' => 'suivi_interventions',
    'DB_USER' => 'suivi_interventions',
    'DB_PASS' => 'Fraisse@43', // TODO: Change to a secure password
    'DB_CHARSET' => 'utf8',

    'UPLOAD_MAX_SIZE' => 10 * 1024 * 1024, // 10MB
    'UPLOAD_PATH' => __DIR__ . '/../uploads/',
    'SESSION_LIFETIME' => 86400, // 24h

    'BCRYPT_COST' => 12,
    'SESSION_NAME' => 'INTERVENTION_SESS',
    'CORS_ORIGIN' => 'http://localhost:3000',

    'SMTP_HOST' => 'your-smtp-server',
    'SMTP_USER' => 'your-email@domain.com',
    'SMTP_PASS' => 'your-smtp-password',
];
?>