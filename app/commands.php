<?php

// CLI console commands

return [

    // KeeperFX
    \App\Console\Command\KeeperFX\FetchAlphaCommand::class,
    \App\Console\Command\KeeperFX\FetchStableCommand::class,
    \App\Console\Command\KeeperFX\FetchPrototypeCommand::class,
    \App\Console\Command\KeeperFX\FetchWikiCommand::class,
    \App\Console\Command\KeeperFX\HandleCommitsCommand::class,
    \App\Console\Command\KeeperFX\PullRepoCommand::class,
    \App\Console\Command\KeeperFX\FetchForumActivityCommand::class,
    \App\Console\Command\KeeperFX\HandleTwitchStreamsCommand::class,
    \App\Console\Command\KeeperFX\ClearOldPrototypesCommand::class,
    \App\Console\Command\KeeperFX\FetchDiscordInfoCommand::class,

    // User
    \App\Console\Command\User\CreateUserCommand::class,
    \App\Console\Command\User\ClearOldPasswordResetTokensCommand::class,
    \App\Console\Command\User\ClearOldNotificationsCommand::class,
    \App\Console\Command\User\GenerateAllAvatarThumbnailsCommand::class,
    \App\Console\Command\User\HandleNewIpLogsCommand::class,

    // Workshop
    \App\Console\Command\Workshop\AddWorkshopTagCommand::class,
    \App\Console\Command\Workshop\FetchUnearthCommand::class,
    \App\Console\Command\Workshop\FetchCreatureMakerCommand::class,
    \App\Console\Command\Workshop\GenerateAllThumbnailsCommand::class,
    \App\Console\Command\Workshop\FixWorkshopRatingsCommand::class,

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
    \App\Console\Command\Mail\CreateMailCommand::class,
    \App\Console\Command\Mail\SendMailCommand::class,
    // \App\Console\Command\Mail\SendMailFromQueueCommand::class,
    \App\Console\Command\Mail\SendAllMailFromQueueCommand::class,
    \App\Console\Command\Mail\MassVerifyEmailCommand::class,

    // Lubiki
    \App\Console\Command\Lubiki\LubikiAddFileDumpToWorkshopCommand::class,

    // Website
    \App\Console\Command\Website\CacheWebsiteChangelogCommand::class,
];
