<?php

return [

    // CDN information / endpoints
    'endpoints' => [

        // The main endpoint (required)
        // This will almost always be pointed to the current domain
        // It should be left as is
        'main'       => [
            'name'     => \str_replace(
                'keeperfx',
                'KeeperFX',
                \parse_url($_ENV['APP_ROOT_URL'], PHP_URL_HOST) . (\parse_url($_ENV['APP_ROOT_URL'], PHP_URL_PORT) ? ':' . \parse_url($_ENV['APP_ROOT_URL'], PHP_URL_PORT) : '')
            ),
            'url'      => $_ENV['APP_ROOT_URL'],
            'location' => 'Germany',
        ],

        // The KeeperFX.net endpoint
        // This one should only be used for testing
        // 'kfx' => [
        //     'name'     => 'KeeperFX.net',
        //     'url'      => 'https://keeperfx.net',
        //     'location' => 'Germany',
        // ],

        'cloudflare' => [
            'name'     => 'Cloudflare CDN',
            'url'      => 'https://cdn-cf1.keeperfx.net',
            'location' => 'Worldwide',
        ],
    ],

    // The default CDN to use as fallback
    'default' => 'main',

    // Use default CDNs for specific countries
    'country_defaults' => [
        'DE' => 'main', // Some German ISPs have a bad connection to Cloudflare
        'US' => 'cloudflare',
        'AU' => 'cloudflare',
    ],

];
