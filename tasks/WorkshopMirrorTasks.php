<?php

return \App\TaskScheduler::schedule(
    \App\TaskScheduler::task(
        description: 'Fetch latest version of Unearth',
        console_command: 'workshop:fetch-unearth',
        interval: 'everyTenMinutes',
    ),
    \App\TaskScheduler::task(
        description: 'Fetch latest version of CreatureMaker',
        console_command: 'workshop:fetch-creature-maker',
        interval: 'everyTenMinutes',
    ),
);
