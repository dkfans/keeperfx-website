<?php

use App\Config\Config;

$schedule = new \Crunz\Schedule();

$task = $schedule->run(\PHP_BINARY . ' ' . \dirname(__DIR__) . '/console kfx:clear-old-prototypes');
$task
    ->daily()
    ->description('Clear old mirrored build prototypes')
    ->preventOverlapping()
    ->appendOutputTo((Config::get('storage.path.logs') ?? APP_ROOT . '/logs') . '/' . basename(__FILE__, '.php') . '.log');

return $schedule;
