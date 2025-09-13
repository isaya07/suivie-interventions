<?php
// Example environment configuration
// Copy this file to env.php and update with your actual values

return [
    'DB_HOST' => 'localhost',
    'DB_NAME' => 'suivi_interventions',
    'DB_USER' => 'your_db_username',
    'DB_PASS' => 'your_secure_password',
    'DB_CHARSET' => 'utf8',

    'SESSION_TIMEOUT' => 86400, // 24 hours in seconds
    'UPLOAD_MAX_SIZE' => 10485760, // 10MB in bytes
    'ALLOWED_UPLOAD_TYPES' => ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'],

    'CORS_ORIGIN' => 'http://localhost:3000',
    'API_RATE_LIMIT' => 100, // requests per minute
];
?>