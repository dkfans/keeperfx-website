<?php

return App\TaskScheduler::schedule(
    App\TaskScheduler::task(
        description: 'Send all mails in the mail queue',
        console_command: 'mail:send-queue-all',
        interval: 'everyMinute',
    ),
);
