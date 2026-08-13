<?php

/**
 * PsySH configuration
 * https://github.com/bobthecow/psysh/wiki/Configuration.
 */
return [
    'colorMode' => Psy\Configuration::COLOR_MODE_AUTO,

    'dataDir'    => __DIR__ . '/.psysh/data',
    'configDir'  => __DIR__ . '/.psysh/config',
    'runtimeDir' => __DIR__ . '/.psysh/runtime',

    'defaultIncludes' => [
        __DIR__ . '/app/bootstrap/bootstrap.psysh.php',
    ],

    'implicitUse' => [
        'includeNamespaces' => [
            'App\\',
            'App\\Entity\\',
            'App\\Enum\\',
        ],
    ],
];
