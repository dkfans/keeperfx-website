<?php

$schedule = new \Crunz\Schedule();

$task = $schedule->run(\PHP_BINARY . ' ' . \dirname(__DIR__) . '/console workshop:fetch-unearth');
$task
    ->everyTenMinutes()
    ->description('Fetch latest version of Unearth')
    ->preventOverlapping();

$task2 = $schedule->run(\PHP_BINARY . ' ' . \dirname(__DIR__) . '/console workshop:fetch-creature-maker');
$task2
    ->everyTenMinutes()
    ->description('Fetch latest version of CreatureMaker')
    ->preventOverlapping();

return $schedule;
