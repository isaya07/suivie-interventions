<?php
// Environment configuration - SECURE VERSION
// IMPORTANT: This file should not be committed to version control

// Load .env file if it exists
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Skip comments
        }
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

return [
    'DB_HOST' => $_ENV['DB_HOST'] ?? 'localhost',
    'DB_NAME' => $_ENV['DB_NAME'] ?? 'suivi_interventions',
    'DB_USER' => $_ENV['DB_USER'] ?? 'root',
    'DB_PASS' => $_ENV['DB_PASS'] ?? '',
    'DB_CHARSET' => 'utf8',

    'UPLOAD_MAX_SIZE' => 10 * 1024 * 1024, // 10MB
    'UPLOAD_PATH' => __DIR__ . '/../uploads/',
    'SESSION_LIFETIME' => 86400, // 24h

    'BCRYPT_COST' => 12,
    'SESSION_NAME' => 'INTERVENTION_SESS',
    'CORS_ORIGIN' => $_ENV['CORS_ORIGIN'] ?? 'http://localhost:3000',

    'SMTP_HOST' => $_ENV['SMTP_HOST'] ?? '',
    'SMTP_USER' => $_ENV['SMTP_USER'] ?? '',
    'SMTP_PASS' => $_ENV['SMTP_PASS'] ?? '',
];
?>