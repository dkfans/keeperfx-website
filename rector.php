<?php

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([__DIR__ . '/src'])
    ->withComposerBased(
        twig: true,
        doctrine: true
    );
