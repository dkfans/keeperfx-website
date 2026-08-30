<?php

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/config',
        __DIR__ . '/controllers',
        __DIR__ . '/migrations',
        __DIR__ . '/src',
    ])
    ->withComposerBased(
        twig: true,
        doctrine: true,
        phpunit: true,
        symfony: true,
    );
