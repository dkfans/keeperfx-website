<?php

return App\TaskScheduler::schedule(
    App\TaskScheduler::task(
        description: 'Fetch the forum activity from the Keeper Klan forums',
        console_command: 'kfx:fetch-forum-activity',
        interval: 'everyTenMinutes',
    ),
    App\TaskScheduler::task(
        description: 'Fetch and handle connected Twitch streams playing KeeperFX',
        console_command: 'kfx:handle-twitch-streams',
        interval: 'everyMinute',
    ),
);
