<?php

// Configuration for IP Whitelist Manager Service
$tokenFile = __DIR__ . '/data/api_token.txt';
$passwordFile = __DIR__ . '/data/web_password.txt';

// Generate or load API token
if (!file_exists($tokenFile)) {
    $token = 'changeme_' . bin2hex(random_bytes(16));
    file_put_contents($tokenFile, $token);
} else {
    $token = trim(file_get_contents($tokenFile));
}

// Generate or load web password hash
if (!file_exists($passwordFile)) {
    $passwordHash = password_hash('admin', PASSWORD_DEFAULT);
    file_put_contents($passwordFile, $passwordHash);
} else {
    $passwordHash = trim(file_get_contents($passwordFile));
}

return [
    'port' => 9999,
    'host' => '0.0.0.0',
    'photobooth_config' => '/var/www/html/config/my.config.inc.php',
    'data_file' => __DIR__ . '/data/ips.json',
    'backup_dir' => __DIR__ . '/data/backups',
    'api_token' => $token,
    'web_password' => $passwordHash,
];

