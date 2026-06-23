<?php

use App\Config\Config;

$schedule = new \Crunz\Schedule();

$task = $schedule->run(\PHP_BINARY . ' ' . \dirname(__DIR__) . '/console kfx:fetch-discord-info');
$task
    ->everyMinute()
    ->description('Fetch Discord information')
    ->preventOverlapping()
    ->appendOutputTo(($_ENV['APP_LOG_STORAGE'] ?? '/app/log') . '/' . basename(__FILE__, '.php') . '.log');

return $schedule;
