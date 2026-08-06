<?php

use App\Config\Config;

$schedule = new \Crunz\Schedule();

$task = $schedule->run(\PHP_BINARY . ' ' . \dirname(__DIR__) . '/console workshop:virus-scan new');
$task
    ->everyMinute()
    ->description('Scan new workshop files for malware')
    ->preventOverlapping()
    ->appendOutputTo(($_ENV['APP_LOG_STORAGE'] ?? '/app/log') . '/' . basename(__FILE__, '.php') . '.log');

$task2 = $schedule->run(\PHP_BINARY . ' ' . \dirname(__DIR__) . '/console workshop:virus-scan scanned');
$task2
    ->daily()
    ->description('Scan all previously scanned workshop files for malware')
    ->preventOverlapping()
    ->appendOutputTo(($_ENV['APP_LOG_STORAGE'] ?? '/app/log') . '/' . basename(__FILE__, '.php') . '.log');

return $schedule;
