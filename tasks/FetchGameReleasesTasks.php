<?php

$schedule = new \Crunz\Schedule();

$task = $schedule->run(\PHP_BINARY . ' ' . \dirname(__DIR__) . '/console kfx:fetch-stable');
$task
    ->everyTenMinutes()
    ->description('Fetch the stable releases from github')
    ->preventOverlapping();

$task2 = $schedule->run(\PHP_BINARY . ' ' . \dirname(__DIR__) . '/console kfx:fetch-alpha');
$task2
    ->everyFiveMinutes()
    ->description('Fetch the alpha patches from github')
    ->preventOverlapping();

$task3 = $schedule->run(\PHP_BINARY . ' ' . \dirname(__DIR__) . '/console kfx:fetch-prototype');
$task3
    ->everyFiveMinutes()
    ->description('Fetch the prototypes from github')
    ->preventOverlapping();

return $schedule;
