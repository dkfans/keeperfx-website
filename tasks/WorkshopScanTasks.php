<?php

$schedule = new \Crunz\Schedule();

$task = $schedule->run(\PHP_BINARY . ' ' . \dirname(__DIR__) . '/console clamav:scan-workshop-new');
$task
    ->everyMinute()
    ->description('Scan new workshop files for malware')
    ->preventOverlapping()
    ->appendOutputTo(($_ENV['APP_LOG_STORAGE'] ?? APP_ROOT . '/logs') . '/' . basename(__FILE__, '.php') . '.log');

$task2 = $schedule->run(\PHP_BINARY . ' ' . \dirname(__DIR__) . '/console clamav:scan-workshop-all');
$task2
    ->daily()
    ->description('Scan all workshop files for malware')
    ->preventOverlapping()
    ->appendOutputTo(($_ENV['APP_LOG_STORAGE'] ?? APP_ROOT . '/logs') . '/' . basename(__FILE__, '.php') . '.log');

return $schedule;
