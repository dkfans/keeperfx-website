<?php

return [

    // Class definition files (and others)
    // -> File structure should be: '<name>.container.php'
    // -> File should return an array
    'definition_dir' => APP_ROOT . '/app/container',

    // Compilation configuration
    // -> Never compiles in 'dev' env, regardless of it being enabled
    'compilation'    => [
        'is_enabled' => true,
        'output_dir' => APP_ROOT . '/cache/container',
    ],

    // Autowire classes
    'autowire' => [
        'is_enabled' => true,

        // Class directories to autowire.
        // - These are recursively loaded.
        // - Defined as: namespace => root class dir
        'paths' => [
            'App\\'             => APP_ROOT . '/src',
            'App\\Controller\\' => APP_ROOT . '/controllers',
        ],

        // Classes to ignore for autowiring.
        // - To manually load them they can be added as a class definition.
        'ignore' => [
            '*Exception.php',
            '*Helper.php',
            '*Interface.php',
        ]

        // TODO: ignore paths */Entities/*.php
        // So we can ignore doctrine entities autowiring
    ],
];
