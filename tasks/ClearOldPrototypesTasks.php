<?php

$schedule = new \Crunz\Schedule();

$task = $schedule->run(\PHP_BINARY . ' ' . \dirname(__DIR__) . '/console kfx:clear-old-prototypes');
$task
    ->daily()
    ->description('Clear old mirrored build prototypes')
    ->preventOverlapping()
    ->appendOutputTo(__DIR__ . '/../logs/tasks/' . basename(__FILE__, '.php') . '.log');

return $schedule;
