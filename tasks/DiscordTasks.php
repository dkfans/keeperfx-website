<?php

$schedule = new \Crunz\Schedule();

$task = $schedule->run(\PHP_BINARY . ' ' . \dirname(__DIR__) . '/console kfx:fetch-discord-info');
$task
    ->everyMinute()
    ->description('Fetch Discord information')
    ->preventOverlapping();

return $schedule;
