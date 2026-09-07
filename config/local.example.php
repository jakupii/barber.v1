<?php

declare(strict_types=1);

return [
    // Leave empty when the site is installed directly in public_html.
    // Example for a subfolder installation: '/GentlemanBarber'
    'base_path' => '',

    // SQLite works without extra setup. To use Hostinger MySQL, replace these values.
    'database' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'u123456789_gentleman',
        'username' => 'u123456789_admin',
        'password' => 'CHANGE_THIS_DATABASE_PASSWORD',
        'charset' => 'utf8mb4',
    ],
];
