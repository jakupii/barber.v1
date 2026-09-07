<?php

declare(strict_types=1);

/**
 * GentlemanBarber configuration.
 *
 * For a custom installation copy config/local.example.php to config/local.php.
 * local.php is intentionally excluded from the downloadable database backup.
 */
$config = [
    'timezone' => 'Europe/Skopje',
    'base_path' => '',
    'database' => [
        'driver' => 'sqlite',
        'sqlite_path' => dirname(__DIR__) . '/storage/gentlemanbarber.sqlite',
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'gentlemanbarber',
        'username' => '',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
    'booking' => [
        'days_ahead' => 3,
        'retention_days' => 7,
        'opening_time' => '09:00',
        'closing_time' => '22:00',
        'slot_interval_minutes' => 30,
    ],
    'uploads' => [
        'max_bytes' => 3 * 1024 * 1024,
        'directory' => dirname(__DIR__) . '/storage/uploads/barbers',
        'public_prefix' => '/storage/uploads/barbers',
    ],
];

$localFile = __DIR__ . '/local.php';
if (is_file($localFile)) {
    $local = require $localFile;
    if (is_array($local)) {
        $config = array_replace_recursive($config, $local);
    }
}

return $config;
