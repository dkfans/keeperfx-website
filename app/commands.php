<?php

// CLI console commands

return [

    // KeeperFX
    \App\Console\Command\KeeperFX\FetchAlphaCommand::class,
    \App\Console\Command\KeeperFX\FetchStableCommand::class,
    \App\Console\Command\KeeperFX\FetchWikiCommand::class,
    \App\Console\Command\KeeperFX\HandleCommitsCommand::class,
    \App\Console\Command\KeeperFX\PullRepoCommand::class,
    \App\Console\Command\KeeperFX\FetchForumActivityCommand::class,

    // User
    \App\Console\Command\User\CreateUserCommand::class,

    // Cache
    \App\Console\Command\Cache\CacheClearCommand::class,

    // Controller
    \App\Console\Command\Controller\ControllerCreateCommand::class,

    // Maintenance
    \App\Console\Command\Maintenance\MaintenanceStartCommand::class,
    \App\Console\Command\Maintenance\MaintenanceStopCommand::class,

];
