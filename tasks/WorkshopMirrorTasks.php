<?php

use App\Config\Config;

$schedule = new \Crunz\Schedule();

$task = $schedule->run(\PHP_BINARY . ' ' . \dirname(__DIR__) . '/console workshop:fetch-unearth');
$task
    ->everyTenMinutes()
    ->description('Fetch latest version of Unearth')
    ->preventOverlapping()
    ->appendOutputTo(($_ENV['APP_LOG_STORAGE'] ?? __DIR__ . '/../logs') . '/' . basename(__FILE__, '.php') . '.log');

$task2 = $schedule->run(\PHP_BINARY . ' ' . \dirname(__DIR__) . '/console workshop:fetch-creature-maker');
$task2
    ->everyTenMinutes()
    ->description('Fetch latest version of CreatureMaker')
    ->preventOverlapping()
    ->appendOutputTo(($_ENV['APP_LOG_STORAGE'] ?? __DIR__ . '/../logs') . '/' . basename(__FILE__, '.php') . '.log');

return $schedule;
