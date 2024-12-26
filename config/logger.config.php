<?php

use Monolog\Level;
use App\Config\Config;

/**
 * Logger configuration
 *
 * Monolog Reference: https://seldaek.github.io/monolog/doc/01-usage.html
 */
return [

    'logs' => [

        'default_log' => [
            'is_enabled' => true,
            'level'      => Level::Info, // info
            'path'       => (Config::get('storage.path.logs') ?? APP_ROOT . '/logs') . '/app.log',
        ],

        'error_log' => [
            'is_enabled' => true,
            'level'      => Level::Warning, // warning
            'path'       => (Config::get('storage.path.logs') ?? APP_ROOT . '/logs') . '/app.error.log',
        ],

        'debug_log' => [
            'is_enabled' => $_ENV['APP_ENV'] === 'dev',
            'level'      => Level::Debug, // debug
            'path'       => (Config::get('storage.path.logs') ?? APP_ROOT . '/logs') . '/app.debug.log',
        ],
    ],

];
