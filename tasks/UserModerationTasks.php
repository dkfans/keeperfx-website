<?php

$schedule = new \Crunz\Schedule();

$task = $schedule->run(\PHP_BINARY . ' ' . \dirname(__DIR__) . '/console user:clear-old-password-reset');
$task
    ->daily()
    ->description('Remove stale password reset tokens')
    ->preventOverlapping()
    ->appendOutputTo(($_ENV['APP_LOG_STORAGE'] ?? APP_ROOT . '/logs') . '/' . basename(__FILE__, '.php') . '.log');

$task2 = $schedule->run(\PHP_BINARY . ' ' . \dirname(__DIR__) . '/console user:clear-old-notifications');
$task2
    ->daily()
    ->description('Remove old notifications')
    ->preventOverlapping()
    ->appendOutputTo(($_ENV['APP_LOG_STORAGE'] ?? APP_ROOT . '/logs') . '/' . basename(__FILE__, '.php') . '.log');

return $schedule;
