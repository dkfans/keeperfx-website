<?php

$schedule = new \Crunz\Schedule();

$task = $schedule->run(\PHP_BINARY . ' ' . \dirname(__DIR__) . '/console kfx:pull-repo');
$task
    ->everyTenMinutes()
    ->description('Pull the game git repo from github')
    ->preventOverlapping();

$task2 = $schedule->run(\PHP_BINARY . ' ' . \dirname(__DIR__) . '/console kfx:handle-commits');
$task2
    ->everyTenMinutes()
    ->description('Handle game git repo commits and create changelogs')
    ->preventOverlapping();

$task3 = $schedule->run(\PHP_BINARY . ' ' . \dirname(__DIR__) . '/console kfx:fetch-wiki');
$task3
    ->everyTenMinutes()
    ->description('Fetch the game dev wiki from github')
    ->preventOverlapping();

return $schedule;
