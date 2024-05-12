<?php

$schedule = new \Crunz\Schedule();

$task = $schedule->run(\PHP_BINARY . ' ' . \dirname(__DIR__) . '/console mail:send-queue-all');
$task
    ->everyMinute()
    ->description('Send all mails in the mail queue')
    ->preventOverlapping()
    ->appendOutputTo(__DIR__ . '/../logs/tasks/' . basename(__FILE__, '.php') . '.log');

return $schedule;
