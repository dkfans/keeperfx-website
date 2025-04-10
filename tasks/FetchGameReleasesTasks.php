<?php

use App\Config\Config;

$schedule = new \Crunz\Schedule();

// Launcher
$task_launcher = $schedule->run(\PHP_BINARY . ' ' . \dirname(__DIR__) . '/console kfx:fetch-launcher');
$task_launcher
    ->everyMinute()
    ->description('Fetch the launcher from github')
    ->preventOverlapping()
    ->appendOutputTo(($_ENV['APP_LOG_STORAGE'] ?? __DIR__ . '/../logs') . '/' . basename(__FILE__, '.php') . '.log');

// Stable
$task_stable = $schedule->run(\PHP_BINARY . ' ' . \dirname(__DIR__) . '/console kfx:fetch-stable');
$task_stable
    ->everyMinute()
    ->description('Fetch the stable releases from github')
    ->preventOverlapping()
    ->appendOutputTo(($_ENV['APP_LOG_STORAGE'] ?? __DIR__ . '/../logs') . '/' . basename(__FILE__, '.php') . '.log');

// Alpha
$task_alpha = $schedule->run(\PHP_BINARY . ' ' . \dirname(__DIR__) . '/console kfx:fetch-alpha');
$task_alpha
    ->everyMinute()
    ->description('Fetch the alpha patches from github')
    ->preventOverlapping()
    ->appendOutputTo(($_ENV['APP_LOG_STORAGE'] ?? __DIR__ . '/../logs') . '/' . basename(__FILE__, '.php') . '.log');

// Prototype
$task_prototype = $schedule->run(\PHP_BINARY . ' ' . \dirname(__DIR__) . '/console kfx:fetch-prototype');
$task_prototype
    ->everyMinute()
    ->description('Fetch the prototypes from github')
    ->preventOverlapping()
    ->appendOutputTo(($_ENV['APP_LOG_STORAGE'] ?? __DIR__ . '/../logs') . '/' . basename(__FILE__, '.php') . '.log');

return $schedule;
