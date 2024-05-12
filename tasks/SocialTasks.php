<?php

$schedule = new \Crunz\Schedule();

$task = $schedule->run(\PHP_BINARY . ' ' . \dirname(__DIR__) . '/console kfx:fetch-forum-activity');
$task
    ->everyTenMinutes()
    ->description('Fetch the forum activity from the Keeper Klan forums')
    ->preventOverlapping()
    ->appendOutputTo(__DIR__ . '/../logs/tasks/' . basename(__FILE__, '.php') . '.log');

$task2 = $schedule->run(\PHP_BINARY . ' ' . \dirname(__DIR__) . '/console kfx:handle-twitch-streams');
$task2
    ->everyMinute()
    ->description('Fetch and handle connected Twitch streams playing KeeperFX')
    ->preventOverlapping()
    ->appendOutputTo(__DIR__ . '/../logs/tasks/' . basename(__FILE__, '.php') . '.log');

return $schedule;
