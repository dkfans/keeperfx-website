<?php

use App\Config\Config;

$schedule = new \Crunz\Schedule();

$task = $schedule->run(\PHP_BINARY . ' ' . \dirname(__DIR__) . '/console kfx:fetch-forum-activity');
$task
    ->everyTenMinutes()
    ->description('Fetch the forum activity from the Keeper Klan forums')
    ->preventOverlapping()
    ->appendOutputTo(($_ENV['APP_LOG_STORAGE'] ?? __DIR__ . '/../logs') . '/' . basename(__FILE__, '.php') . '.log');

$task2 = $schedule->run(\PHP_BINARY . ' ' . \dirname(__DIR__) . '/console kfx:handle-twitch-streams');
$task2
    ->everyMinute()
    ->description('Fetch and handle connected Twitch streams playing KeeperFX')
    ->preventOverlapping()
    ->appendOutputTo(($_ENV['APP_LOG_STORAGE'] ?? __DIR__ . '/../logs') . '/' . basename(__FILE__, '.php') . '.log');

return $schedule;
