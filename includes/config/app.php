<?php
return [
    'name' => 'Nivaro Capital',
    'tagline' => 'Build wealth with disciplined digital investments',
    'currency' => 'USDT',
    'timezone' => 'Africa/Lagos',
    'base_url' => env('APP_BASE_URL', ''),
    'demo_user' => [
        'email' => 'apex@example.com',
        'password' => 'password123',
    ],
    'demo_admin' => [
        'email' => 'admin@example.com',
        'password' => 'admin123',
    ],
    'database' => [
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'name' => env('DB_NAME', 'investment_platform'),
        'user' => env('DB_USER', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
    ],
    // Production database mode.
    // Leave false while the app still uses the PHP-session demo store.
    // Set true (or DB_ENABLED=true in the environment) ONLY after the service
    // layer has been migrated to MySQL and verified against a live database.
    'db_enabled' => filter_var(env('DB_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN),
];
