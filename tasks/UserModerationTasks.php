<?php

return \App\TaskScheduler::schedule(
    \App\TaskScheduler::task(
        description: 'Remove stale password reset tokens',
        console_command: 'user:clear-old-password-reset',
        interval: 'daily',
    ),
    \App\TaskScheduler::task(
        description: 'Remove old notifications',
        console_command: 'user:clear-old-notifications',
        interval: 'daily',
    ),
);
