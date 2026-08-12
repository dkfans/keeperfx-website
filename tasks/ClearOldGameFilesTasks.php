<?php

return App\TaskScheduler::schedule(
    App\TaskScheduler::task(
        description: 'Clear old game files',
        console_command: 'kfx:clear-old-game-files',
        interval: 'daily',
    ),
);
