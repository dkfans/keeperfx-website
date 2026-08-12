<?php

return App\TaskScheduler::schedule(
    App\TaskScheduler::task(
        description: 'Fetch the launcher from github',
        console_command: 'kfx:fetch-launcher',
        interval: 'everyMinute',
    ),
    App\TaskScheduler::task(
        description: 'Fetch the stable releases from github',
        console_command: 'kfx:fetch-stable',
        interval: 'everyMinute',
    ),
    App\TaskScheduler::task(
        description: 'Fetch the alpha patches from github',
        console_command: 'kfx:fetch-alpha',
        interval: 'everyMinute',
    ),
    App\TaskScheduler::task(
        description: 'Fetch the prototypes from github',
        console_command: 'kfx:fetch-prototype',
        interval: 'everyMinute',
    ),
);
