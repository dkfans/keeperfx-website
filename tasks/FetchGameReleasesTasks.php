<?php

use App\Config\Config;

$schedule = new \Crunz\Schedule();

$task = $schedule->run(\PHP_BINARY . ' ' . \dirname(__DIR__) . '/console kfx:fetch-stable');
$task
    ->everyTenMinutes()
    ->description('Fetch the stable releases from github')
    ->preventOverlapping()
    ->appendOutputTo(($_ENV['APP_LOG_STORAGE'] ?? __DIR__ . '/../logs') . '/' . basename(__FILE__, '.php') . '.log');

$task2 = $schedule->run(\PHP_BINARY . ' ' . \dirname(__DIR__) . '/console kfx:fetch-alpha');
$task2
    ->everyMinute()
    ->description('Fetch the alpha patches from github')
    ->preventOverlapping()
    ->appendOutputTo(($_ENV['APP_LOG_STORAGE'] ?? __DIR__ . '/../logs') . '/' . basename(__FILE__, '.php') . '.log');

$task3 = $schedule->run(\PHP_BINARY . ' ' . \dirname(__DIR__) . '/console kfx:fetch-prototype');
$task3
    ->everyMinute()
    ->description('Fetch the prototypes from github')
    ->preventOverlapping()
    ->appendOutputTo(($_ENV['APP_LOG_STORAGE'] ?? __DIR__ . '/../logs') . '/' . basename(__FILE__, '.php') . '.log');

return $schedule;
