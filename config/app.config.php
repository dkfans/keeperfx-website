<?php

use Symfony\Component\Cache\Adapter\FilesystemAdapter;

return [

    'app_name' => 'keeperfx-web',

    /**
     * Whoops error handler
     * Creates fancy error pages for development
     *
     * Reference: https://github.com/filp/whoops/blob/master/docs/API%20Documentation.md
     */
    'whoops' => [
        'is_enabled' => $_ENV['APP_ENV'] === 'dev',
        'editor'     => $_ENV['APP_DEV_WHOOPS_EDITOR'],
    ],

    'workshop' => [
        'item_types_without_difficulty' => [
            \App\Enum\WorkshopType::Creature,
            \App\Enum\WorkshopType::Application,
            \App\Enum\WorkshopType::Other,
        ],
    ],

    'disallowed_username_words' => [
        'keeperfx',
        'kfx',
        'admin',
        'moderator',
        'developer',
        'workshop',
    ],

];
