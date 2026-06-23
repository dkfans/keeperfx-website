<?php

use App\Config\Config;

$schedule = new \Crunz\Schedule();

$task = $schedule->run(\PHP_BINARY . ' ' . \dirname(__DIR__) . '/console website:cache-git-commits');
$task
    ->everyFourHours()
    ->description('Cache the git commits for our website')
    ->preventOverlapping()
    ->appendOutputTo(($_ENV['APP_LOG_STORAGE'] ?? '/app/log') . '/' . basename(__FILE__, '.php') . '.log');

return $schedule;
