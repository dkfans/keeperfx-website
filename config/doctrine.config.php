<?php

/**
 * Doctrine configuration file
 */
return [

    'connection' => [
        'host'      => $_ENV['APP_DB_HOST'],
        'port'      => $_ENV['APP_DB_PORT'],
        'user'      => $_ENV['APP_DB_USER'],
        'password'  => $_ENV['APP_DB_PASS'],
        'dbname'    => $_ENV['APP_DB_DATABASE'],
        'driver'    => $_ENV['APP_DB_DRIVER'],
        'charset'   => $_ENV['APP_DB_CHARSET'],
        'collation' => $_ENV['APP_DB_COLLATION'],
    ],

    'entity_dirs' => [
        APP_ROOT . '/src/Entity',
    ],

    'dev_mode' => $_ENV['APP_ENV'] === 'dev',

    'cache_dir' => APP_ROOT . '/cache/doctrine', // If using 'filesystem' cache

    'orm_naming_strategy' => new \Doctrine\ORM\Mapping\UnderscoreNamingStrategy(CASE_LOWER),

    'proxy_dir' => APP_ROOT . '/cache/doctrine/proxy',

    'migration_config' => [
        'table_storage' => [
            'table_name'                 => '_migrations',
            'version_column_name'        => 'version',
            'version_column_length'      => 1024,
            'executed_at_column_name'    => 'executed_at',
            'execution_time_column_name' => 'execution_time',
        ],
        'migrations_paths' => [
            'App\Migrations'            => APP_ROOT . '/migrations',
            'App\Migrations\Production' => APP_ROOT . '/migrations/production',
        ],
        'all_or_nothing'          => true,
        'check_database_platform' => true,
        'organize_migrations'     => 'none',
        'connection'              => null,
        'em'                      => null,
    ],

    'migration_commands' => [
        \Doctrine\Migrations\Tools\Console\Command\CurrentCommand::class,
        \Doctrine\Migrations\Tools\Console\Command\DiffCommand::class,
        \Doctrine\Migrations\Tools\Console\Command\DumpSchemaCommand::class,
        \Doctrine\Migrations\Tools\Console\Command\ExecuteCommand::class,
        \Doctrine\Migrations\Tools\Console\Command\GenerateCommand::class,
        \Doctrine\Migrations\Tools\Console\Command\LatestCommand::class,
        \Doctrine\Migrations\Tools\Console\Command\ListCommand::class,
        \Doctrine\Migrations\Tools\Console\Command\MigrateCommand::class,
        \Doctrine\Migrations\Tools\Console\Command\RollupCommand::class,
        \Doctrine\Migrations\Tools\Console\Command\StatusCommand::class,
        \Doctrine\Migrations\Tools\Console\Command\SyncMetadataCommand::class,
        \Doctrine\Migrations\Tools\Console\Command\VersionCommand::class,
    ],
];
