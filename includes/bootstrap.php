<?php
// Resolve environment variables so production hosts (Render, Railway, etc.)
// can override config without editing committed files. Defined first so the
// config file below can call env().
if (!function_exists('env')) {
    function env(string $key, $default = null)
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        return ($value === false || $value === null) ? $default : $value;
    }
}

$config = require __DIR__ . '/config/app.php';
date_default_timezone_set($config['timezone']);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require __DIR__ . '/functions.php';
require __DIR__ . '/db.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/auth_db.php';
require __DIR__ . '/data.php';
require __DIR__ . '/services/customer.php';
require __DIR__ . '/services/platform.php';

initialize_demo_state();
