<?php

use Monolog\Level;

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
            'path'       => APP_ROOT . '/logs/app.log',
        ],

        'error_log' => [
            'is_enabled' => true,
            'level'      => Level::Warning, // warning
            'path'       => APP_ROOT . '/logs/app.error.log',
        ],

        'debug_log' => [
            'is_enabled' => $_ENV['APP_ENV'] === 'dev',
            'level'      => Level::Debug, // debug
            'path'       => APP_ROOT . '/logs/app.debug.log',
        ],
    ],

];
