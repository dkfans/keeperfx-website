<?php

return \App\TaskScheduler::schedule(
    \App\TaskScheduler::task(
        description: 'Pull the game git repo from github',
        console_command: 'kfx:pull-repo',
        interval: 'everyTenMinutes',
    ),
    \App\TaskScheduler::task(
        description: 'Handle game git repo commits and create changelogs',
        console_command: 'kfx:handle-commits',
        interval: 'everyTenMinutes',
    ),
    \App\TaskScheduler::task(
        description: 'Fetch the game dev wiki from github',
        console_command: 'kfx:fetch-wiki',
        interval: 'everyTenMinutes',
    ),
);
