<?php

return App\TaskScheduler::schedule(
    App\TaskScheduler::task(
        description: 'Fetch the latest GeoIP database',
        console_command: 'website:fetch-geoip-db',
        interval: 'daily',
    ),
);
