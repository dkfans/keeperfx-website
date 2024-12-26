<?php

use App\Config\Config;

$schedule = new \Crunz\Schedule();

$task = $schedule->run(\PHP_BINARY . ' ' . \dirname(__DIR__) . '/console workshop:fix-ratings');
$task
    ->everySixHours()
    ->description('Fix and recalculate workshop ratings')
    ->preventOverlapping()
    ->appendOutputTo(($_ENV['APP_LOG_STORAGE'] ?? __DIR__ . '/../logs') . '/' . basename(__FILE__, '.php') . '.log');

return $schedule;
