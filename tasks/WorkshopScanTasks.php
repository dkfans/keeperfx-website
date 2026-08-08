<?php

return \App\TaskScheduler::schedule(
    \App\TaskScheduler::task(
        description: 'Scan new workshop files for malware',
        console_command: 'workshop:virus-scan new',
        interval: 'everyMinute',
    ),
    \App\TaskScheduler::task(
        description: 'Scan all previously scanned workshop files for malware',
        console_command: 'workshop:virus-scan scanned',
        interval: 'daily',
    ),
);
