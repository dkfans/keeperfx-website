<?php

return \App\TaskScheduler::schedule(
    \App\TaskScheduler::task(
        description: 'Clear old mirrored build prototypes',
        console_command: 'kfx:clear-old-prototypes',
        interval: 'daily',
    ),
);
