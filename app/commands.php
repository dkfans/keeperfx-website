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
    \App\Console\Command\KeeperFX\HandleTwitchStreamsCommand::class,

    // User
    \App\Console\Command\User\CreateUserCommand::class,

    // Workshop
    \App\Console\Command\Workshop\AddWorkshopTagCommand::class,

    // Cache
    \App\Console\Command\Cache\CacheClearCommand::class,
    \App\Console\Command\Cache\CacheWarmCommand::class,

    // Controller
    \App\Console\Command\Controller\ControllerCreateCommand::class,

    // Maintenance
    \App\Console\Command\Maintenance\MaintenanceStartCommand::class,
    \App\Console\Command\Maintenance\MaintenanceStopCommand::class,

    // ClamAV scanner
    \App\Console\Command\ClamAV\ScanWorkshopNewCommand::class,
    \App\Console\Command\ClamAV\ScanWorkshopAllCommand::class,

    // Mail
    \App\Console\Command\Mail\SendMailCommand::class,

    // Lubiki
    \App\Console\Command\Lubiki\LubikiAddFileDumpToWorkshopCommand::class,
];
