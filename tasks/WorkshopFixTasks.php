<?php

use App\Config\Config;

$schedule = new \Crunz\Schedule();

$task = $schedule->run(\PHP_BINARY . ' ' . \dirname(__DIR__) . '/console workshop:fix-ratings');
$task
    ->everySixHours()
    ->description('Fix and recalculate workshop ratings')
    ->preventOverlapping()
    ->appendOutputTo((Config::get('storage.path.logs') ?? APP_ROOT . '/logs') . '/' . basename(__FILE__, '.php') . '.log');

return $schedule;
