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
            'path'       => ($_ENV['APP_LOG_STORAGE'] ?? '/app/log') . '/app.log',
        ],

        'error_log' => [
            'is_enabled' => true,
            'level'      => Level::Warning, // warning
            'path'       => ($_ENV['APP_LOG_STORAGE'] ?? '/app/log') . '/app.error.log',
        ],

        'debug_log' => [
            'is_enabled' => $_ENV['APP_ENV'] === 'dev',
            'level'      => Level::Debug, // debug
            'path'       => ($_ENV['APP_LOG_STORAGE'] ?? '/app/log') . '/app.debug.log',
        ],
    ],

];
