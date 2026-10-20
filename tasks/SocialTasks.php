<?php

return App\TaskScheduler::schedule(
    App\TaskScheduler::task(
        description: 'Fetch the forum activity from the Keeper Klan forums',
        console_command: 'kfx:fetch-forum-activity',
        interval: 'everyTenMinutes',
    ),
    App\TaskScheduler::task(
        description: 'Fetch and handle Twitch streams that are playing KeeperFX',
        console_command: 'kfx:fetch-homepage-twitch-streams',
        interval: 'everyMinute',
    ),
    App\TaskScheduler::task(
        description: 'Fetch and notify active Twitch streams to Discord',
        console_command: 'kfx:monitor-and-post-twitch-streams',
        interval: 'everyMinute',
    ),
);
