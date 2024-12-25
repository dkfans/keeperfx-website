<?php

use App\Config\Config;

$schedule = new \Crunz\Schedule();

$task = $schedule->run(\PHP_BINARY . ' ' . \dirname(__DIR__) . '/console user:handle-new-ip-logs');
$task
    ->everyMinute()
    ->description('Handle new ip logs and get info about them')
    ->preventOverlapping()
    ->appendOutputTo((Config::get('storage.path.logs') ?? APP_ROOT . '/logs') . '/' . basename(__FILE__, '.php') . '.log');

return $schedule;
