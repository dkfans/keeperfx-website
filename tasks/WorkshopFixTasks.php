<?php

return App\TaskScheduler::schedule(
    App\TaskScheduler::task(
        description: 'Fix and recalculate workshop ratings',
        console_command: 'workshop:fix-ratings',
        interval: 'everySixHours',
    ),
);
