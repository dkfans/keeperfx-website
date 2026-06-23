<?php

use App\Config\Config;

$schedule = new \Crunz\Schedule();

$task = $schedule->run(\PHP_BINARY . ' ' . \dirname(__DIR__) . '/console website:fetch-geoip-db');
$task
    ->daily()
    ->description('Fetch the latest GeoIP database')
    ->preventOverlapping()
    ->appendOutputTo(($_ENV['APP_LOG_STORAGE'] ?? '/app/log') . '/' . basename(__FILE__, '.php') . '.log');

return $schedule;
