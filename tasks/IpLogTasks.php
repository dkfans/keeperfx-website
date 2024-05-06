<?php

$schedule = new \Crunz\Schedule();

$task = $schedule->run(\PHP_BINARY . ' ' . \dirname(__DIR__) . '/console user:handle-new-ip-logs');
$task
    ->everyMinute()
    ->description('Handle new ip logs and get info about them')
    ->preventOverlapping();

return $schedule;
