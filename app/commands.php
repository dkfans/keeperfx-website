<?php

// CLI console commands

return [

    // User
    \App\Console\Command\User\CreateUserCommand::class,

    // Github Fetch commands
    \App\Console\Command\Github\GithubFetchAlphaCommand::class,
    \App\Console\Command\Github\GithubFetchStableCommand::class,
    \App\Console\Command\Github\GithubFetchWikiCommand::class,

    // Project git commands
    \App\Console\Command\Project\ProjectPullCommand::class,
    \App\Console\Command\Project\ProjectHandleCommitsCommand::class,

    // Cache
    \App\Console\Command\Cache\CacheClearCommand::class,

    // Controller
    \App\Console\Command\Controller\ControllerCreateCommand::class,

    // Maintenance
    \App\Console\Command\Maintenance\MaintenanceStartCommand::class,
    \App\Console\Command\Maintenance\MaintenanceStopCommand::class,

];
