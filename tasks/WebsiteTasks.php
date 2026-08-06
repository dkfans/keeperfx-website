<?php

return \App\TaskScheduler::schedule(
    \App\TaskScheduler::task(
        description: 'Cache the git commits for our website',
        console_command: 'website:cache-git-commits',
        interval: 'everyFourHours',
    ),
);
