<?php

/**
 * Symfony Cache configuration
 * Reference: https://symfony.com/doc/current/components/cache.html
 */
return [

    /**
     * Default lifetime duration of cache items in seconds. (0 = infinite)
     * Custom durations can be defined when setting a cache key.
     */
    'default_lifetime' => 0,

    /**
     * Namespace to append to the keys
     * Set as null to use the app.app_name config variable
     */
    'namespace' => null,

    /**
     * The storage directory for cached files
     * This is required because not everything will be handled by the cache adapter and some stuff will always use the filesystem
     */
    'file_storage_dir' => $_ENV['APP_CACHE_DIR'] ?? '/app/cache',

    /**
     * Adapter to use
     *
     * Currently implemented:
     * - filesystem
     * - redis
     */
    'adapter' => $_ENV['APP_CACHE_ADAPTER'],

    /**
     * Adapter configuration
     */
    'adapter_config' => [
        'filesystem' => [
            'dir' => ($_ENV['APP_CACHE_DIR'] ?? '/app/cache') . '/app',
        ],
        'redis' => [
            'dsn' => $_ENV['APP_CACHE_REDIS_DSN']
        ]
    ]
];
