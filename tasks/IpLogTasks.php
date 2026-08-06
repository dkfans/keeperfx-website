<?php

return \App\TaskScheduler::schedule(
    \App\TaskScheduler::task(
        description: 'Handle new ip logs and get info about them',
        console_command: 'user:handle-new-ip-logs',
        interval: 'everyMinute',
    ),
);
