<?php

return \App\TaskScheduler::schedule(
    \App\TaskScheduler::task(
        description: 'Fetch Discord information',
        console_command: 'kfx:fetch-discord-info',
        interval: 'everyMinute',
    ),
);
